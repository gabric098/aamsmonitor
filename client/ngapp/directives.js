'use strict';

/* Directives */

angular.module('aamsMonitor.directives', [])
    .directive('eventsTableLoaded', function($timeout) {
        return function(scope, element, attrs) {
            if (scope.$last){
                $timeout( function() {
                    $('tbody.rowlink').rowlink();
                },500);
            }
        };
    });