var wcCart = angular.module('wcCart', []);

wcCart.factory('cartFactory', function($http) {
    return {
        getCartAsync: function(callback) {
            $http.get('../ajax-callbacks/ajax-get-cart.php')
                .success(callback);
        },
    };
});
