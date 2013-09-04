'use strict';


// Declare app level module which depends on filters, and services
angular.module('aamsMonitor', ['aamsMonitor.filters', 'aamsMonitor.services', 'aamsMonitor.directives']).
    config(['$routeProvider', function($routeProvider) {
        $routeProvider.when('/live', {templateUrl: 'partials/partial_list.html', controller: AamsLive});
        $routeProvider.when('/qf', {templateUrl: 'partials/partial_list.html', controller: AamsQf});
        $routeProvider.otherwise({redirectTo: '/qf'});
    }]);