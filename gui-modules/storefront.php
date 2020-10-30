<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

class storefront extends BasicPage {

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

	function main_content(){
		global $IS4C_PATH;
		echo '<div id="sidebar">';
		echo $this->sidebar();
		echo '</div>';
		echo '<div id="browsearea">';
		echo $this->itemlist();
		echo '</div>';
		echo '<div style="clear:both;"></div>';
	}

	function itemlist(){
		global $IS4C_LOCAL;
        unset($_SESSION['emp_no']);
		$super = isset($_REQUEST['sup'])?$_REQUEST['sup']:-1;
		$sub = isset($_REQUEST['sub'])?$_REQUEST['sub']:-1;
		$d = isset($_REQUEST['d'])?$_REQUEST['d']:-1;
		$brand = isset($_REQUEST['bid'])?base64_decode($_REQUEST['bid']):-1;

		$limit = 50;
		$page = isset($_REQUEST['pg'])?((int)$_REQUEST['pg']):0;
		$offset = $page*$limit;

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
            ds.min, ds.max, ds.step,
            p.special_price, p.discounttype, p.scale, m.superID
			FROM products AS p INNER JOIN productUser
			AS u ON p.upc=u.upc LEFT JOIN ".$IS4C_LOCAL->get("tDatabase").".localtemptrans
			AS l ON p.upc=l.upc AND l.emp_no=?
            LEFT JOIN superdepts AS m ON p.department=m.dept_ID
			LEFT JOIN productOrderLimits AS o ON p.upc=o.upc AND o.storeID=" . (isset($_SESSION['storeID']) ? (int)$_SESSION['storeID'] : 0) . "
            LEFT JOIN DeptScaling AS ds ON p.department=ds.dept_no ";
		$args = array($empno);
		if ($super != -1)
			$q .= "INNER JOIN superdepts AS s ON p.department=s.dept_ID ";
		if ($sub != -1)
			$q .= "INNER JOIN subdepts AS b ON p.department=b.dept_ID ";
		$q .= "WHERE u.enableOnline=1 AND u.soldOut=0 AND (o.available IS NULL or o.available > 0) ";
		if ($super != -1){
			$q .= "AND s.superID=? ";
			$args[] = $super;
		}
		if ($d != -1){
            if ($d == 83) {
                $q .= " AND p.upc IN ('0021822000000', '0021821000000', '0021820000000', '0021819000000') ";
            } elseif ($d == 82) {
                $q .= " AND p.upc NOT IN ('0021822000000', '0021821000000', '0021820000000', '0021819000000') AND p.department=? ";
                $args[] = $d;
            } else {
                $q .= "AND p.department=? ";
                $args[] = $d;
            }
		}
		if ($sub != -1){
			$q .= "AND b.subdept_no=? ";
			$args[] = $sub;
		}
		if ($brand != -1){
			$q .= "AND u.brand=? ";
			$args[] = $brand;
		}
        // this is a TEST wfc-u class, to test noauto
        $q .= "AND p.upc <> 99010118 ";
        //if ($empno!=-999) {
        //    $q .= "AND p.description NOT LIKE '%yoga%'";
        //}
        $q .= "ORDER BY
            CASE WHEN u.brand IS NULL OR u.brand='' THEN p.brand ELSE u.brand END,
            CASE WHEN u.description IS NULL OR u.description='' THEN p.description ELSE u.description END";
        if ($super == -1 || $d == -1 || $brand == -1) {
            $q .= " LIMIT 100";
        }

		$p = $dbc->prepare_statement($q);
		$r = $dbc->exec_statement($p, $args);

        $ret = '<b>Pickup Location</b>: <select name="store" onchange="window.location=\'https://store.wholefoods.coop/gui-modules/setStore.php?id=\' + this.value;">';
        $opts = array('Choose a store',
            'Hillside (610 E 4th St, Duluth, MN 55805)',
            'Denfeld (4426 Grand Ave, Duluth, MN 55807)',
        );
        foreach ($opts as $id => $o) {
            $ret .= sprintf('<option %s value=%d>%s</option>',
                    ($id == $_SESSION['storeID'] ? 'selected' : ''), $id, $o);
        }
        $ret .= '</select>';
        if (!$_SESSION['storeID']) {
            $ret .= '<p>Please choose a location first<p>';
            return $ret;
        }

		$ret .= '<table class="table" cellspacing="4" cellpadding="4" id="browsetable">';
		$ret .= '<tr><th>Brand</th><th>Product</th><th>Price</th><th>&nbsp;</th></tr>';
		while($w = $dbc->fetch_row($r)){
			$price = $w['normal_price'];
			if ($price == 0) {
				$price = 'Free!';
			} else {
				$price = sprintf('%.2f',$price);
                if ($w['sale_price']) {
                    $w['sale_price'] = sprintf('%.2f',$price);
                }
			}
            if ($w['brand'] == 'ORGANIC') {
                $w['brand'] = 'Organic';
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
                        $min = 1;
                        $max = 10;
                        $step = 1;
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
                            min="1" max="10" /></div>', $w['upc']);
                        $maxQty = 10;
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
		return $ret;
	}

	function sidebar(){
		$super = isset($_REQUEST['sup'])?$_REQUEST['sup']:-1;
		$sub = isset($_REQUEST['sub'])?$_REQUEST['sub']:-1;
		$d = isset($_REQUEST['d'])?$_REQUEST['d']:-1;

		$ret = '<ul id="superList">';
		$dbc = Database::pDataConnect();
		$r = $dbc->query("SELECT s.superID,s.super_name FROM
			superDeptNames AS s
            INNER JOIN superdepts AS d ON s.superID=d.superID
            INNER JOIN products AS p ON p.department=d.dept_ID
            GROUP BY s.superID, s.super_name
            ORDER BY super_name");
		$sids = array();
		while($w = $dbc->fetch_row($r)){
			$sids[$w['superID']] = $w['super_name'];
		}
		if (count($sids)==1) {
            $keys = array_keys($sids);
            $super = array_pop($keys);
        }

		if ($sub != -1 && $d != -1 && $super != -1){
			// browsing subdept

		}
		else if ($d != -1 && $super != -1){
			// browsing dept
			$q = $dbc->prepare_statement("SELECT subdept_no,subdept_name FROM subdepts
				WHERE dept_ID=?");
			$r = $dbc->exec_statement($q, array($d));
			$subs = True;
			if ($dbc->num_rows($r) == 0){
				// no subdepts; skip straight to brands
				$subs = False;
				$q = $dbc->prepare_statement("SELECT u.brand FROM products AS p
					INNER JOIN productUser AS u ON p.upc=u.upc
					WHERE p.department=? AND u.brand <> ''
                    AND u.enableOnline=1
					AND u.brand IS NOT NULL GROUP BY u.brand
                    ORDER BY u.brand");
				$r = $dbc->exec_statement($q, array($d));
			}

			foreach($sids as $id=>$name){
				$ret .= sprintf('<li><a href="%s?sup=%d">%s</a>',
					$_SERVER['PHP_SELF'],$id,$name);
				if ($id == $super){
					$ret .= '<ul id="deptlist">';
					$dP = $dbc->prepare_statement("SELECT 
                            CASE WHEN p.upc in ('0021822000000', '0021821000000', '0021820000000', '0021819000000') THEN 83 ELSE dept_no END AS dept_no,
                            CASE WHEN p.upc in ('0021822000000', '0021821000000', '0021820000000', '0021819000000') THEN 'Thanksgiving Meals' ELSE dept_name END AS dept_name
                        FROM departments
						as d INNER JOIN superdepts as s ON d.dept_no=s.dept_ID
                        INNER JOIN products AS p ON p.department=d.dept_no
                        INNER JOIN productUser AS u ON p.upc=u.upc
						WHERE s.superID=?
                            AND u.enableOnline=1
                        GROUP BY
                            CASE WHEN p.upc in ('0021822000000', '0021821000000', '0021820000000', '0021819000000') THEN 83 ELSE dept_no END,
                            CASE WHEN p.upc in ('0021822000000', '0021821000000', '0021820000000', '0021819000000') THEN 'Thanksgiving Meals' ELSE dept_name END
                        ORDER BY dept_name");
					$dR = $dbc->exec_statement($dP, array($super));
					while($w = $dbc->fetch_row($dR)){
						$ret .= sprintf('<li><a href="%s?sup=%d&d=%d">%s</a>',
							$_SERVER['PHP_SELF'],$id,$w['dept_no'],$w['dept_name']);
						if ($w['dept_no'] == $d){
							$ret .= '<ul id="sidebar3">';
							while($w = $dbc->fetch_row($r)){
								$ret .= sprintf('<li><a href="%s?sup=%d&d=%d',
									$_SERVER['PHP_SELF'],$id,$d);
								if ($subs){
									$ret .= sprintf('&sub=%d">%s</a></li>',
										$w['subdept_no'],$w['subdept_name']);
								}
								else {
									$ret .= sprintf('&bid=%s">%s</a></li>',
										base64_encode($w['brand']),
										$w['brand']);
								}
							}
							$ret .= '</ul>';
						}
						$ret .= '</li>';
					}
					$ret .= '</ul>';
				}
			}
			
		}
		else if ($super != -1){
			// browsing super
			foreach($sids as $id=>$name){
				$ret .= sprintf('<li><a href="%s?sup=%d">%s</a>',
					$_SERVER['PHP_SELF'],$id,$name);
				if ($id == $super){
					$ret .= '<ul id="deptlist">';
					$p = $dbc->prepare_statement("SELECT
                            CASE WHEN p.upc in ('0021822000000', '0021821000000', '0021820000000', '0021819000000') THEN 83 ELSE dept_no END AS dept_no,
                            CASE WHEN p.upc in ('0021822000000', '0021821000000', '0021820000000', '0021819000000') THEN 'Thanksgiving Meals' ELSE dept_name END AS dept_name
                        FROM departments
						as d INNER JOIN superdepts as s ON d.dept_no=s.dept_ID
                        INNER JOIN products AS p ON p.department=d.dept_no
                        INNER JOIN productUser AS u ON p.upc=u.upc
						WHERE s.superID=?
                            AND u.enableOnline=1
                        GROUP BY
                            CASE WHEN p.upc in ('0021822000000', '0021821000000', '0021820000000', '0021819000000') THEN 83 ELSE dept_no END,
                            CASE WHEN p.upc in ('0021822000000', '0021821000000', '0021820000000', '0021819000000') THEN 'Thanksgiving Meals' ELSE dept_name END
                        ORDER BY dept_name");
					$r = $dbc->exec_statement($p, array($super));
					while($w = $dbc->fetch_row($r)){
						$ret .= sprintf('<li><a href="%s?sup=%d&d=%d">%s</a></li>',
							$_SERVER['PHP_SELF'],$id,$w['dept_no'],$w['dept_name']);
					}
					$ret .= '</ul>';
				}
				$ret .= '</li>';
			}
		}
		else {
			// top level browsing
			foreach($sids as $id=>$name){
				$ret .= sprintf('<li><a href="%s?sup=%d">%s</a></li>',
					$_SERVER['PHP_SELF'],$id,$name);
			}
		}

		$ret .= '</ul>';

		return $ret;
	}

	function preprocess(){
		global $IS4C_PATH;
		if (isset($_REQUEST['email'])){
			if (!AuthUtilities::isEmail($_REQUEST['email'])){
				echo '<div class="errorMsg">';
				echo 'Not a valid e-mail address: '.$_REQUEST['email'];
				echo '</div>';
				return True;
			}
			else if (!login($_REQUEST['email'],$_REQUEST['passwd'])){
				echo '<div class="errorMsg">';
				echo 'Incorrect e-mail address or password';
				echo '</div>';
				return True;
			}
			else {
				header("Location: {$IS4C_PATH}gui-modules/storefront.php");
				return False;
			}
		}
		return True;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new storefront('Browse Store');
}

?>
