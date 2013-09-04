'use strict';

/* Controllers */

function AamsLive($scope, $timeout, Event) {
    $scope.done = true;
    $scope.mode = 'live';
    $scope.hasError = false;
    refresh();

    $scope.markAsRead = function($event, id) {
        var opResult = Event.markAsReadById({id:id},
        function success() {
            if (opResult.success) {
                refresh();
            } else {
                showError(opResult.msg);
            }
        },
        function err() {
            showError("Ehi! A quanto pare c'e' qualche problema con il server, riprova tra poco");
        });
    }

    $scope.markAllRead = function($event, id) {
        var opResult = Event.markLiveAsRead({},
        function success() {
            if (opResult.success) {
                refresh();
            } else {
                showError(opResult.msg);
            }
        },
        function err() {
            showError("Ehi! A quanto pare c'e' qualche problema con il server, riprova tra poco");
        });

    }

    $scope.refresh = function($event, id) {
        refresh();
    }

    function refresh() {
        $scope.done = false;
        $scope.errorMsg = '';

        var lastUpdateRes = Event.getLastLiveUpdate({},
        function success() {
            if (lastUpdateRes.success) {
                $scope.lastUpdate = lastUpdateRes.lastUpdate;
            } else {
                showError(lastUpdateRes.msg);
            }
        },
        function err() {
            showError("Ehi! A quanto pare c'e' qualche problema con il server, riprova tra poco");
        });

        var liveEvents = Event.queryLive({},
        function success() {
            $scope.events = liveEvents;
            done();
        },
        function err() {
            showError("Ehi! Non riesco ad ottenere la lista di eventi dal server, riprova tra poco");
            done();
        });
    }

    function showError(msg) {
        $scope.hasError = true;
        $scope.errorMsg = msg;
    }

    function done() {
        $scope.done = true;

    }
}

function AamsQf($scope, Event) {
    $scope.done = true;
    $scope.mode = 'qf';
    $scope.hasError = false;
    refresh();

    $scope.markAsRead = function($event, id) {
        var opResult = Event.markAsReadById({id:id},
        function success() {
            if (opResult.success) {
                refresh();
            } else {
                showError(opResult.msg);
            }
        },
        function err() {
            showError("Ehi! A quanto pare c'e' qualche problema con il server, riprova tra poco");
        });
    }

    $scope.markAllRead = function($event, id) {
        var opResult = Event.markQfAsRead({},
        function success() {
            if (opResult.success) {
                refresh();
            } else {
                showError(opResult.msg);
            }
        },
        function err() {
            showError("Ehi! A quanto pare c'e' qualche problema con il server, riprova tra poco");
        });
    }

    $scope.refresh = function($event, id) {
        refresh();
    }

    function refresh() {
        // disable refresh button
        $scope.done = false;
        // reset error message
        $scope.errorMsg = '';

        var lastUpdateRes = Event.getLastQfUpdate({},
        function success() {
            if (lastUpdateRes.success) {
                $scope.lastUpdate = lastUpdateRes.lastUpdate;
            } else {
                showError(lastUpdateRes.msg);
            }
        },
        function err() {
            showError("Ehi! A quanto pare c'e' qualche problema con il server, riprova tra poco");
        });

        var qfEvents = Event.queryQf({},
        function success() {
            $scope.events = qfEvents;
            done();
        },
        function err() {
            showError("Ehi! Non riesco ad ottenere la lista di eventi dal server, riprova tra poco");
            done();
        });
    }

    function showError(msg) {
        $scope.hasError = true;
        $scope.errorMsg = msg;
    }

    function done() {
        $scope.done = true;

    }
}