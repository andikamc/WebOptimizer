<?php
namespace AndikaMC\WebOptimizer\Functions;

class HtmlCracker
{
    protected $CssTagsCrack = [];
    protected $Regex = [
        "CssTag" => '/(?P<all>'.// Entire match.
                    '(?P<if_open_tag>\<\![^[>]*?\[if\W[^\]]*?\][^>]*?\>\s*)?'.
                    '(?:(?P<link_self_closing_tag>\<link(?:\s+[^>]*?)?\>)'.// Or a <style></style> tag.
                    '|(?P<style_open_tag>\<style(?:\s+[^>]*?)?\>)(?P<style_css>.*?)(?P<style_closing_tag>\<\/style\>))'.
                    '(?P<if_closing_tag>\s*\<\![^[>]*?\[endif\][^>]*?\>)?'.
                    ')/uis',
    ];

    public function CrackHtml($html)
    {
        if (!($html = (string) $html))
        {
            return []; // Nothing to do.
        }

        if (preg_match('/(?P<all>(?P<open_tag>\<html(?:\s+[^>]*?)?\>)(?P<contents>.*?)(?P<closing_tag>\<\/html\>))/uis', $html, $html_cracked))
        {
            return $this->RemoveNumericKeysDeep($html_cracked);
        }

        return [];
    }

    public function CrackHeadHtml($html)
    {
        if (!($html = (string) $html))
        {
            return []; // Nothing to do.
        }

        if (preg_match('/(?P<all>(?P<open_tag>\<head(?:\s+[^>]*?)?\>)(?P<contents>.*?)(?P<closing_tag>\<\/head\>))/uis', $html, $head_cracked))
        {
            return $this->RemoveNumericKeysDeep($head_cracked);
        }

        return [];
    }

    public function CrackCssTag(array $html_cracked)
    {
        if (!empty($html_cracked['contents']) && preg_match_all($this->Regex["CssTag"], $html_cracked['contents'], $_tags_cracked, PREG_SET_ORDER))
        {
            foreach($_tags_cracked as $_tag_cracked)
            {
                if (($_link_href = $this->LinkCssHref($_tag_cracked, true)))
                {
                    $_media = $this->LinkCssMedia($_tag_cracked, false);
                }
                elseif (($_style_css = $this->StyleCss($_tag_cracked, true)))
                {
                    $_media = $this->StyleCssMedia($_tag_cracked, false);
                }

                if ($_link_href || $_style_css)
                {
                    $this->CssTagsCrack[] = [
                        'all' => $_tag_cracked['all'],
                        'if_open_tag'    => isset($_tag_cracked['if_open_tag']) ? $_tag_cracked['if_open_tag'] : '',
                        'if_closing_tag' => isset($_tag_cracked['if_closing_tag']) ? $_tag_cracked['if_closing_tag'] : '',
                        'link_self_closing_tag' => isset($_tag_cracked['link_self_closing_tag']) ? $_tag_cracked['link_self_closing_tag'] : '',
                        'link_href_external'    => ($_link_href) ? $this->UrlExternal($_link_href) : false,
                        'link_href'             => $_link_href, // This could also be empty.
                        'style_open_tag'    => isset($_tag_cracked['style_open_tag']) ? $_tag_cracked['style_open_tag'] : '',
                        'style_css'         => $_style_css, // This could also be empty.
                        'style_closing_tag' => isset($_tag_cracked['style_closing_tag']) ? $_tag_cracked['style_closing_tag'] : '',
                        'media' => $_media ? $_media : 'all', // Default value.
                        'exclude' => false, // Default value.
                    ];
                    $_tag_cracked_r = &$this->CssTagsCrack[count($this->CssTagsCrack) - 1];

                    if ($_tag_cracked_r['if_open_tag'] || $_tag_cracked_r['if_closing_tag'])
                    {
                        $_tag_cracked_r['exclude'] = true;
                    }
                    else
                    if ($_tag_cracked_r['link_href'] && $_tag_cracked_r['link_href_external'] && isset($this->options['compress_combine_remote_css_js']) && !$this->options['compress_combine_remote_css_js'])
                    {
                        $_tag_cracked_r['exclude'] = true;
                    }
                    else
                    if (isset($this->regex_css_exclusions) && preg_match($this->regex_css_exclusions, $_tag_cracked_r['link_self_closing_tag'].' '.$_tag_cracked_r['style_open_tag'].' '.$_tag_cracked_r['style_css']))
                    {
                        $_tag_cracked_r['exclude'] = true;
                    }
                    else
                    if (isset($this->built_in_regex_css_exclusions) && preg_match($this->built_in_regex_css_exclusions, $_tag_cracked_r['link_self_closing_tag'].' '.$_tag_cracked_r['style_open_tag'].' '.$_tag_cracked_r['style_css']))
                    {
                        $_tag_cracked_r['exclude'] = true;
                    }
                }
            }

            return $this->CssTagsCrack;
        }
    }

