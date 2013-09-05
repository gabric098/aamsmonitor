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

/**
 * Class WebServices
 *
 * Defines a set of methods for interacting with AAMS Monitor. It's a kind of interface for web users
 *
 * @category    AAMS Monitor
 * @copyright   Copyright (C) 2013  Gabriele Antonini (gabriele.antonini@gmail.com)
 * @license     GNU General Public License
 */
class WebServices
{
    /** @var  $mysqli DbProvider */
    private $mysqli;

    /**
     * Initializes the WebServices object with a DbProvider instance
     *
     * @param $mysqli DbProvider The DbProvider instance
     */
    function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * Returns a JSON encoded string containing the list of all the changed/new
     * "live" events found on db
     *
     * @return string The JSON encoded event list
     */
    function getLiveEventsByStatus()
    {
        return $this->getChangedAndNewEvents(Constants::MODE_LIVE);
    }

    /**
     * Returns a JSON encoded string containing the list of all the changed/new
     * "quota fissa" events found on db
     *
     * @return string The JSON encoded event list
     */
    function getQfEventsByStatus()
    {
        return $this->getChangedAndNewEvents(Constants::MODE_QUOTA_FISSA);
    }

    /**
     * Sets a single event status to NORMAL (0).
     * An error message is provided into the JSON response if the operation fails.
     *
     * @param $pkId int The events' primary key
     * @return string The JSON encoded operation result
     */
    function setEventAsRead($pkId)
    {
        $success = true;
        $niceErrMsg = '';
        try {
            if ($this->mysqli->updateEventStatus($pkId, DbProvider::STATUS_NORMAL) === false) {
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

    /**
     * Sets all events with a given mode status to NORMAL (0).
     * An error message is provided into the JSON response if the operation fails.
     *
     * @param $mode int The event mode
     * @return string The JSON encoded operation result
     */
    function setAllEventAsRead($mode)
    {
        $success = true;
        $niceErrMsg = '';
        try {
            $res = $this->mysqli->updateAllEventsStatus($mode, DbProvider::STATUS_NORMAL);
            if ($res === true) {
                $success = true;
            } else {
                $success = false;
                $niceErrMsg = "Ops... A quanto pare non riesco a cancellare i dati, riprova tra un attimo";
            }
        } catch(Exception $e) {
            $success = false;
            $niceErrMsg = "Uhm... a quanto pare c'e' stato un errore non previsto";
        }
        if (!$success) {
            return $this->formatNiceError($niceErrMsg);
        }
        return '{"success": true}';
    }

    /**
     * Returns a JSON formatted response containing the last update time for a given processor mode.
     *
     * @param $mode int The processor mode
     * @return string The JSON encoded operation result
     */
    function getLastUpdateTime($mode)
    {
        $success = true;
        $niceErrMsg = '';
        $lastUpdate = ' - ';

        try {
            $events = $this->mysqli->getLastUpdate($mode);
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

    /**
     * Utility function. It formats the error messages in a JSON format
     *
     * @param $msg string The error message
     * @return string The JSON encoded string
     */
    private function formatNiceError($msg)
    {
        return '{"success": false, "msg": "'.$msg.'"}';
    }

    /**
     * Returns a JSON encoded string containing the list of all the changed/new
     * events found on db for a given processor mode
     *
     * @param $mode int The processor mode
     * @return string The JSON encoded event list
     */
    private function getChangedAndNewEvents($mode)
    {
        $events = $this->mysqli->getEventsByStatus($mode, array(DbProvider::STATUS_CHANGED, DbProvider::STATUS_NEW));
        $eventsArr = array();
        if ($events->num_rows > 0) {
            while ($event = $events->fetch_object()) {
                $eventsArr[$event->id] = $event;
            }
        }
        mysqli_free_result($events);
        return json_encode($eventsArr);
    }
}