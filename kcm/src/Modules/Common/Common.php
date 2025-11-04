<?php

namespace Kova\Kcm\Modules\Common;

class Common
{

    public $cargo;
    public $config;

    public function __construct($config)
    {
        $this->config = $config;
    }



    public function getContents($str, $startDelimiter, $endDelimiter)
    {
        $contents = array();
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $contentStart = $contentEnd = 0;
        while (false !== ($contentStart = strpos($str, $startDelimiter, $startFrom))) {
            $contentStart += $startDelimiterLength;
            $contentEnd = strpos($str, $endDelimiter, $contentStart);
            if (false === $contentEnd) {
                break;
            }
            $contents[] = substr($str, $contentStart, $contentEnd - $contentStart);
            $startFrom = $contentEnd + $endDelimiterLength;
        }

        return $contents;
    }

    public function clean($string)
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

    public function buildMsgLine($valArray)
    {

        date_default_timezone_set('America/New_York');

        $ClientEmail = $this->config['ClientEmail'];
        $Site = $this->config['SiteName'];
        $epoch = time();

        $User = $valArray[1];
        $Message = $valArray[2];
        $Value1 = $valArray[3];
        $Value2 = $valArray[4];
        $Value3 = $valArray[5];
        $Channel = $valArray[6];
        $Duration = $valArray[7];


        $msgLine = $epoch . "," . $Site . "," . $User . "," . $Message . "," . $Value1 . "," . $Value2 . "," . $Value3 . "," . $Channel . "," . $Duration . "," . $ClientEmail;

        echo $msgLine . PHP_EOL;

        return $msgLine;
    }

    public function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}
