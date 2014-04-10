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

if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if (!class_exists('BasicPage')) {
    include_once(dirname(__FILE__) . '/../gui-class-lib/BasicPage.php');
}

class ItemPage extends BasicPage 
{

	public function js_content()
    {
		?>
        var myApp = angular.module('myApp', ['ngSanitize']);
        myApp.factory('userFactory', function($http) {
            return { 
                getUserAsync: function(callback) {
                    $http.get('../ajax-callbacks/ajax-get-user.php', { cache: true})
                        .success(callback);
                },
            };
        });
        myApp.factory('itemFactory', function($http) {
            return {
                getItemAsync: function(upc, callback) {
                    $http.get('../ajax-callbacks/ajax-get-item.php?upc='+upc)
                        .success(callback);
                },
            };
        });
        myApp.controller('itemController', function ($scope, $http, userFactory, itemFactory) {
            $scope.ready = false;
            $scope.buyable = false;

            userFactory.getUserAsync(function(result) {
                $scope.user = result;
            });

            $scope.$watch('upc', function() {
                itemFactory.getItemAsync($scope.upc, function(result) {
                    $scope.item = result;
                    $scope.ready = true;

                    if ($scope.item.inUse == 1 && $scope.item.soldOut == 0) {
                        $scope.buyable = true;
                    }
                });
            });

            $scope.adding = false;
            $scope.addItem = function() {
                $scope.adding = true;
                $http.get('../ajax-callbacks/ajax-add-item.php?upc='+$scope.upc)
                    .success(function(result) {
                        if (result == 1) {
                            $scope.item.inCart = 1;
                        }
                        $scope.adding = false;
                    })
                    .error(function() {
                        $scope.adding = false;
                    });
            };
        });
		<?php
	}

	public function main_content()
    {
		global $IS4C_PATH;
		$upc = $_REQUEST['upc'];
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

        ?>
        <div class="itemBox" 
            ng-app="myApp"
            ng-init="upc='<?php echo $upc; ?>';"
            ng-controller="itemController"
        >
          <div ng-show="ready && item.found">
            <div class="itemMain">
                <span class="itemDesc"> {{ item.description }} </span><br />
                <span class="itemBrand"> {{ item.brand }} </span>
                <p ng-bind-html="item.long_text"></p>
            </div>

            <div class="itemPrice">
                <span class="itemPriceNormal">
                    <span ng-show="item.discounttype == 1"> {{ item.special_price | currency }}</span>
                    <span ng-show="item.discounttype != 1"> {{ item.normal_price | currency }}</span>
		        </span><br />
                <span class="itemPriceAddOn">
                    <span ng-show="item.discounttype == 1"> On Sale!</span>
                    <span ng-show="item.discounttype == 2"> Owner Price: {{ item.special_price | currency }}</span>
		        </span>
                <br /><br />
                <span class="itemGone" ng-show="!buyable">
			        This product is expired, out of stock, or otherwise
                    no longer available to order
                </span>
                <span ng-show="buyable && !user.loggedIn">
                    <a href="loginPage.php">Login</a> or 
                    <a href="createAccount.php">Create an Account</a> 
                    to add items to your cart.
                </span>
				<span ng-show="buyable && user.loggedIn && !item.inCart">
                    <button ng-click="addItem()" ng-show="!adding">Add to Cart</button>
                </span>
                <span ng-show="buyable && user.loggedIn && item.inCart">
					<a href="cart.php">In Cart</a>
                </span>
            </div>
          </div>
          <div ng-show="ready && !item.found">
            Item not found {{ item.found }}
          </div>
        </div>

		<div class="itemCart">

		</div>
        <?php
	}

	public function preprocess()
    {
		global $IS4C_PATH;
		if (!isset($_REQUEST['upc'])){
			header("Location: {$IS4C_PATH}gui-modules/storefront.php");
			return False;
		}
		return True;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new ItemPage();
}

?>
