<?php

// This file is part of AAMS Monitor
// Copyright (C) 2013  Gabriele Antonini
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

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