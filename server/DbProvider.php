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

/**
 * Class DbProvider
 *
 * Interface to the database. It exposes all the relevant methods for retrieving, inserting or updating
 * data within the database. It uses mysqli connection object
 *
 * @category    AAMS Monitor
 * @copyright   Copyright (C) 2013  Gabriele Antonini (gabriele.antonini@gmail.com)
 * @license     GNU General Public License
 */
class DbProvider
{
    /* @var string */
    private $db_host;
    /* @var string */
    private $db_user;
    /* @var string */
    private $db_pwd;
    /* @var string */
    private $db_name;
    /* @var MyLogPHP */
    private $log;
    /* @var mysqli */
    private $mysqli; // the connection

    const AAMS_EVENTS_TABLE = 'aams_events';
    const AAMS_PROCESS_INFO_TABLE = 'process_info';
    const STATUS_NORMAL = 0;
    const STATUS_CHANGED = 1;
    const STATUS_NEW = 2;

    /**
     * Initializes the database connection properties.
     *
     * @param $db_host string The database hostname or address
     * @param $db_user string The database user name
     * @param $db_pwd string The database password
     * @param $db_name string The database name
     */
    function __construct($db_host, $db_user, $db_pwd, $db_name)
    {
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_pwd = $db_pwd;
        $this->db_name = $db_name;
    }

    /**
     * Assigns a logger to the class
     *
     * @param $log MyLogPHP The logger object
     */
    public function setLogger($log)
    {
        $this->log = $log;
    }

