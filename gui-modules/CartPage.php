<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class CartPage extends BasicPage 
{

	private $notices;

	public function js_content()
    {
		?>
        var myApp = angular.module('myApp', ['ngSanitize', 'wcCart']);
        myApp.controller('cartController', function ($scope, cartFactory) {
            $scope.cart = [];
            $scope.cart = [{"upc":"0000099041514","brand":"test-brand","description":"test item","scale":"0","quantity":"1","unitPrice":"20","total":"20","saleMsg":"Owner Special","checked":false}];
            cartFactory.getCartAsync(function(result) {
                $scope.cart = result.cart;
                $scope.subtotal = result.subtotal;
                $scope.tax = result.tax;
                $scope.total = result.total;
            });

            $scope.updateQty = function() {
                console.log($scope.cart);
            };
        });
		<?php
	}

	public function main_content()
    {
		global $IS4C_PATH,$IS4C_LOCAL;

		if (!PayPal::PAYPAL_LIVE){
			echo '<h2>This store is in test mode; orders will not be processed</h2>';
		}

		echo '<blockquote><em>'.$this->notices.'</em></blockquote>';
		//echo '<input type="image" name="cobtn" height="30px;" style="vertical-align:bottom;" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" />';
        ?>

        <div ng-app="myApp" ng-controller="cartController">
		<table id="carttable" cellspacing="0" cellpadding="4" border="1">
            <tr>
                <th>&nbsp;</th><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th>&nbsp;</th>
            </tr>
            <tr ng-repeat="entry in cart">
                <td><input type="checkbox" ng-model="cart[$index].checked" /></td>
                <td> {{ entry.brand }} {{ entry.description }}</td> 
                <td><input type="text" size=4 ng-model="cart[$index].quantity" /></td>
                <td> {{ entry.unitPrice | currency }} </td>
                <td> {{ entry.total | currency }} </td>
                <td> {{ entry.saleMsg }} </td>
            </tr>
            <tr>
                <td colspan="4" align="right">Subtotal</td>
                <td>{{ subtotal  | currency }} </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td colspan="4" align="right">Taxes</td>
                <td>{{ tax  | currency }} </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td colspan="4" align="right">Total</td>
                <td>{{ total  | currency }} </td>
                <td>&nbsp;</td>
            </tr>
		    <tr>
                <td colspan="6" valign="top">
		        <input type="submit" value="Delete Selected Items" />
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		        <input type="submit" ng-click="updateQty();" value="Update Quantities" />
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </td>
            </tr>
        </table>
        </div>
        <?php
	}

	function preprocess(){
		global $IS4C_LOCAL;
		$db = Database::tDataConnect();
		$empno = AuthUtilities::getUID(AuthLogin::checkLogin());

		if (isset($_REQUEST['qtybtn'])){
			for($i=0; $i<count($_REQUEST['qtys']);$i++){
				if (!is_numeric($_REQUEST['qtys'][$i])) continue;
				if ($_REQUEST['qtys'][$i] == $_REQUEST['orig'][$i]) continue;

				$upc = $_REQUEST['upcs'][$i];
				$qty = round($_REQUEST['qtys'][$i]);
				if ($_REQUEST['scales'][$i] == 1)
					$qty = number_format(round($_REQUEST['qtys'][$i]*4)/4,2);
				if ($qty == $_REQUEST['orig'][$i]) continue;

				$availP = $db->prepare_statement("SELECT available FROM productOrderLimits WHERE upc=?");
				$availR = $db->exec_statement($availP,array($upc));
				$limit = 999;
				if ($db->num_rows($availR) > 0)
					$limit = array_pop($db->fetch_row($availR));
				if ($qty > $limit && $qty > 0){
					$qty = $limit;
					if ($qty <= 0) $qty=1;
					$this->notices = "Due to limited availability, requested quantity
						cannot be provided";
				}

				$q1 = $db->prepare_statement("DELETE FROM localtemptrans WHERE
					upc=? AND emp_no=?");
				$db->exec_statement($q1,array($upc,$empno));
				if ($qty > 0) {
					TransRecord::addUPC($upc,$qty);
                }
			}
		}
		if (isset($_REQUEST['delbtn'])){
			if (isset($_REQUEST['selections'])){
				foreach($_REQUEST['selections'] as $upc){
					$q1 = $db->prepare_statement("DELETE FROM localtemptrans WHERE
						upc=? AND emp_no=?");
					$db->exec_statement($q1,array($upc,$empno));
				}
			}
		}
		if (isset($_REQUEST['cobtn_x'])){
			$dbc = Database::tDataConnect();
			$email = AuthLogin::checkLogin();
			$empno = AuthUtilities::getUID($email);
			$subP = $dbc->prepare_statement("SELECT sum(total) FROM cart WHERE emp_no=?");
			$sub = $dbc->exec_statement($subP,array($empno));
			$sub = array_pop($dbc->fetch_row($sub));
			$taxP = $dbc->prepare_statement("SELECT sum(total) FROM taxTTL WHERE emp_no=?");
			$tax = $dbc->exec_statement($taxP,array($empno));
			$tax = array_pop($dbc->fetch_row($tax));

            $ttl = round($sub + $tax, 2);
            if (floor($ttl*100) == 0) {
                header('Location: confirm.php');
                return false;
            } else {
                return PayPal::SetExpressCheckout(round($sub+$tax,2),
                    round($tax,2),$email);
            }
		}

        $this->addScript('../js/wcCart.js');

		return True;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new CartPage();
}

?>
