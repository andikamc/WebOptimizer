<?php
namespace AndikaMC\WebOptimizer\Classes\Optimization;

use AndikaMC\WebOptimizer\Classes\Optimization\HTML\Minify;

class Engine
{
    public function Optimize($buffer, $options)
    {
        if (isset($options["optimize_html"]) && !empty($options["optimize_html"]))
        { // optimize html
            $engine = Minify::class;
            $buffer = $engine::minify($buffer);
        }

        if (isset($options["optimize_css"]) && !empty($options["optimize_css"]))
        { // optimize css
            $engine = Minify::class;
            $buffer = $engine::minify($buffer);
        }

        return $buffer;
    }
}