    /**
     * Instantiates the mysqli object and tries to open the database connection
     */
    private function openConnection()
    {
        $this->mysqli = new mysqli($this->db_host, $this->db_user, $this->db_pwd, $this->db_name);
        if ($this->mysqli->connect_errno) {
            $this->log->error("Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);
        }
    }

    /**
     * Closes the mysqli connection
     */
    private function closeConnection()
    {
        $this->mysqli->close();
    }

    /**
     * Updates a single event entry's status on the database
     *
     * @param $pkId int The event primary key
     * @param $status int The event new status
     * @return bool false if update operation fails, true otherwise
     */
    public function updateEventStatus($pkId, $status)
    {
        $outcome = true;
        $this->openConnection();
        try {
            if (!($updateStmt = $this->mysqli->prepare("UPDATE " . DbProvider::AAMS_EVENTS_TABLE . " SET status = ? WHERE id = ?"))){
                $this->log->error("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
            }
            $updateStmt->bind_param('ii', $p1, $p2);
            $p1 = $status;
            $p2 = $pkId;
            $updateStmt->execute();
            $outcome = ($updateStmt->affected_rows >= 1); // at least 1 record has been updated
            $updateStmt->close();
        }catch(Exception $e) {
            $this->log->error("updateEventStatus - Error: " . $e->getMessage());
            $outcome = false;
        }
        $this->closeConnection();
        return $outcome;
    }

    /**
     * Calls updateEvent stored procedures once for every event object in the parameter array.
     *
     * @param $eventsArray AamsEvent[]
     */
    public function updateEvents($eventsArray)
    {
        $this->openConnection();
        try{
            $stmt = $this->mysqli->prepare("call updateEvent(?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iiissssss", $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9);
            foreach($eventsArray as $event) {
                $p1 = $event->getMode();
                $p2 = $event->getAamsEventId();
                $p3 = $event->getAamsProgramId();
                $p4 = $event->getName();
                $p5 = $event->getHref();
                $p6 = $event->getDateTime();
                $p7 = $event->getCategory();
                $p8 = $event->getSubCategory();
                $p9 = $event->getHash();
                $stmt->execute();
            }
            $stmt->close();
        }catch (Exception $e) {
            $this->log->error("updateEvents - Error: " . $e->getMessage());
        }
        $this->closeConnection();
    }

    /**
     * Returns the processor status
     *
     * @param $mode int Processor mode
     * @return null
     */
    public function getProcessStatus($mode)
    {
        $result = null;
        $this->openConnection();
        try{
            $result = $this->mysqli->query("SELECT * FROM ". DbProvider::AAMS_PROCESS_INFO_TABLE ." where mode = ". $mode);
        }catch(Exception $e){
            $this->log->error("getProcessStatus - Error: " . $e->getMessage());
        }
        $this->closeConnection();
        return $result;
    }

    /**
     * Calls beginProcess stored procedure. If a process is still running, then 0 is returned,
     * othewise, it returns 1.
     *
     * @param $mode int The processor mode
     * @return int The operation result (0=no-go, 1=go)
     */
    public function beginProcess($mode)
    {
        $canRun = 0;
        $this->openConnection();
        try {
            $this->mysqli->multi_query("call beginProcess(" . $mode . ", @canrun);SELECT @canrun as can_run;");
            $this->mysqli->next_result();
            $rs=$this->mysqli->store_result();
            $canRun = $rs->fetch_object()->can_run;
            $rs->free();
        } catch (Exception $e) {
            $this->log->error("beginProcess - Error: " . $e->getMessage());
            $canRun = 0;
        }
        $this->closeConnection();
        return $canRun;
    }

    /**
     * Updates the process_info table setting the status to idle, and updating the last_finish timestamp.
     *
     * @param $mode int The processor mode
     */
    public function endProcess($mode)
    {
        $this->openConnection();
        try {
            $this->mysqli->multi_query("UPDATE " . DbProvider::AAMS_PROCESS_INFO_TABLE . " SET status = 0, last_finish = UNIX_TIMESTAMP() WHERE mode = " . $mode);
        } catch (Exception $e) {
            $this->log->error("endProcess - Error: " . $e->getMessage());
        }
        $this->closeConnection();
    }

    /**
     * Returns a resultset containing all events records within a given status range (and mode).
     * It returns false if any error occurs.
     *
     * @param $mode int The event mode
     * @param $status int[] The event statuses
     * @return bool|mysqli_result
     */
    public function getEventsByStatus($mode, $status)
    {
        $result = false;
        $this->openConnection();
        try {
            $statusCondition = implode(", ", $status);
            $this->mysqli->query('SET CHARACTER SET utf8');
            $result = $this->mysqli->query("SELECT * FROM " . DbProvider::AAMS_EVENTS_TABLE . " where mode = $mode AND status IN (" . $statusCondition . ")");
        } catch (Exception $e) {
            $this->log->error("getEventsByStatus - Error: " . $e->getMessage());
            $result = false;
        }
        $this->closeConnection();
        return $result;
    }

    /**
     * Get last execution timestamp at a for a given processor mode
     *
     * @param $mode int The processor mode
     * @return bool|mysqli_result The database result set containing the last execution timestamp,
     * false in case of error
     */
    public function getLastUpdate($mode)
    {
        $result = false;
        $this->openConnection();
        try {
            $result = $this->mysqli->query("SELECT FROM_UNIXTIME(last_finish + 3600, '%d/%m/%Y %H:%i:%s') AS last_finish FROM " . DbProvider::AAMS_PROCESS_INFO_TABLE . " where mode = $mode");
        } catch (Exception $e) {
            $this->log->error("getEventsByStatus - Error: " . $e->getMessage());
        }
        $this->closeConnection();
        return $result;
    }

    /**
     * Updates all events' status for a given process mode.
     *
     * @param $mode int The processor mode
     * @param $status int The new status
     * @return bool|mysqli_result The database result set, false if any error occurs
     */
    public function updateAllEventsStatus($mode, $status)
    {
        $result = false;
        $this->openConnection();
        try{
            $result = $this->mysqli->query("UPDATE ". DbProvider::AAMS_EVENTS_TABLE ." SET status = " . $status . " where mode = " . $mode);
        } catch (Exception $e) {
            $this->log->error("updateAllEventsStatus - Error: " . $e->getMessage());
        }
        $this->closeConnection();
        return $result;
    }
}