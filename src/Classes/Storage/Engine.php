<?php
namespace AndikaMC\WebOptimizer\Classes\Storage;

use AndikaMC\WebOptimizer\Classes\Exception\Exception;

class Engine
{
    private $options = [];

    public function __construct($options)
    {
        $this->options   = $options;
        $this->base_dir  = $options["cache_directory"];
        $this->cache_dir = trim($this->base_dir . DIRECTORY_SEPARATOR);

        /**
         * Validate Cache Directory
         */
        $this->ValidateFolder($this->cache_dir);
    }

    private function ValidateFolder($folder)
    {
        if (!is_dir($folder))
        {
            if (!@mkdir($folder))
            {
                $error = error_get_last();
                throw new Exception("".@$error['message'].". Failed to create cache folder (".$folder."), please create manualy.");
            }
        }
    }

    public function GetBaseDir()
    {
        return $this->base_dir;
    }

    public function GetCacheDir()
    {
        return $this->cache_dir;
    }

    public function InitFile($filename, $filecontent = "")
    {
        $this->temp_file = $this->cache_dir . $filename;

        /**
         * Validate folder
         */
        $this->ValidateFolder(str_replace(basename($this->cache_dir . $filename), "", $this->cache_dir . $filename));

        return file_put_contents($this->cache_dir . $filename, $filecontent);
    }

    public function AddFile($filecontent)
    {
        return file_put_contents($this->temp_file, $filecontent, FILE_APPEND);
    }

    public function CheckFile($filename)
    {
        return file_exists($this->cache_dir . $filename);
    }

    public function SaveFile()
    {
        $filename = str_replace($this->base_dir, "", $this->temp_file);
        $filename = str_replace("\\", "/", $filename);
        $this->temp_file = NULL;
        return $filename;
    }

    public function GenerateFilename($name, $extension = ".file", $prefix = "")
    {
        $name = $prefix . DIRECTORY_SEPARATOR . @$this->options["cache_prefix"] . hash("crc32", json_encode((string) $name)) . $extension;
        return $name;
    }

}