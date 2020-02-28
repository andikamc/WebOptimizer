<?php
namespace AndikaMC\WebOptimizer\Classes\Optimization;

use AndikaMC\WebOptimizer\Classes\Optimization\HTML\Minify as HTMLMinify;
use AndikaMC\WebOptimizer\Classes\Optimization\CSS\Combine as CombineCSS;
use AndikaMC\WebOptimizer\Classes\Optimization\JS\Combine as CombineJS;
use AndikaMC\WebOptimizer\Classes\Optimization\CSS\Minify as CSSMinify;

class Engine
{
    public function Optimize($buffer, $options)
    {
        if (isset($options["optimize_css"]) && !empty($options["optimize_css"]))
        { // optimize css
            if (isset($options["combine_css"]) && !empty($options["combine_css"]))
            {
                $engine = CombineCSS::class;
                $buffer = $engine::combine($buffer, $options);
            }
            else
            {
                $engine = CSSMinify::class;
                $buffer = $engine::minify($buffer);    
            }
        }

        if (isset($options["optimize_js"]) && !empty($options["optimize_js"]))
        { // optimize js
            if (isset($options["combine_js"]) && !empty($options["combine_js"]))
            {
                $engine = CombineJS::class;
                $buffer = $engine::combine($buffer, $options);
            }
            else
            {
                $engine = CSSMinify::class;
                $buffer = $engine::minify($buffer);    
            }
        }

        if (isset($options["optimize_html"]) && !empty($options["optimize_html"]))
        { // optimize html
            $engine = HTMLMinify::class;
            $buffer = $engine::minify($buffer);
        }

        /**
         * 
         */
        if (isset($options["optimize_experimental"]) && !empty($options["optimize_experimental"]))
        { // optimize experimental
            $engine = new Experimental;
            $buffer = $engine->MinHTML($buffer);
        }

        return $buffer;
    }
}