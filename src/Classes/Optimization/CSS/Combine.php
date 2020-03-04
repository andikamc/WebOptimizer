<?php
namespace AndikaMC\WebOptimizer\Classes\Optimization\CSS;

use AndikaMC\WebOptimizer\Classes\Optimization\HTML\Cracker;
use AndikaMC\WebOptimizer\Classes\Storage\Engine;
use MatthiasMullie\Minify;

class Combine
{
    private static $AppEngine, $AppStorage, $AppOptions, $AppMinify, $UncombinedCSS, $CrackedCSS = [];

    public static function combine($buffer, $options)
    {
        self::$AppOptions = $options;
        self::$AppEngine  = new Cracker;

        /**
         * Crack HTML Tags
         */
        self::$CrackedCSS = self::$AppEngine->CrackCSSTags(
            self::$AppEngine->CrackHTML($buffer)
        );

        /**
         * Filter CSS Tags
         */
        foreach(self::$CrackedCSS as $CSSCrack)
        {
            self::$UncombinedCSS[] = [
                "il"  => !empty($CSSCrack["link_href"]),
                "ilx" => !empty($CSSCrack["link_href_external"]),
                "is"  => !empty($CSSCrack["style_css"]),
                "lh"  => (!empty($CSSCrack["link_href"]) ? $CSSCrack["link_href"] : ""),
                "sc"  => (!empty($CSSCrack["style_css"]) ? $CSSCrack["style_css"] : "")
            ];

            /**
             * Remove all original tags
             */
            $buffer = str_replace($CSSCrack["all"], "", $buffer);
        }

        /**
         * Merge Uncombined CSS Content
         */
        $buffer = self::MergeUncombinedCSS($buffer);

        /**
         * Return
         */
        return $buffer;
    }

    private static function MergeUncombinedCSS($buffer)
    {
        self::$AppStorage = new Engine(self::$AppOptions);

        /**
         * Validate Cache File
         */
        if (isset(self::$AppOptions["cache_combine_css"]) && !empty(self::$AppOptions["cache_combine_css"]) && self::$AppStorage->CheckFile(self::$AppStorage->GenerateFilename($buffer, ".min.css", @self::$AppOptions["combine_css_path"])))
        {
            goto cached;
        }

        self::$AppStorage->InitFile(self::$AppStorage->GenerateFilename($buffer, ".min.css", @self::$AppOptions["combine_css_path"]));

        /**
         * Explode cracked css
         */
        foreach(self::$UncombinedCSS as $UncombinedCSS)
        {
            if ($UncombinedCSS["is"])
            {
                self::$AppStorage->AddFile($UncombinedCSS["sc"]);
            }

            if ($UncombinedCSS["il"])
            {
                if ($UncombinedCSS["ilx"])
                {
                    self::$AppStorage->AddFile("@import url(".$UncombinedCSS["lh"].");");
                }
                else
                {
                    if (preg_match('#((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i', $UncombinedCSS["lh"]))
                    {
                        self::$AppStorage->AddFile("@import url(".$UncombinedCSS["lh"].");");
                    }
                    else
                    {
                        self::$AppStorage->AddFile("@import url(".@self::$AppOptions["host"] . $UncombinedCSS["lh"].");");
                    }
                }
            }
        }

        /**
         * Process Combined CSS
         */
        $file = self::$AppStorage->SaveFile();
        self::$AppMinify = new Minify\CSS(self::$AppStorage->GetCacheDir() . @self::$AppOptions["combine_css_path"] . DIRECTORY_SEPARATOR . basename($file));
        self::$AppMinify->gzip(self::$AppStorage->GetCacheDir() . @self::$AppOptions["combine_css_path"] . DIRECTORY_SEPARATOR . basename($file));
        self::$AppMinify->minify(self::$AppStorage->GetCacheDir() . @self::$AppOptions["combine_css_path"] . DIRECTORY_SEPARATOR . basename($file));
        goto finals;

        /**
         * Cached file process
         */
        cached:
        $file = str_replace(self::$AppStorage->GetBaseDir(), "", self::$AppStorage->GetCacheDir() . self::$AppStorage->GenerateFilename($buffer, ".min.css", @self::$AppOptions["combine_css_path"]));
        $file = str_replace("\\", "/", $file);

        /**
         * Replace and append to <head> attribute
         */
        finals:
        $buffer = str_replace("</head>", "<link rel=\"stylesheet\" href=\"" . self::$AppOptions["host"] . $file . "\">" . "</head>", $buffer);
        $buffer = str_replace("://", ":@@", $buffer);
        $buffer = str_replace("//", "/", $buffer);
        $buffer = str_replace(":@@", "://", $buffer);

        return $buffer;
    }
}