<?php
namespace AndikaMC\WebOptimizer\Classes\Optimization\JS;

use AndikaMC\WebOptimizer\Classes\Optimization\HTML\Cracker;
use MatthiasMullie\Minify as VendorMinify;

class Minify
{
    public static function minify($buffer)
    {
        $engine = new Cracker;

        $cracked_js = $engine->CrackJSTags($engine->CrackHTML($buffer));
        foreach($cracked_js as $js_crack)
        {
            if ($js_crack["script_js"])
            {
                $minifier = new VendorMinify\JS($js_crack["script_js"]);
                $buffer = str_replace($js_crack["script_js"], $minifier->minify(), $buffer);
            }
        }

        return $buffer;
    }
}