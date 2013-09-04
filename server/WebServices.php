<?php
    require_once 'Constants.php';
    require_once 'ProcessManager.php';
/**
 * Created by JetBrains PhpStorm.
 * User: gabriele
 * Date: 16/08/13
 * Time: 13:04
 * To change this template use File | Settings | File Templates.
 */

class WebServices {

    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    function getLiveEventsByStatus()
    {
        $events = $this->db->getEventsByStatus(Constants::MODE_LIVE, array(DbProvider::STATUS_CHANGED, DbProvider::STATUS_NEW));
        $eventsArr = array();
        if ($events->num_rows > 0) {
            while ($event = $events->fetch_object()) {
                $eventsArr[$event->id] = $event;
            }
        }
        mysqli_free_result($events);
        return json_encode($eventsArr);
    }

    function getQfEventsByStatus()
    {
        $events = $this->db->getEventsByStatus(Constants::MODE_QUOTA_FISSA, array(DbProvider::STATUS_CHANGED, DbProvider::STATUS_NEW));
        $eventsArr = array();
        if ($events->num_rows > 0) {
            while ($event = $events->fetch_object()) {
                $eventsArr[$event->id] = $event;
            }
        }
        mysqli_free_result($events);
        return json_encode($eventsArr);
    }

    function setEventAsRead($pkId)
    {
        $success = true;
        $niceErrMsg = '';
        try {
            if ($this->db->updateEventStatus($pkId, DbProvider::STATUS_NORMAL) === false) {
                $success = false;
                $niceErrMsg = "Ahia... non riesco a cancellare il dato, prova ad aggiornare e provare ancora";
            }
        }catch(Exception $e) {
            $success = false;
            $niceErrMsg = "Uhm... a quanto pare c'e' stato un errore non previsto";
        }
        if (!$success) {
            return $this->formatNiceError($niceErrMsg);
        }
        return '{"success": true}';
    }

    function setAllEventAsRead($mode)
    {
        $success = true;
        $niceErrMsg = '';
        try {
            $res = $this->db->getProcessStatus($mode);
            $resArr = $res->fetch_array();
            if ($res->num_rows == 0 || $resArr['status'] == ProcessManager::STATUS_IDLE || $resArr['status'] == ProcessManager::STATUS_RUNNING) {
                $res = $this->db->updateAllEventsStatus($mode, DbProvider::STATUS_NORMAL);
                if ($res === true) {
                    $success = true;
                } else {
                    $success = false;
                    $niceErrMsg = "Ops... A quanto pare non riesco a cancellare i dati, riprova tra un attimo";
                }
            } else{
                 $success = false;
                 $niceErrMsg = "Solo un attimo... In questo momento sto aggiornado i dati sul database, potrai cancellare tutti gli eventi non appena avro' finito, riprova tra qualche minuto";
            }
            // else
        } catch(Exception $e) {
            $success = false;
            $niceErrMsg = "Uhm... a quanto pare c'e' stato un errore non previsto";
        }
        if (!$success) {
            return $this->formatNiceError($niceErrMsg);
        }
        return '{"success": true}';
    }

    function getLastUpdateTime($mode)
    {
        $success = true;
        $niceErrMsg = '';
        $lastUpdate = ' - ';

        try {
            $events = $this->db->getLastUpdate($mode);
            if ($events->num_rows > 0) {
                $event = $events->fetch_object();
                $lastUpdate = $event->last_finish;
            }
        } catch(Exception $e) {
            $success = false;
            $niceErrMsg = 'La data dell\'ultimo aggiornamento non e\' disponibile';
        }

        if (!$success) {
            return $this->formatNiceError($niceErrMsg);
        }
        return '{"success": true, "lastUpdate": "'.$lastUpdate.'"}';
    }

    private function formatNiceError($msg) {
        return '{"success": false, "msg": "'.$msg.'"}';
    }
}