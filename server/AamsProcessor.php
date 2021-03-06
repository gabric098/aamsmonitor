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
 * Class AamsProcessor
 *
 * This class takes care of crawl AAMS website and get all the relevant information.
 * The only way to do that is parsing HTML pages and identify relevant data, for this
 * reason this class behaviour is deeply dependent on the AAMS site structure. Unfortunately
 * there's no other way (that I'm aware of) to make this data available in a more standard
 * format.
 *
 * @category    AAMS Monitor
 * @copyright   Copyright (C) 2013  Gabriele Antonini (gabriele.antonini@gmail.com)
 * @license     GNU General Public License*
 */
class AamsProcessor
{
    /** @var $mode string */
    private $mode;
    /** @var $mode MyLogPHP */
    private $log;
    /** @var $mode DbProvider */
    private $mysqli;

    const BASE_URL = "http://www.aams.gov.it/site.php";
    const DATA_TYPE_HREF = 0;
    const DATA_TYPE_TEXT = 1;

    /**
     * Initializes the class with the logger and the database connection
     *
     * @param $mode int processing mode (live or quotafissa)
     * @param $log MyLogPHP logger instance
     * @param $mysqli mysqli database connection
     */
    function __construct($mode, $log, $mysqli)
    {
        $this->mode = $mode;
        $this->log = $log;
        $this->mysqli = $mysqli;
        $this->eventArray = array();
    }

    /**
     * Begins the crawling operation, process recursively the event pages and finally
     * call the dbProcess() method to save the data into the database
     */
    public function run()
    {
        $this->log->info('AAMS info crawler started @ ' . date('H:i:s'));

        // get the main page content
        $mainPage = $this->get_url(self::BASE_URL . '?id=2863');

        // builds the xPath to locate relevant content
        $xpath = new DOMXpath($mainPage);
        $contentDivId = ($this->mode == Constants::MODE_QUOTA_FISSA) ? 'portaboxInternaSx' : 'portaboxInternaDx1';
        $contentDiv = $xpath->query("//div[@id = '" . $contentDivId . "']");

        // check if the div exists
        if ($contentDiv->length != 1) {
            $this->suicide("Cannot locate mode " . $this->mode . " content");
        }

        // processes the main page relevant content
        $this->parseMainCat($contentDiv->item(0));

        // ended using sub content dom, free memory
        unset($mainPage);

        // persists all the information to the database
        $this->dbProcess();
        $this->log->info('AAMS info crawler ended @ ' . date('H:i:s'));
    }

