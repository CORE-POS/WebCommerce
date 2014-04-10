var wcItem = angular.module('wcItem', []);

wcItem.factory('itemFactory', function($http) {
    return {
        getItemAsync: function(upc, callback) {
            $http.get('../ajax-callbacks/ajax-get-item.php?upc='+upc)
                .success(callback);
        },
    };
});
