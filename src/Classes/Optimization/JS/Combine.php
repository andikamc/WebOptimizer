<?php
namespace AndikaMC\WebOptimizer\Classes\Optimization\Js;

use AndikaMC\WebOptimizer\Classes\Optimization\HTML\Cracker;
use Exception;
use MatthiasMullie\Minify;

class Combine
{
    private static $app_options = [];
    private static $uncombined_js = [];
    private static $combined_js_url = "";

    public static function combine($buffer, $options = [])
    {
        self::$app_options = $options;
        $engine = new Cracker;

        $cracked_js = $engine->CrackJSTags($engine->CrackHTML($buffer));

        foreach($cracked_js as $js_crack)
        {
            if (empty($js_crack["exclude"]))
            {
                self::$uncombined_js[] = [
                    "is_link" => !empty($js_crack["script_src"]),
                    "is_link_external" => !empty($js_crack["script_src_external"]),
                    "is_js" => !empty($js_crack["script_js"]),
                    "link_src" => (!empty($js_crack["script_src"]) ? $js_crack["script_src"] : NULL),
                    "script_js" => (!empty($js_crack["script_js"]) ? $js_crack["script_js"] : NULL),
                ];
                $buffer = str_replace($js_crack["all"], NULL, $buffer);
            }
        }

        self::$combined_js_url = self::MergeUncombinedJS(self::$uncombined_js);
        $buffer = str_replace("</body>", "<script type=\"text/javascript\" src=\"".self::URLHost(self::$combined_js_url)."\"></script></body>", $buffer);

        return $buffer;
    }

    private static function MergeUncombinedJS(array $js_lists)
    {
        $save_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"])."/".(isset(self::$app_options["combine_js_path"]) ? self::$app_options["combine_js_path"] : "/")).DIRECTORY_SEPARATOR;

        if (!is_dir($save_path))
        { // cek direktori
            throw new Exception("Combined JS Path not found");
        }

        $combined_js_name = "com-".hash("crc32", json_encode($js_lists)).".min.js";
        $sourcePath = $save_path.$combined_js_name;

        if (isset(self::$app_options["cache_combine_js"]) && !empty(self::$app_options["cache_combine_js"]))
        {
            if (file_exists($sourcePath))
            {
                return str_replace( "\\", "/", str_replace( realpath(dirname($_SERVER["SCRIPT_FILENAME"])), NULL, $sourcePath ) );
            }
        }
        else
        {
            file_put_contents($sourcePath, "");
            $minifier = new Minify\JS($sourcePath);    
        }

        foreach($js_lists as $js_cracked)
        {
            if ($js_cracked["is_link"])
            {
                if ($js_cracked["is_link_external"])
                {
                    $minifier->add(file_get_contents($js_cracked["link_src"]));
                }
                else
                {
                    if (is_readable($save_path.$js_cracked["link_src"]))
                    {
                        $minifier->add(file_get_contents($save_path.$js_cracked["link_src"]));
                    }
                }
            }
        }

        foreach($js_lists as $js_cracked)
        {
            if ($js_cracked["is_js"])
            {
                $minifier->add($js_cracked["script_js"] . ";\n");
            }
        }

        //
        $minifier->gzip($sourcePath);
        $minifier->minify($sourcePath);

        $_rpath = str_replace( realpath(dirname($_SERVER["SCRIPT_FILENAME"])), NULL, $sourcePath );
        
        return str_replace( "\\", "/", $_rpath );
    }

    private static function URLHost($asset)
    {
        return $_SERVER["REQUEST_SCHEME"]."://".str_replace("//", "/", $_SERVER["HTTP_HOST"].str_replace(basename($_SERVER["SCRIPT_NAME"]), "", $_SERVER["SCRIPT_NAME"]).$asset);
    }

}