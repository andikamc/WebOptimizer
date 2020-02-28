<?php
namespace AndikaMC\WebOptimizer\Functions;

class HtmlCracker
{
    protected $public_html = "";
    protected $parsed_html = [];

    public function __construct($html, $options = [])
    {
        $this->public_html = $html;
        $this->options = $options;
        return $this;
    }

    public function CrackHtml()
    {
        if (preg_match('/(?P<all>(?P<open_tag>\<html(?:\s+[^>]*?)?\>)(?P<contents>.*?)(?P<closing_tag>\<\/html\>))/uis', $this->public_html, $html_cracked))
        {
            $this->parsed_html = $html_cracked;
        }

        return $this;
    }

    public function CracsCssTag()
    {}

}