    public function GenerateMetaCode($meta)
    {
        return sha1(md5($meta));
    }

    public function CombineCatchedStyles(array $styles)
    {
        $_style = "";
        foreach($styles as $style)
        {
            $_style .= $style . "\n\n";
        }

        return $_style;
    }

    /**
     * 
     */
    protected function nUrlAmps($url_uri_query_crack)
    {
        if (!($url_uri_query_crack = (string) $url_uri_query_crack))
        {
            return $url_uri_query_crack;
        }

        if (mb_strpos($url_uri_query_crack, '&') === false)
        {
            return $url_uri_query_crack;
        }

        return preg_replace('/&amp;|&#0*38;|&#[xX]0*26;/u', '&', $url_uri_query_crack);
    }

    protected function UrlExternal($url_uri_query_crack)
    {
        if (mb_strpos($url_uri_query_crack, '//') === false)
        {
            return false; // Relative.
        }

        return mb_stripos($url_uri_query_crack, '//'.$this->UrlHost()) === false;
    }

    protected function UrlHost()
    {
        if (empty($_SERVER['HTTP_HOST']))
        {
            throw new \Exception('Missing required `$_SERVER[\'HTTP_HOST\']`.');
        }

        return $_SERVER['HTTP_HOST'];
    }

    protected function UrlSsl()
    {
        $SSL = false;
        if (!empty($_SERVER['SERVER_PORT']))
        {
            if ((int) $_SERVER['SERVER_PORT'] === 443)
            {
                return $SSL = true;
            }
        }
        if (!empty($_SERVER['HTTPS']))
        {
            if (filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN))
            {
                return $SSL = true;
            }
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
        {
            if (strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0)
            {
                return $SSL = true;
            }
        }

        return $SSL = false;
    }

    protected function UrlScheme()
    {
        return ($this->UrlSsl()) ? 'https' : 'http';
    }

