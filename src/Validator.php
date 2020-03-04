<?php
namespace AndikaMC\WebOptimizer;

class Validator
{
    public function IsHTMLDoc($input)
    {
        return mb_stripos($input, "</html>");
    }

    public function IsValidUTF8($input)
    {
        preg_match('/./u', $input);
        $last_error = preg_last_error();
        return !in_array($last_error, [PREG_BAD_UTF8_ERROR, PREG_BAD_UTF8_OFFSET_ERROR], true);
    }

    public function FixRequestScheme()
    {
        if ( (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443'))
        {
            $_SERVER["REQUEST_SCHEME"] = "https";
        }
        else
        {
            $_SERVER["REQUEST_SCHEME"] = "http";
        }

    }
}