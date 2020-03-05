<?php
namespace AndikaMC\WebOptimizer\Classes\Optimization\JS;

use AndikaMC\WebOptimizer\Classes\Optimization\HTML\Cracker;
use AndikaMC\WebOptimizer\Classes\Storage\Engine;
use AndikaMC\WebOptimizer\Classes\Optimization\Experimental;
use MatthiasMullie\Minify;

class Combine
{
    private static $AppEngine, $AppStorage, $AppOptions, $AppMinify, $UncombinedJS, $CrackedJS = [];

    public static function combine($buffer, $options)
    {
        self::$AppOptions = $options;
        self::$AppEngine  = new Cracker;

        /**
         * Crack HTML Tags
         */
        self::$CrackedJS = self::$AppEngine->CrackJSTags(
            self::$AppEngine->CrackHTML($buffer)
        );

        /**
         * Filter CSS Tags
         */
        foreach(self::$CrackedJS as $JSCrack)
        {
            self::$UncombinedJS[] = [
                "il"  => !empty($JSCrack["script_src"]),
                "ilx" => !empty($JSCrack["script_src_external"]),
                "is"  => !empty($JSCrack["script_js"]),
                "lh"  => (!empty($JSCrack["script_src"]) ? $JSCrack["script_src"] : ""),
                "sc"  => (!empty($JSCrack["script_js"]) ? $JSCrack["script_js"] : "")
            ];

            /**
             * Remove all original tags
             */
            $buffer = str_replace($JSCrack["all"], "", $buffer);
        }

        /**
         * Merge Uncombined CSS Content
         */
        $buffer = self::MergeUncombinedJS($buffer);

        /**
         * Return
         */
        return $buffer;
    }

    private static function MergeUncombinedJS($buffer)
    {
        self::$AppStorage = new Engine(self::$AppOptions);

        /**
         * Validate Cache File
         */
        if (isset(self::$AppOptions["cache_combine_js"]) && !empty(self::$AppOptions["cache_combine_js"]) && self::$AppStorage->CheckFile(self::$AppStorage->GenerateFilename($buffer, ".min.js", @self::$AppOptions["combine_js_path"])))
        {
            goto cached;
        }

        self::$AppStorage->InitFile(self::$AppStorage->GenerateFilename($buffer, ".min.js", @self::$AppOptions["combine_js_path"]));

        /**
         * Explode cracked css
         */
        foreach(self::$UncombinedJS as $UncombinedJS)
        {
            if ($UncombinedJS["il"])
            {
                if ($UncombinedJS["ilx"])
                {
                    self::$AppStorage->AddFile(file_get_contents($UncombinedJS["lh"]).";\n");
                }
                else
                {
                    if (preg_match('#((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i', $UncombinedJS["lh"]))
                    {
                        self::$AppStorage->AddFile(file_get_contents($UncombinedJS["lh"]).";\n");
                    }
                    else
                    {
                        self::$AppStorage->AddFile(file_get_contents(@self::$AppOptions["host"] . $UncombinedJS["lh"]).";\n");
                    }
                }
            }

            if ($UncombinedJS["is"])
            {
                /**
                 * More fixing bug remove comment
                 */
                self::$AppStorage->AddFile((new Experimental())->MinJS($UncombinedJS["sc"]).";\n");
            }
        }

        /**
         * Process Combined CSS
         */
        $file = self::$AppStorage->SaveFile();
        self::$AppMinify = new Minify\JS(self::$AppStorage->GetCacheDir() . @self::$AppOptions["combine_js_path"] . DIRECTORY_SEPARATOR . basename($file));
        self::$AppMinify->gzip(self::$AppStorage->GetCacheDir() . @self::$AppOptions["combine_js_path"] . DIRECTORY_SEPARATOR . basename($file));
        self::$AppMinify->minify(self::$AppStorage->GetCacheDir() . @self::$AppOptions["combine_js_path"] . DIRECTORY_SEPARATOR . basename($file));
        goto finals;

        /**
         * Cached file process
         */
        cached:
        $file = str_replace(self::$AppStorage->GetBaseDir(), "", self::$AppStorage->GetCacheDir() . self::$AppStorage->GenerateFilename($buffer, ".min.js", @self::$AppOptions["combine_js_path"]));
        $file = str_replace("\\", "/", $file);

        /**
         * Replace and append to <head> attribute
         */
        finals:
        $buffer = str_replace("</body>", "<script type=\"text/javascript\" src=\"" . self::$AppOptions["host"] . $file . "\">" . "</script></body>", $buffer);
        $buffer = str_replace("://", ":@@", $buffer);
        $buffer = str_replace("//", "/", $buffer);
        $buffer = str_replace(":@@", "://", $buffer);

        return $buffer;
    }
}