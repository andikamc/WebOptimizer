<?php
namespace AndikaMC\WebOptimizer\Classes\Optimization\CSS;

use AndikaMC\WebOptimizer\Classes\Optimization\HTML\Cracker;
use MatthiasMullie\Minify as VendorMinify;

class Minify
{
    public static function minify($buffer)
    {
        $engine = new Cracker;

        $cracked_css = $engine->CrackCSSTags($engine->CrackHTML($buffer));
        foreach($cracked_css as $css_crack)
        {
            if ($css_crack["style_css"])
            {
                $minifier = new VendorMinify\CSS($css_crack["style_css"]);
                $buffer = str_replace($css_crack["style_css"], $minifier->minify(), $buffer);
            }
        }

        return $buffer;
    }
}