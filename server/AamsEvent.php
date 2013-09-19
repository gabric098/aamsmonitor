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
 * Class AamsEvent
 *
 * A "bean-like" class representing an Event object
 *
 * @category    AAMS Monitor
 * @copyright   Copyright (C) 2013  Gabriele Antonini (gabriele.antonini@gmail.com)
 * @license     GNU General Public License
 */
class AamsEvent
{
    /** @var $aams_event_id string */
    private $aams_event_id;
    /** @var $aams_program_id string */
    private $aams_program_id;
    /** @var $name string */
    private $name;
    /** @var $href string */
    private $href;
    /** @var $dateTime string */
    private $dateTime;
    /** @var $hash string */
    private $hash;
    /** @var $mode int */
    private $mode; // 0 = QUOTA_FISSA | 1 = LIVE
    /** @var $category string */
    private $category;
    /** @var $subCategory string */
    private $subCategory;

    /**
     * @param string $aams_event_id
     */
    public function setAamsEventId($aams_event_id)
    {
        $this->aams_event_id = $aams_event_id;
    }

    /**
     * @return string
     */
    public function getAamsEventId()
    {
        return $this->aams_event_id;
    }

    /**
     * @param string $aams_program_id
     */
    public function setAamsProgramId($aams_program_id)
    {
        $this->aams_program_id = $aams_program_id;
    }

    /**
     * @return string
     */
    public function getAamsProgramId()
    {
        return $this->aams_program_id;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $dateTime
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @return string
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $href
     */
    public function setHref($href)
    {
        $this->href = $href;
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param int $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $subCategory
     */
    public function setSubCategory($subCategory)
    {
        $this->subCategory = $subCategory;
    }

    /**
     * @return string
     */
    public function getSubCategory()
    {
        return $this->subCategory;
    }

    /**
     * __toString() override method outputting a string representation of the object
     *
     * @return string The string representation of the object
     */
    public function __toString()
    {
        $str = "Event Name: " . $this->name . "\r\n" .
            "Event Href: " . $this->href . "\r\n" .
            "Event DateTime: " . $this->dateTime . "\r\n" .
            "Mode (live:1 | quota fissa:0): " . $this->mode . "\r\n" .
            "Category: " . $this->category . "\r\n" .
            "SubCategory: " . $this->subCategory . "\r\n" .
            "Program Id: " . $this->aams_program_id . "\r\n" .
            "Event Id: " . $this->aams_event_id. "\r\n" .
            "Event Page Hash: " . $this->hash. "\r\n" .
            "\r\n";
        return $str;
    }
}