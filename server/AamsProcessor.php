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

class AamsProcessor {
    private $mode;
    private $log;
    private $mysqli;

    const BASE_URL = "http://www.aams.gov.it/site.php";

    const DATA_TYPE_HREF = 0;
    const DATA_TYPE_TEXT = 1;

    function __construct($mode, $log, $mysqli) {
        $this->mode = $mode;
        $this->log = $log;
        $this->mysqli = $mysqli;
        $this->eventArray = array();
    }

    public function run() {
        $this->log->info('AAMS info crawler started @ ' . date('H:i:s'));

        // get the main page content
        $mainPage = $this->get_url(self::BASE_URL . '?id=2863');
        $contDiv = $mainPage->find('.portaboxInterna');

        // check if the .portaboxInterna div exists
        if (!isset($contDiv)) {
            $this->suicide("Cannot locate .portaboxInterna");
        }

        // check if relevant div exists
        $contentDiv = ($this->mode == Constants::MODE_QUOTA_FISSA) ? $contDiv[0] : $contDiv[1];
        if (!isset($contentDiv)) {
            $this->suicide("Cannot locate mode " . $this->mode . " content");
        }

        $this->parseMainCat($contentDiv);

        // ended using sub content dom, free memory
        $mainPage->clear();
        unset($mainPage);

        // persists all the information to the database
        $this->dbProcess();
        $this->log->info('AAMS info crawler ended @ ' . date('H:i:s'));
    }

    // This function assumes that the page has the following html structure:
    // <h5> Event general category </h5>
    // <ul>
    // <li><a href="">Event detail category</a></li>
    // </ul>
    private function parseMainCat($content) {
        $mainCategory = '';
        foreach ($content->children as $chidNode) {
            if ($chidNode->tag == 'h5') {
                $mainCategory = $this->normalizeData($chidNode->innertext, self::DATA_TYPE_TEXT);
            }
            if ($chidNode->tag == 'ul') {
                foreach($chidNode->children as $lis){
                    $subCategory = $this->normalizeData($lis->children(0)->innertext, self::DATA_TYPE_TEXT);
                    $subCategoryHref = $this->normalizeData($lis->children(0)->href, self::DATA_TYPE_HREF);
                    $subContent = $this->get_url((self::BASE_URL . $subCategoryHref));
                    $this->parseSubCat($subContent, $mainCategory, $subCategory);
                    // ended using sub content dom, free memory
                    $subContent->clear();
                    unset($subContent);
                }
            }
        }
    }

    // This function assumes that the page has the following html structure:
    // <table class="risultatiTotocalcio">
    // <tr>
    //   <th></th>
    // </tr>
    // <tr>
    //   <td><a href="">Nome evento</a></td>
    //   <td>Data e ora</td>
    //   <td>Programma</td>
    //   <td>Numero Avvenimento</td>
    // </tr>
    // </table>
    private function parseSubCat($subContent, $mainCategory, $subCategory) {
        $events = array();
        $eventTable = $subContent->find(".risultatiTotocalcio");
        if (!isset($eventTable)) {
            $this->suicide("Cannot locate .risultatiTotocalcio table");
        }
        foreach($eventTable[0]->children(0)->children as $row) {
            if ($row->tag == 'tr' && $row->children(0)->tag == 'td') {

                // this is an event row
                // first td is event name and link
                $eventHref = $this->normalizeData($row->children(0)->children(0)->href, self::DATA_TYPE_HREF);
                $eventName = $this->normalizeData($row->children(0)->children(0)->innertext, self::DATA_TYPE_TEXT);

                // 2nd td is date and time
                $eventDateTime = $this->normalizeData($row->children(1)->innertext, self::DATA_TYPE_TEXT);

                // 3rd td is program id
                $programId = $this->normalizeData($row->children(2)->innertext, self::DATA_TYPE_TEXT);

                // 4th td is event id
                $eventId = $this->normalizeData($row->children(3)->innertext, self::DATA_TYPE_TEXT);

                // I get the event detail page
                $eventDetailContent = $this->get_url(self::BASE_URL . $eventHref);
                $detailTable = $eventDetailContent->find('.risultatiTotocalcio');
                if (!isset($detailTable)) {
                    $this->suicide("Cannot locate .risultatiTotocalcio table for details");
                }
                // and I calculate the page hash
                $theHash = md5($detailTable[0]->plaintext);

                // now that I got a bunch of data, I store it in an object
                $event = new AamsEvent();
                $event->setAamsEventId($eventId);
                $event->setDateTime($eventDateTime);
                $event->setHref($eventHref);
                $event->setName($eventName);
                $event->setAamsProgramId($programId);
                $event->setHash($theHash);
                $event->setMode($this->mode);
                $event->setCategory($mainCategory);
                $event->setSubCategory($subCategory);

                $this->eventArray[] = $event;
                // ended event detail content dom, free memory
                $eventDetailContent->clear();
                unset($eventDetailContent);
            }
        }
    }

    private function dbProcess() {
        $this->log->info('dbProcess - Begin updating database, events found: ' . count($this->eventArray));
        $this->mysqli->updateEvents($this->eventArray);
        $this->log->info('dbProcess - Finished updating database');
    }

    private function get_url($url) {
        $this->log->info('get_url - Reading url: ' . $url);
        $context = stream_context_create();
        stream_context_set_params($context, array('user_agent' => 'Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1'));
        $toReturn = file_get_html($url, 0, $context);
        if ($toReturn === false) {
            $this->log->error('get_url - Error reading url: ' . $url);
        }
        return $toReturn;
    }

    private function suicide($msg) {
        throw new Exception($msg);
    }

    private function normalizeData($dataStr, $dataType) {
        switch ($dataType) {
            case (self::DATA_TYPE_HREF):
                return str_replace(' ', '%20', trim(html_entity_decode($dataStr)));
            case (self::DATA_TYPE_TEXT):
                return trim(html_entity_decode($dataStr));
            default:
                return $dataStr;
        }
    }
}