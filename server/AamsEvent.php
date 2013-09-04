<?php
/**
 * Created by JetBrains PhpStorm.
 * User: gabriele
 * Date: 16/08/13
 * Time: 10:26
 * To change this template use File | Settings | File Templates.
 */

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