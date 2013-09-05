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

class AamsEvent
{
    public $aams_event_id;
    public $aams_program_id;
    public $name;
    public $href;
    public $dateTime;
    public $hash;
    public $mode; // 0 = QUOTA_FISSA | 1 = LIVE
    public $category;
    public $subCategory;

    function __toString()
    {
        $str = "Event Name: " . $this->name . "\r\n" .
            "Event Href: " . $this->href . "\r\n" .
            "Event DateTime: " . $this->dateTime . "\r\n" .
            "Mode (live | quota fissa): " . $this->mode . "\r\n" .
            "Category: " . $this->category . "\r\n" .
            "SubCategory: " . $this->subCategory . "\r\n" .
            "Program Id: " . $this->aams_program_id . "\r\n" .
            "Event Id: " . $this->aams_event_id. "\r\n" .
            "Event Page Hash: " . $this->hash. "\r\n" .
            "\r\n";
        return $str;
    }
}