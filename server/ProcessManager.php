<?php
require_once 'AamsProcessor.php';
/**
 * Created by JetBrains PhpStorm.
 * User: gabriele
 * Date: 16/08/13
 * Time: 11:13
 * To change this template use File | Settings | File Templates.
 */

class ProcessManager {
    private $mode;
    private $log;
    private $mysqli;

    const STATUS_IDLE = 0;
    const STATUS_RUNNING = 1;
    const STATUS_DBUPDATE = 2;

    function __construct($mode, $mysqlConn) {
        $this->mode = $mode;
        $this->log = new MyLogPHP('./log/log_' . $this->mode . '_'. date('Ymd') . '.csv');
        $this->mysqli = $mysqlConn;
        $this->mysqli->setLogger($this->log);
    }

    public function process()   {
            // set process flag to running
            if ($this->setBeginProcess() == (string)$this::STATUS_RUNNING ) {
                try {
                    $this->run();
                }catch (Exception $e) {
                    $this->log->error("AAMS info crawler end with errors @ " . date('H:i:s'));
                }

            // set process flag to idle
            $this->setEndProcess();
        } else {
            $this->log->error("Process for mode " . $this->mode . " already runnning.");
        }
    }

    /**
     * This function calls the beginProcess stored procedure.
     * It returns 1 if the process has been correctly opened or 0
     * if the process is still running
     */
    private function setBeginProcess() {
        return ($this->mysqli->beginProcess($this->mode));
    }

    /**
     * This function call setDbUpdateProcessStatus
     * @return int
     */
    private function setDbUpdateProcess() {
        return ($this->mysqli->setDbUpdateProcessStatus($this->mode));
    }

    /**
     * This function call endProcess
     */
    private function setEndProcess() {
        $this->mysqli->endProcess($this->mode);
    }

    private function run() {
        $processor = new AamsProcessor($this->mode, $this->log, $this->mysqli);
        $processor->run();
        if ($this->setDbUpdateProcess() == '1') {
            $processor->dbProcess();
        } else {
            $this->log->error("Unable to update process status to 2");
            throw new Exception("Unable to update process status to 2");
        }
    }
}