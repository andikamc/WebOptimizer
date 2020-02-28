<?php
namespace AndikaMC\WebOptimizer\Classes\Optimization\CSS;

use AndikaMC\WebOptimizer\Classes\Optimization\HTML\Cracker;
use Exception;
use MatthiasMullie\Minify;

class Combine
{
    private static $app_options = [];
    private static $uncombined_css = [];
    private static $combined_css_url = "";

    public static function combine($buffer, $options)
    {
        self::$app_options = $options;
        $engine = new Cracker;

        $cracked_css = $engine->CrackCSSTags($engine->CrackHTML($buffer));
        foreach($cracked_css as $css_crack)
        {
            self::$uncombined_css[] = [
                "is_link" => !empty($css_crack["link_href"]),
                "is_link_external" => !empty($css_crack["link_href_external"]),
                "is_style" => !empty($css_crack["style_css"]),
                "link_href" => (!empty($css_crack["link_href"]) ? $css_crack["link_href"] : NULL),
                "style_css" => (!empty($css_crack["style_css"]) ? $css_crack["style_css"] : NULL),
            ];

            $buffer = str_replace($css_crack["all"], NULL, $buffer);
        }

        self::$combined_css_url = self::MergeUncombinedCSS(self::$uncombined_css);
        $buffer = str_replace("</head>", "<link rel=\"stylesheet\" href=\"".self::URLHost(self::$combined_css_url)."\"></head>", $buffer);

        return $buffer;
    }

    private static function MergeUncombinedCSS(array $css_lists)
    {
        $save_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"])."/".(isset(self::$app_options["combine_css_path"]) ? self::$app_options["combine_css_path"] : "/")).DIRECTORY_SEPARATOR;

        if (!is_dir($save_path))
        { // cek direktori
            throw new Exception("Combined CSS Path not found");
        }

        //
        $combined_css_name = "com-".hash("crc32", json_encode($css_lists)).".min.css";
        $sourcePath = $save_path.$combined_css_name;

        if (isset(self::$app_options["cache_combine_css"]) && !empty(self::$app_options["cache_combine_css"]))
        {
            if (file_exists($sourcePath))
            {
                return str_replace( "\\", "/", str_replace( realpath(dirname($_SERVER["SCRIPT_FILENAME"])), NULL, $sourcePath ) );
            }
        }

        file_put_contents($sourcePath, "");
        $minifier = new Minify\CSS($sourcePath);    

        //
        foreach($css_lists as $css_cracked)
        {
            if ($css_cracked["is_style"])
            {
                $minifier->add($css_cracked["style_css"]);
            }

            if ($css_cracked["is_link"])
            {
                if ($css_cracked["is_link_external"])
                {
                    $minifier->add("@import url(".$css_cracked["link_href"].")");
                }
                else
                {
                    if (is_readable(str_replace(self::URLHost(""), "", realpath(dirname($_SERVER["SCRIPT_FILENAME"])).DIRECTORY_SEPARATOR.$css_cracked["link_href"])))
                    {
                        $minifier->add(str_replace(self::URLHost(""), "", realpath(dirname($_SERVER["SCRIPT_FILENAME"])).DIRECTORY_SEPARATOR.$css_cracked["link_href"]));
                    }    
                }
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