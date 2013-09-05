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

require_once 'Constants.php';
require_once 'ProcessManager.php';

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