'use strict';

/* Services */


// Demonstrate how to register services
// In this case it is a simple value service.
angular.module('aamsMonitor.services', ['ngResource']).
    factory('Event', function ($resource) {
        return $resource('../server/index.php/:action', {}, {
            queryLive: {method: 'GET', params: { action:"liveevents" }, isArray: true},
            queryQf: {method: 'GET', params: { action:"qfevents" }, isArray: true},
            markAsReadById: {method: 'GET', params: { action:"setasread" },  isArray: false},
            markLiveAsRead: {method: 'GET', params: { action:"setliveallasread" }, isArray: false},
            markQfAsRead: {method: 'GET', params: { action:"setqfallasread" }, isArray: false},
            //getLastQfUpdate error managed
            getLastQfUpdate: {method: 'GET', params: { action:"getqflastupdate" }, isArray: false},
            //getLastLiveUpdate error managed
            getLastLiveUpdate: {method: 'GET', params: { action:"getlivelastupdate" }, isArray: false}
        });
    });