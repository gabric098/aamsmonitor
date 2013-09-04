<?php
/**
 * Created by JetBrains PhpStorm.
 * User: gabriele
 * Date: 16/08/13
 * Time: 10:29
 * To change this template use File | Settings | File Templates.
 */

class DbProvider {
    private $db_host;
    private $db_user;
    private $db_pwd;
    private $db_name;
    private $log;
    private $aamsEventsTable = 'aams_events';
    private $processInfoTable = 'process_info';
    private $mysqli; // the connection

    const STATUS_NORMAL = 0;
    const STATUS_CHANGED = 1;
    const STATUS_NEW = 2;

    function __construct($db_host, $db_user, $db_pwd, $db_name)
    {
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_pwd = $db_pwd;
        $this->db_name = $db_name;
    }

    public function setLogger($log)
    {
        $this->log = $log;
    }

    private function openConnection()
    {
        $this->mysqli = new mysqli($this->db_host, $this->db_user, $this->db_pwd, $this->db_name);
        if ($this->mysqli->connect_errno) {
            $this->log->error("Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);
            suicide();
        }
    }

    private function closeConnection()
    {
        $this->mysqli->close();
    }

    public function updateEventStatus($pkId, $status)
    {
        $outcome = true;
        $this->openConnection();
        try {
            if (!($updateStmt = $this->mysqli->prepare("UPDATE " . $this->aamsEventsTable . " SET status = ? WHERE id = ?"))){
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

    /** call stored procedure updateEvent by prepared statement */
    public function updateEvents($eventsArray)
    {
        $this->openConnection();
        try{
            $stmt = $this->mysqli->prepare("call updateEvent(?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iiissssss", $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9);
            foreach($eventsArray as $event) {
                /** @var  $event AamsEvent */
                $p1 = $event->mode;
                $p2 = $event->aams_event_id;
                $p3 = $event->aams_program_id;
                $p4 = $event->name;
                $p5 = $event->href;
                $p6 = $event->dateTime;
                $p7 = $event->category;
                $p8 = $event->subCategory;
                $p9 = $event->hash;
                $stmt->execute();
            }
            $stmt->close();
        }catch (Exception $e) {
            $this->log->error("updateEvents - Error: " . $e->getMessage());
        }
        $this->closeConnection();
    }

    public function getProcessStatus($mode)
    {
        $result = null;
        $this->openConnection();
        try{
            $result = $this->mysqli->query("SELECT * FROM ". $this->processInfoTable ." where mode = ". $mode);
        }catch(Exception $e){
            $this->log->error("getProcessStatus - Error: " . $e->getMessage());
        }
        $this->closeConnection();
        return $result;
    }

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
        }
        $this->closeConnection();
        return $canRun;
    }

    public function setDbUpdateProcessStatus($mode)
    {
        $allOk = 0;
        $this->openConnection();
        try {
            $this->mysqli->multi_query("call setDbUpdateProcess(" . $mode . ", @allok);SELECT @allok as can_run;");
            $this->mysqli->next_result();
            $rs=$this->mysqli->store_result();
            $allOk = $rs->fetch_object()->can_run;
            $rs->free();
        } catch (Exception $e) {
            $this->log->error("setDbUpdateProcessStatus - Error: " . $e->getMessage());
        }
        $this->closeConnection();
        return $allOk;
    }

    public function endProcess($mode)
    {
        $this->openConnection();
        try {
            $this->mysqli->multi_query("UPDATE " . $this->processInfoTable . " SET status = 0, last_finish = UNIX_TIMESTAMP() WHERE mode = " . $mode);
        } catch (Exception $e) {
            $this->log->error("endProcess - Error: " . $e->getMessage());
        }
        $this->closeConnection();
    }

    public function getEventsByStatus($mode, $status)
    {
        $this->openConnection();
        try {
            $statusCondition = implode(", ", $status);
            $this->mysqli->query('SET CHARACTER SET utf8');
            $result = $this->mysqli->query("SELECT * FROM " . $this->aamsEventsTable . " where mode = $mode AND status IN (" . $statusCondition . ")");
        } catch (Exception $e) {
            $this->log->error("getEventsByStatus - Error: " . $e->getMessage());
        }
        $this->closeConnection();
        return $result;
    }

    public function getLastUpdate($mode)
    {
        $this->openConnection();
        try {
            $result = $this->mysqli->query("SELECT FROM_UNIXTIME(last_finish + 3600, '%d/%m/%Y %H:%i:%s') AS last_finish FROM " . $this->processInfoTable . " where mode = $mode");
        } catch (Exception $e) {
            $this->log->error("getEventsByStatus - Error: " . $e->getMessage());
        }
        $this->closeConnection();
        return $result;
    }

    public function updateAllEventsStatus($mode, $status)
    {
        $this->openConnection();
        try{
            $result = $this->mysqli->query("UPDATE ". $this->aamsEventsTable ." SET status = " . $status . " where mode = " . $mode);
        } catch (Exception $e) {
            $this->log->error("updateAllEventsStatus - Error: " . $e->getMessage());
        }
        $this->closeConnection();
        return $result;
    }

    public function deleteAllEventsStatus($mode)
    {
        $this->openConnection();
        try {
            $result = $this->mysqli->query("DELETE FROM " . $this->aamsEventsTable . " where mode = " . $mode);
        } catch (Exception $e) {
            $this->log->error("deleteAllEventsStatus - Error: " . $e->getMessage());
        }
        $this->closeConnection();
        return $result;
    }
}