<?php
namespace AndikaMC\WebOptimizer\Classes\Optimization\HTML;

class Cracker
{
    private $regex_css_exclusions = [];
    private $built_in_regex_css_exclusions = [];
    private static $static;

    /**
     * 
     */
    public function CrackHTML($html)
    {
        if (!($html = (string) $html))
        {
            return []; // Nothing to do.
        }

        if (preg_match('/(?P<all>(?P<open_tag>\<html(?:\s+[^>]*?)?\>)(?P<contents>.*?)(?P<closing_tag>\<\/html\>))/uis', $html, $cracked_html))
        {
            return $cracked_html;
        }

        return [];
    }

    public function CrackCSSTags(array $cracked_html)
    {
        $css_tag_frags = [];

        if (!$cracked_html)
        {
            goto finale; // Nothing to do.
        }
        $regex = '/(?P<all>'.// Entire match.
                 '(?P<if_open_tag>\<\![^[>]*?\[if\W[^\]]*?\][^>]*?\>\s*)?'.
                 '(?:(?P<link_self_closing_tag>\<link(?:\s+[^>]*?)?\>)'.// Or a <style></style> tag.
                 '|(?P<style_open_tag>\<style(?:\s+[^>]*?)?\>)(?P<style_css>.*?)(?P<style_closing_tag>\<\/style\>))'.
                 '(?P<if_closing_tag>\s*\<\![^[>]*?\[endif\][^>]*?\>)?'.
                 ')/uis'; // Dot matches line breaks.

        if (!empty($cracked_html['contents']) && preg_match_all($regex, $cracked_html['contents'], $_tag_frags, PREG_SET_ORDER)) {
            foreach ($_tag_frags as $_tag_frag) {
                $_link_href = $_style_css = $_media = ''; // Initialize.

                if (($_link_href = $this->getLinkCssHref($_tag_frag, true))) {
                    $_media = $this->getLinkCssMedia($_tag_frag, false);
                } elseif (($_style_css = $this->getStyleCss($_tag_frag, true))) {
                    $_media = $this->getStyleCssMedia($_tag_frag, false);
                }
                if ($_link_href || $_style_css) {
                    $css_tag_frags[] = [
                        'all' => $_tag_frag['all'],

                        'if_open_tag'    => isset($_tag_frag['if_open_tag']) ? $_tag_frag['if_open_tag'] : '',
                        'if_closing_tag' => isset($_tag_frag['if_closing_tag']) ? $_tag_frag['if_closing_tag'] : '',

                        'link_self_closing_tag' => isset($_tag_frag['link_self_closing_tag']) ? $_tag_frag['link_self_closing_tag'] : '',
                        'link_href_external'    => ($_link_href) ? $this->isUrlExternal($_link_href) : false,
                        'link_href'             => $_link_href, // This could also be empty.

                        'style_open_tag'    => isset($_tag_frag['style_open_tag']) ? $_tag_frag['style_open_tag'] : '',
                        'style_css'         => $_style_css, // This could also be empty.
                        'style_closing_tag' => isset($_tag_frag['style_closing_tag']) ? $_tag_frag['style_closing_tag'] : '',

                        'media' => $_media ? $_media : 'all', // Default value.

                        'exclude' => false, // Default value.
                    ];
                    $_tag_frag_r = &$css_tag_frags[count($css_tag_frags) - 1];

                    if ($_tag_frag_r['if_open_tag'] || $_tag_frag_r['if_closing_tag']) {
                        $_tag_frag_r['exclude'] = true;
                    } elseif ($_tag_frag_r['link_href'] && $_tag_frag_r['link_href_external'] && isset($this->options['compress_combine_remote_css_js']) && !$this->options['compress_combine_remote_css_js']) {
                        $_tag_frag_r['exclude'] = true;
                    } elseif ($this->regex_css_exclusions && preg_match($this->regex_css_exclusions, $_tag_frag_r['link_self_closing_tag'].' '.$_tag_frag_r['style_open_tag'].' '.$_tag_frag_r['style_css'])) {
                        $_tag_frag_r['exclude'] = true;
                    } elseif ($this->built_in_regex_css_exclusions && preg_match($this->built_in_regex_css_exclusions, $_tag_frag_r['link_self_closing_tag'].' '.$_tag_frag_r['style_open_tag'].' '.$_tag_frag_r['style_css'])) {
                        $_tag_frag_r['exclude'] = true;
                    }
                }
            }
        }

        finale: // Target point; finale/return value.
        return $css_tag_frags;
    }