    protected function StyleCssMedia(array $tag_crack, $test_for_css = true)
    {
        if ($test_for_css && !$this->StyleTagCrackCss($tag_crack))
        {
            return '';
        }

        if (preg_match('/\smedia\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_crack['style_open_tag'], $_m))
        {
            return trim(mb_strtolower($_m['value']));
        }

        return '';
    }

    protected function StyleCss(array $tag_crack, $test_for_css = true)
    {
        if (empty($tag_crack['style_css']))
        {
            return ''; // Not possible; no CSS code.
        }

        if ($test_for_css && !$this->StyleTagCrackCss($tag_crack))
        {
            return ''; // This tag does not contain CSS.
        }

        return trim($tag_crack['style_css']); // CSS code.
    }

    protected function LinkCssMedia(array $tag_crack, $test_for_css = true)
    {
        if ($test_for_css && !$this->LinkTagCrackCss($tag_crack))
        {
            return '';
        }

        if (preg_match('/\smedia\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_crack['link_self_closing_tag'], $_m)) {
            return trim(mb_strtolower($_m['value']));
        }

        return '';
    }

    protected function LinkCssHref(array $tag_crack, $test_for_css = true)
    {
        if ($test_for_css && !$this->LinkTagCrackCss($tag_crack))
        {
            return '';
        }

        if (preg_match('/\shref\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_crack['link_self_closing_tag'], $_m)) {
            return trim($this->nUrlAmps($_m['value']));
        }

        return '';
    }

    protected function StyleTagCrackCss(array $tag_crack)
    {
        if (empty($tag_crack['style_open_tag']) || empty($tag_crack['style_closing_tag']))
        {
            return false;
        }

        if (mb_stripos($tag_crack['style_open_tag'], 'type') !== 0)
        {
            if (preg_match('/\stype\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_crack['style_open_tag'], $_m))
            {
                $type = $_m['value'];
            }
        }

        if (isset($type) && mb_stripos($type, 'css') === false)
        {
            return false;
        }

        return true;
    }

    protected function LinkTagCrackCss(array $tag_crack)
    {
        if (empty($tag_crack['link_self_closing_tag']))
        {
            return false; // Nope; missing tag.
        }

        if (mb_stripos($tag_crack['link_self_closing_tag'], 'type') !== 0)
        {
            if (preg_match('/\stype\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_crack['link_self_closing_tag'], $_m))
            {
                $type = $_m['value'];
            }
        }

        if (mb_stripos($tag_crack['link_self_closing_tag'], 'rel') !== 0)
        {
            if (preg_match('/\srel\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_crack['link_self_closing_tag'], $_m))
            {
                $rel = $_m['value'];
            }
        }

        if ($type && mb_stripos($type, 'css') === false)
        {
            return false;
        }

        if ($rel && mb_stripos($rel, 'stylesheet') === false)
        {
            return false;
        }

        return true;
    }

    protected function NormalizeDirSeparator($dir_file, $allow_trailing_slash = false)
    {
        if (($dir_file = (string) $dir_file) === '')
        {
            return $dir_file;
        }

        if (mb_strpos($dir_file, '://' !== false))
            {
            if (preg_match('/^(?P<stream_wrapper>[a-z0-9]+)\:\/\//ui', $dir_file, $stream_wrapper))
            {
                $dir_file = preg_replace('/^(?P<stream_wrapper>[a-z0-9]+)\:\/\//ui', '', $dir_file);
            }

            if (mb_strpos($dir_file, ':' !== false))
            {
                if (preg_match('/^(?P<drive_letter>[a-z])\:[\/\\\\]/ui', $dir_file))
                {
                    $dir_file = preg_replace_callback('/^(?P<drive_letter>[a-z])\:[\/\\\\]/ui', create_function('$m', 'return mb_strtoupper($m[0]);'), $dir_file);
                }

                $dir_file = preg_replace('/\/+/u', '/', str_replace([DIRECTORY_SEPARATOR, '\\', '/'], '/', $dir_file));
            }
            $dir_file = ($allow_trailing_slash) ? $dir_file : rtrim($dir_file, '/');
        }

        if (!empty($stream_wrapper[0]))
        {
            $dir_file = mb_strtolower($stream_wrapper[0]).$dir_file;
        }

        return $dir_file;
    }

    protected function StripUtf8Bom($string)
    {
        if (!($string = (string) $string))
        {
            return $string;
        }

        return preg_replace('/^\xEF\xBB\xBF/u', '', $string);
    }

    protected function RemoveNumericKeysDeep(array $array, $___recursion = false)
    {
        foreach ($array as $_key => &$_value)
        {
            if (is_numeric($_key))
            {
                unset($array[$_key]);
            }
            elseif (is_array($_value))
            {
                $_value = $this->removeNumericKeysDeep($_value, true);
            }
        }

        return $array;
    }

}