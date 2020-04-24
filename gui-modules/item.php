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

class itemPage extends BasicPage {

	function js_content(){
		?>
		function addItem(upc){
			$.ajax({
				url: '../ajax-callbacks/ajax-add-item.php',
				type: 'post',
				data: 'upc='+upc+'&qty='+$('#qty').val(),
				success: function(resp){
					$('#btn'+upc).html('<a href="/gui-modules/cart.php">In Cart</a>');
				}
			});
		}
		<?php
	}

	function main_content(){
		global $IS4C_PATH;
		$upc = $_REQUEST['upc'];
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

		$empno = AuthUtilities::getUID(AuthLogin::checkLogin());
        if ($empno===false) $empno=-999;

		$dbc = Database::pDataConnect();
		$q = $dbc->prepare_statement("SELECT p.upc,p.normal_price,p.special_price,
			p.discounttype,u.description,u.brand,u.long_text,
			1 AS inUse,u.soldOut,u.photo,u.narrow,p.scale,
            p.size, u.sizing,
            s.min, s.max, s.step,
			CASE WHEN o.available IS NULL then 99 ELSE o.available END as available
			FROM products AS p INNER JOIN productUser AS u
			ON p.upc=u.upc LEFT JOIN productOrderLimits AS o
			ON p.upc=o.upc
            LEFT JOIN DeptScaling AS s ON p.department=s.dept_no
            WHERE p.upc=?");
		$r = $dbc->exec_statement($q,array($upc));

		if ($dbc->num_rows($r)==0){
			echo "Item not found";
			return;
		}

		$w = $dbc->fetch_row($r);
		
		echo '<div class="itemBox">';

		echo '<div class="col-sm-6">';
        if (strtoupper($w['brand']) == 'ORGANIC') {
            $w['brand'] = '';
            $w['description'] = 'Organic ' . $w['description'];
        }
		echo '<span class="itemDesc">'.$w['description'].'</span><br />';
        if ($w['brand']) {
            echo '<span class="itemBrand">by '.$w['brand'].'</span>';
        }
        if ($w['size']) {
            $w['size'] = str_replace('FZ', 'fl oz', $w['size']);
            echo '<span class="itemBrand">Package Size: '.$w['size'].'</span>';
        } elseif ($w['sizing']) {
            $w['sizing'] = str_replace('FZ', 'fl oz', $w['sizing']);
            echo '<span class="itemBrand">Package Size: '.$w['sizing'].'</span>';
        }
        if ($w['photo']) {
            $url = 'https://wholefoods.coop/cms/wp-content/uploads/items/' . $w['photo'];
            echo "<img src=\"{$url}\" class=\"itemImg\" />";
        }
		echo '<p />';
		echo nl2br($w['long_text']);
		echo '</div>';

		echo '<div class="col-sm-3">';
		echo '<span class="itemPriceNormal">';
		$price = $w['normal_price'];
        // u.narrow = class is free to access discount owners
        //$access = ($w['narrow'] == 1) ? true : false;
        $description = $w['description'];
        if (strpos(strtolower($description), 'yoga') !== false) {
              $price = 'Free!';
        } elseif ($price == 0) {
              $price = 'Free!<br/><span style="font-size: 14px; font-weight: normal">*Registration is still required</span>';
        } else {
              $price = sprintf('$%.2f',$price);
        }
		$specialPrice = sprintf('$%.2f',$w['special_price']);
		printf('<i>%s</i>',($w['discounttype']==1?$specialPrice:$price));
        if ($w['scale']) {
            echo "/lb";
        }
		echo '</span><br />';
		echo '<span class="itemPriceAddOn">';
		if ($w['discounttype']==1) echo 'On Sale!';
		else if ($w['discounttype']==2)
			printf('Owner price: $%.2f',$w['special_price']);
		echo '</span>';
/*
        if ($access) {
            echo "<br /><br />";
            //echo "<div style=\"font-size: 14px; font-weight: normal;\"><i>Access Discount Owners: FREE</i></div>";
            echo "<div style=\"font-size: 14px; font-weight: normal;\"><i>FREE to Access Discount members</i></div>";
            echo "<div style=\"font-size: 14px; font-weight: normal;\"><i>* Please call or visit either location to 
                register for this class using access discount</i></div>";
        }
*/
		echo '<br /><br />';
		if ($w['inUse'] == 0 || $w['available'] <= 0 || $w['soldOut'] == 1){
			echo 'This product is expired, out of stock, or otherwise
                no longer available to order online. Class reservations or
                waiting lists may still be available. Call 
                218-728-0884 and press 1 to speak to customer service
                for more information.';
		}
		else if ($empno == -999){
			echo '<a href="' . $IS4C_PATH . 'gui-modules/loginPage.php">Login</a> or ';
			echo '<a href="' . $IS4C_PATH . 'gui-modules/createAccount.php">Create an Account</a> ';
			echo 'to add items to your cart.';
		} else if ((!AuthUtilities::getOwner(AuthLogin::checkLogin()) || AuthUtilities::getOwner(AuthLogin::checkLogin()) == 9) && $w['discounttype'] == 2 && $w['special_price'] == 0) {
            echo 'This item is only available to owners. ';
            echo '<a href="manageAccount.php">Verify your Account status</a>';
		} else {
            if ($w['scale']) {
                $min = 0.25;
                $max = 2;
                $step = 0.25;
                if ($w['min'] && $w['max'] && $w['step']) {
                    $min = $w['min'];
                    $max = $w['max'];
                    $step = $w['step'];
                }
                echo 'Select amount:
                    <select id="qty">';
                for ($i=$max; $i>=$min; $i-=$step) {
                    printf('<option %s value="%.2f">%.2f %s</option>',
                        ($i == 1 || ($i == $min && $i > 1) ? 'selected' : ''),
                        $i, $i,
                        ($i == 1 ? 'lb' : 'lbs')
                    );
                }
                echo '</select>';
            } else {
                echo '<input type="hidden" id="qty" value="1" />';
            }
			$chkP = $dbc->prepare_statement("SELECT upc FROM localtemptrans WHERE
				upc=? AND emp_no=?");
			$chkR = $dbc->exec_statement($chkP,array($w['upc'],$empno));
			if ($dbc->num_rows($chkR) == 0){
				printf('<span id="btn%s">
					<input type="submit" value="Add to cart" onclick="addItem(\'%s\');" />
					</span>',
					$w['upc'],$w['upc']);
			}
			else {
				printf('<span id="btn%s">
					<a href="cart.php">In Cart</a>
					</span>',$w['upc']);
			}
		}
		echo '</div>';

		echo  '</div>'; // end itemBox

		echo '<div class="itemCart">';

		echo '</div>';
	}

	function preprocess(){
		global $IS4C_PATH;
		if (!isset($_REQUEST['upc'])){
			header("Location: {$IS4C_PATH}gui-modules/storefront.php");
			return False;
		}
		return True;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new itemPage();
}

?>