    /**
     * Parses AAMS main page DOM content and extracts the events list
     *
     * @param $content DOMNode the main page content
     */
    private function parseMainCat($content)
    {
        $mainCategory = '';
        $contentChildren = $content->childNodes;
        foreach ($contentChildren as $childNode) {
            /* @var $childNode DOMNode */
            if ($childNode->nodeName == 'h5') {
                $mainCategory = $this->normalizeData($childNode->nodeValue, self::DATA_TYPE_TEXT);
            }
            if ($childNode->nodeName == 'ul') {
                foreach($childNode->childNodes as $lis){
                    /* @var $lis DOMNode */
                    if ($lis->nodeType === XML_ELEMENT_NODE) {
                        foreach ($lis->childNodes as $li) {
                            /* @var $li DOMElement */
                            if ($li->nodeType === XML_ELEMENT_NODE) {
                                // get the <a> node
                                $subCategory = $this->normalizeData($li->nodeValue, self::DATA_TYPE_TEXT);
                                $subCategoryHref = $this->normalizeData($li->getAttribute('href'), self::DATA_TYPE_HREF);
                                $subContent = $this->get_url((self::BASE_URL . $subCategoryHref));
                                $this->parseSubCat($subContent, $mainCategory, $subCategory);
                                // ended using sub content dom, free memory
                                unset($subContent);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Processes a sub category page retrieving all the events' specific info
     * All event objects are stored into eventArray array
     *
     * @param $subContent DOMNode the sub category page content
     * @param $mainCategory string main category name
     * @param $subCategory string sub category name
     */
    private function parseSubCat($subContent, $mainCategory, $subCategory)
    {
        // builds the xPath to locate relevant content
        $xpath = new DOMXpath($subContent);
        $eventTableList = $xpath->query("//table[@class = 'risultatiTotocalcio']");

        // check if the div exists
        if ($eventTableList->length != 1) {
            $this->suicide("Cannot locate risultatiTotocalcio table");
        }

        foreach($eventTableList->item(0)->childNodes->item(0)->childNodes as $row) {
            /* @var $row DOMNode */
            if ($row->nodeName == 'tr' && $row->childNodes->item(0)->nodeName == 'td') {
                $columns = $row->childNodes;

                // this is an event row
                // first td is event name and link
                $firstTd = $columns->item(0);
                $eventHref = $this->normalizeData($firstTd->childNodes->item(0)->getAttribute('href'), self::DATA_TYPE_HREF);
                $eventName = $this->normalizeData($firstTd->childNodes->item(0)->nodeValue, self::DATA_TYPE_TEXT);

                // 2nd td is date and time
                $secondTd = $columns->item(2);
                $eventDateTime = $this->normalizeData($secondTd->nodeValue, self::DATA_TYPE_TEXT);

                // 3rd td is program id
                $thirdTd = $columns->item(4);
                $programId = $this->normalizeData($thirdTd->nodeValue, self::DATA_TYPE_TEXT);

                // 4th td is event id
                $fourthTd = $columns->item(6);
                $eventId = $this->normalizeData($fourthTd->nodeValue, self::DATA_TYPE_TEXT);

                // I get the event detail page
                $eventDetailContent = $this->get_url(self::BASE_URL . $eventHref);

                // builds the xPath to locate relevant content
                $xpath = new DOMXpath($eventDetailContent);
                $detailTableList = $xpath->query("//table[@class = 'risultatiTotocalcio']");

                // check if the div exists
                if ($detailTableList->length != 1) {
                    $this->suicide("Cannot locate risultatiTotocalcio table in detail page");
                }

                // and I calculate the detail table hash
                $detalTable = $detailTableList->item(0);
                $theHash = md5($detalTable->ownerDocument->saveXML($detalTable));

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
                unset($eventDetailContent);
            }
        }
    }

    /**
     * Invokes the updateEvents method on the database adapter passing the events list as
     * parameter. The events list is saved to database
     */
    private function dbProcess()
    {
        $this->log->info('dbProcess - Begin updating database, events found: ' . count($this->eventArray));
        $this->mysqli->updateEvents($this->eventArray);
        $this->log->info('dbProcess - Finished updating database');
    }

    /**
     * Loads given url contents and parses it to a DOMDocument object
     *
     * @param $url string The url to load
     * @return DOMDocument The DOM object representing the loaded page content
     */
    private function get_url($url)
    {
        $this->log->info('get_url - Reading url: ' . $url);
        $doc = new DOMDocument('1.0');
        $context = stream_context_create();
        stream_context_set_params($context, array('user_agent' => 'Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1'));
        libxml_set_streams_context($context);

        // Request the file through HTTP, suppressing warnings in case of malformed HTML
        $result = @$doc->loadHTMLFile($url);

        if ($result === false) {
            $this->log->error('get_url - Error reading url: ' . $url);
        }
        return $doc;
    }

    /**
     * In case of problem, throw an exception
     *
     * @param $msg string The exception message
     * @throws Exception
     */
    private function suicide($msg)
    {
        throw new Exception($msg);
    }

    /**
     * Sanitizes a bit the data coming from the web pages depending on the data type
     *
     * @param $dataStr string the data to sanitize
     * @param $dataType in the data type
     * @return string the sanitized data
     */
    private function normalizeData($dataStr, $dataType)
    {
        switch ($dataType) {
            case (self::DATA_TYPE_HREF):
                return str_replace(' ', '%20', trim(html_entity_decode($dataStr)));
            case (self::DATA_TYPE_TEXT):
                return str_replace(array("\xC2", "\xA0", "\x09"), '', trim(html_entity_decode($dataStr)));
            default:
                return $dataStr;
        }
    }
}