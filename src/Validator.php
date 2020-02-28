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
}