    protected function currentUrlHost()
    {
        if (isset(static::$static[__FUNCTION__])) {
            return static::$static[__FUNCTION__];
        }
        if (!empty($this->options['current_url_host'])) {
            return static::$static[__FUNCTION__] = $this->nUrlHost($this->options['current_url_host']);
        }
        if (empty($_SERVER['HTTP_HOST'])) {
            throw new \Exception('Missing required `$_SERVER[\'HTTP_HOST\']`.');
        }
        return static::$static[__FUNCTION__] = $this->nUrlHost($_SERVER['HTTP_HOST']);
    }

    protected function nUrlHost($host)
    {
        if (!($host = (string) $host)) {
            return $host; // Nothing to do.
        }
        return mb_strtolower($host);
    }

    protected function isUrlExternal($url_uri_query_fragment)
    {
        if (mb_strpos($url_uri_query_fragment, '//') === false) {
            return false; // Relative.
        }
        return mb_stripos($url_uri_query_fragment, '//'.$this->currentUrlHost()) === false;
    }

    protected function nUrlAmps($url_uri_query_fragment)
    {
        if (!($url_uri_query_fragment = (string) $url_uri_query_fragment)) {
            return $url_uri_query_fragment; // Nothing to do.
        }
        if (mb_strpos($url_uri_query_fragment, '&') === false) {
            return $url_uri_query_fragment; // Nothing to do.
        }
        return preg_replace('/&amp;|&#0*38;|&#[xX]0*26;/u', '&', $url_uri_query_fragment);
    }

    protected function isLinkTagFragCss(array $tag_frag)
    {
        if (empty($tag_frag['link_self_closing_tag'])) {
            return false; // Nope; missing tag.
        }
        $type = $rel = ''; // Initialize.

        if (mb_stripos($tag_frag['link_self_closing_tag'], 'type') !== 0) {
            if (preg_match('/\stype\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['link_self_closing_tag'], $_m)) {
                $type = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if (mb_stripos($tag_frag['link_self_closing_tag'], 'rel') !== 0) {
            if (preg_match('/\srel\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['link_self_closing_tag'], $_m)) {
                $rel = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if ($type && mb_stripos($type, 'css') === false) {
            return false; // Not CSS.
        }
        if ($rel && mb_stripos($rel, 'stylesheet') === false) {
            return false; // Not CSS.
        }
        return true; // Yes, this is CSS.
    }

    protected function isStyleTagFragCss(array $tag_frag)
    {
        if (empty($tag_frag['style_open_tag']) || empty($tag_frag['style_closing_tag'])) {
            return false; // Nope; missing open|closing tag.
        }
        $type = ''; // Initialize.

        if (mb_stripos($tag_frag['style_open_tag'], 'type') !== 0) {
            if (preg_match('/\stype\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['style_open_tag'], $_m)) {
                $type = $_m['value'];
            }
        } // unset($_m); // Just a little housekeeping.

        if ($type && mb_stripos($type, 'css') === false) {
            return false; // Not CSS.
        }
        return true; // Yes, this is CSS.
    }

    protected function getStyleCss(array $tag_frag, $test_for_css = true)
    {
        if (empty($tag_frag['style_css'])) {
            return ''; // Not possible; no CSS code.
        }
        if ($test_for_css && !$this->isStyleTagFragCss($tag_frag)) {
            return ''; // This tag does not contain CSS.
        }
        return trim($tag_frag['style_css']); // CSS code.
    }

    protected function getStyleCssMedia(array $tag_frag, $test_for_css = true)
    {
        if ($test_for_css && !$this->isStyleTagFragCss($tag_frag)) {
            return ''; // This tag does not contain CSS.
        }
        if (preg_match('/\smedia\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['style_open_tag'], $_m)) {
            return trim(mb_strtolower($_m['value']));
        } // unset($_m); // Just a little housekeeping.

        return ''; // Unable to find a `media` attribute value.
    }

    protected function getLinkCssHref(array $tag_frag, $test_for_css = true)
    {
        if ($test_for_css && !$this->isLinkTagFragCss($tag_frag)) {
            return ''; // This tag does not contain CSS.
        }
        if (preg_match('/\shref\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['link_self_closing_tag'], $_m)) {
            return trim($this->nUrlAmps($_m['value']));
        } // unset($_m); // Just a little housekeeping.

        return ''; // Unable to find an `href` attribute value.
    }

    protected function getLinkCssMedia(array $tag_frag, $test_for_css = true)
    {
        if ($test_for_css && !$this->isLinkTagFragCss($tag_frag)) {
            return ''; // This tag does not contain CSS.
        }
        if (preg_match('/\smedia\s*\=\s*(["\'])(?P<value>.+?)\\1/ui', $tag_frag['link_self_closing_tag'], $_m)) {
            return trim(mb_strtolower($_m['value']));
        } // unset($_m); // Just a little housekeeping.

        return ''; // Unable to find a `media` attribute value.
    }
}