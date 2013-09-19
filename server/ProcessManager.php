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

require_once 'AamsProcessor.php';

/**
 * Class ProcessManager
 *
 * Controls the crawling operations at a high level, it changes the process status in order to prevent
 * the crawler runs more then once at a given time. It also manages the finish timestamp of the
 * crawling operations.
 *
 * @category    AAMS Monitor
 * @copyright   Copyright (C) 2013  Gabriele Antonini (gabriele.antonini@gmail.com)
 * @license     GNU General Public License
 */
class ProcessManager
{
    /* @var int */
    private $mode;
    /* @var MyLogPHP */
    private $log;
    /* @var DBProvider */
    private $mysqli;

    const STATUS_IDLE = 0;
    const STATUS_RUNNING = 1;

    /**
     * Initializes ProcessManager object's proprieties and create a logger object.
     *
     * @param $mode int The crawling mode
     * @param $mysqlConn DBProvider The db connection object
     */
    function __construct($mode, $mysqlConn)
    {
        $this->mode = $mode;
        $this->log = new MyLogPHP('./log/log_' . $this->mode . '_'. date('Ymd') . '.csv');
        $this->mysqli = $mysqlConn;
        $this->mysqli->setLogger($this->log);
    }

    /**
     * If no other processor is running, runs it.
     */
    public function process()
    {
        // set process flag to running
        if ($this->setBeginProcess() == (string)ProcessManager::STATUS_RUNNING ) {
            try {
                $this->run();
            }catch (Exception $e) {
                $this->log->error("AAMS info crawler end with errors @ " . date('H:i:s'));
            }

            // set process flag to idle
            $this->setEndProcess();
        } else {
            $this->log->error("Process for mode " . $this->mode . " already running.");
        }
    }

    /**
     * Invokes the the beginProcess method on the database connection object.
     * It returns 1 if the process has been correctly opened or 0
     * if the process is still running
     *
     * @return string '0' if the processor is running '1' if the processor is idle
     */
    private function setBeginProcess() {
        return ($this->mysqli->beginProcess($this->mode));
    }

    /**
     * Invokes the the beginProcess method on the database connection object.
     */
    private function setEndProcess() {
        $this->mysqli->endProcess($this->mode);
    }

    /**
     * Instantiates the requested processor and execute the run method on it
     */
    private function run() {
        $processor = new AamsProcessor($this->mode, $this->log, $this->mysqli);
        $processor->run();
    }
}