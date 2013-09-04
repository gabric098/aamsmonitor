<?php
require_once "lib/simple_html_dom.php";
require_once "lib/logger.class.php";
require_once "AamsEvent.php";
require_once "DbProvider.php";
require_once "ProcessManager.php";
require_once "Constants.php";
require_once "config.php";

if (php_sapi_name() != 'cgi' && php_sapi_name() != 'cli'){
    echo ("CLI only! " . php_sapi_name());
    exit();
}

$arg1 = '';
if (array_key_exists(1, $argv)) {
    $arg1 = $argv[1];
}

$mysqlConn = new DbProvider($cfg_db_host, $cfg_db_user, $cfg_db_pwd, $cfg_db_name);

if (isset($arg1) && $arg1 == 'live') {
    $pm = new ProcessManager(Constants::MODE_LIVE, $mysqlConn);
    $pm->process();
}elseif (isset($arg1) && $arg1 == 'qf') {
    $pm = new ProcessManager(Constants::MODE_QUOTA_FISSA, $mysqlConn);
    $pm->process();
} else {
    $pm = new ProcessManager(Constants::MODE_LIVE, $mysqlConn);
    $pm2 = new ProcessManager(Constants::MODE_QUOTA_FISSA, $mysqlConn);
    $pm->process();
    $pm2->process();
}
exit();