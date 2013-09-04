<?php
    require_once("config.php");
    require_once("WebServices.php");
    require_once("DbProvider.php");
    header('Content-type: application/json');
    ini_set('display_errors',1);
    ini_set('display_startup_errors',1);
    error_reporting(-1);
    $mysqlConn = new DbProvider($cfg_db_host, $cfg_db_user, $cfg_db_pwd, $cfg_db_name);
    $webService = new WebServices($mysqlConn);
    $_SERVER['REQUEST_URI_PATH'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', $_SERVER['REQUEST_URI_PATH']);
    $op = $segments[3];
    $response = '';
    $seconds_to_cache = 0;
    switch ($op) {
        case 'liveevents':
            $response = $webService->getLiveEventsByStatus();
            break;
        case 'qfevents':
            $response = $webService->getQfEventsByStatus();
            break;
        case 'setasread':
            $pkId = $_GET["id"];
            $response = $webService->setEventAsRead($pkId);
            break;
        case 'setliveallasread':
            $response = $webService->setAllEventAsRead(Constants::MODE_LIVE);
            break;
        case 'setqfallasread':
            $response = $webService->setAllEventAsRead(Constants::MODE_QUOTA_FISSA);
            break;
        case 'getlivelastupdate':
            $seconds_to_cache = 0;
            $response = $webService->getLastUpdateTime(Constants::MODE_LIVE);
            break;
        case 'getqflastupdate':
            $response = $webService->getLastUpdateTime(Constants::MODE_QUOTA_FISSA);
            $seconds_to_cache = 0;
            break;
    }
    if ($seconds_to_cache > 0) {
        $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
        header("Expires: $ts");
        header("Pragma: cache");
        header("Cache-Control: max-age=$seconds_to_cache");
    }
    echo($response);
