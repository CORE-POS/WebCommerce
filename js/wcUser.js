var wcUser = angular.module('wcUser', []);

wcUser.factory('userFactory', function($http) {
    return { 
        getUserAsync: function(callback) {
            $http.get('../ajax-callbacks/ajax-get-user.php', { cache: true})
                .success(callback);
        },
    };
});
