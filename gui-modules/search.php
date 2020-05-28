<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

class search extends BasicPage {

	function preprocess(){
        return true;
    }

	function js_content(){
		?>
		$(document).ready(function(){
			$('#searchbox').focus();
		});
		function addItem(upc){
            var qty = $('#qty'+upc).val();
            var maxQty = $('#maxQty'+upc).val();
            if (maxQty > 0 && qty > maxQty) {
                $('#qty'+upc).val(maxQty);
                alert("Maximum allowed is " + maxQty);
                return;
            }
			$.ajax({
				url: '../ajax-callbacks/ajax-add-item.php',
				type: 'post',
				data: 'upc='+upc+'&qty='+qty,
				success: function(resp){
					$('#btn'+upc).html('<a href="/gui-modules/cart.php">In Cart</a>');
				}
			});
		}
		<?php
	}

	function main_content()
    {
		global $IS4C_PATH, $IS4C_LOCAL;
        unset($_SESSION['emp_no']);
		$term = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
        if ($term == '') {
            echo '<div class="alert alert-danger">No search term given</div>>';
            return;
        }

		$dbc = Database::pDataConnect();
		$empno = AuthUtilities::getUID(AuthLogin::checkLogin());
		if ($empno===False) $empno=-999;

		$q = "SELECT p.upc,p.normal_price,
			CASE WHEN p.discounttype IN (1) then p.special_price
				ELSE 0
				END as sale_price,
            CASE WHEN u.description IS NULL OR u.description='' THEN p.description ELSE u.description END AS description,
            CASE WHEN u.brand IS NULL OR u.brand='' THEN p.brand ELSE u.brand END AS brand,
			CASE WHEN l.upc IS NULL THEN 0 ELSE 1 END AS inCart,
            p.special_price, p.discounttype,
            MATCH(u.brand, u.description) AGAINST (?) AS score,
            s.min, s.max, s.step,
            1 AS inUse, p.scale, m.superID
			FROM products AS p INNER JOIN productUser
			AS u ON p.upc=u.upc LEFT JOIN ".$IS4C_LOCAL->get("tDatabase").".localtemptrans
			AS l ON p.upc=l.upc AND l.emp_no=?
            LEFT JOIN superdepts AS m ON p.department=m.dept_ID
			LEFT JOIN productOrderLimits AS o ON p.upc=o.upc
            LEFT JOIN DeptScaling AS s ON p.department=s.dept_no ";
		$args = array($term, $empno);
        $q .= " WHERE MATCH(u.brand, u.description) AGAINST (?) 
                AND u.soldOut=0 ";
        $args[] = $term;
        $q .= "ORDER BY score DESC";
        $q .= " LIMIT 100";

		$p = $dbc->prepare_statement($q);
		$r = $dbc->exec_statement($p, $args);

		$ret = '<table class="table" cellspacing="4" cellpadding="4" id="browsetable">';
		$ret .= '<tr><th>Brand</th><th>Product</th><th>Price</th><th>&nbsp;</th></tr>';
		while($w = $dbc->fetch_row($r)){
            if ($w['inUse'] == 0) continue;
			$price = $w['normal_price'];
			if ($price == 0) {
				$price = 'Free!';
			} else {
				$price = sprintf('%.2f',$price);
                if ($w['sale_price']) {
                    $w['sale_price'] = sprintf('%.2f',$price);
                }
			}
			$ret .= sprintf('<tr><td>%s</td>
					<td><a href="../items/%s">%s</a></td>
					<td>$%s</td>
					<td>%s</td>',
					$w['brand'],
					$w['upc'],$w['description'],
					($w['sale_price']==0?$price:$w['sale_price']),
					($w['sale_price']==0?'&nbsp;':'ON SALE!')
			);
			if ($w['inCart'] == 0 && $empno != -999 && !($w['discounttype'] == 2 && $w['special_price'] == 0) && strpos($w['description'], 'Yoga') == false){
                    $qty = sprintf('<div class="input-group">
                        <span class="input-group-addon">Quantity</span>
                        <input type="number" value="1" id="qty%s" /></div>', $w['upc']);
                    $maxQty = 0;
                    if ($w['scale']) {
                        $min = 0.25;
                        $max = 2;
                        $step = 0.25;
                        if ($w['min'] && $w['max'] && $w['step']) {
                            $min = $w['min'];
                            $max = $w['max'];
                            $step = $w['step'];
                        }
                        $qty = sprintf('<div class="input-group">
                            <span class="input-group-addon">Quantity</span>
                            <select id="qty%s">', $w['upc']);
                        for ($i=$max; $i>=$min; $i-=$step) {
                            $qty .= sprintf('<option %s value="%.2f">%.2f %s</option>',
                                ($i == 1 || ($i == $min && $i > 1) ? 'selected' : ''),
                                $i, $i,
                                ($i == 1 ? 'lb' : 'lbs')
                            );
                        }
                        $qty .= '</select></div>';
                    } elseif ($w['superID'] != 6) {
                        $qty = sprintf('<div class="input-group">
                            <span class="input-group-addon">Quantity</span>
                            <input type="number" value="1" id="qty%s" 
                            min="1" max="6" /></div>', $w['upc']);
                        $maxQty = 6;
                    }
					$ret .= sprintf('<td id="btn%s">
                        %s
						<input type="submit" value="Add to cart" onclick="addItem(\'%s\');" />
						</td></tr>',
						$w['upc'],$qty,$w['upc']);
                    $ret .= sprintf('<input type="hidden" id="maxQty%s" value="%d" />', $w['upc'], $maxQty);
			}
			else if ($empno != -999 && strpos($w['description'], 'Yoga') == false){
					$ret .= sprintf('<td id="btn%s">
						<a href="cart.php">In Cart</a>
						</td></tr>',
						$w['upc']);
			}
			else if ($empno != -999 && strpos($w['description'], 'Yoga') != false){
					$ret .= '<td>
						<i style="color: #EE5D1A; font-weigth: bold">class is first come, first serve</i>
						</td></tr>';
					
			}
			else $ret .= '<td></td></tr>';
		}
		$ret .= '</table>';

		echo $ret;
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new search('Browse Store');
}
