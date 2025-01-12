<?php
class MACP_Unused_CSS_Processor {
    private $css_extractor;
    private $safelist;

    public function __construct() {
        $this->css_extractor = new MACP_CSS_Extractor();
        $this->safelist = MACP_CSS_Config::get_safelist();
    }

    public function process($css_content, $html) {
        $used_selectors = $this->css_extractor->extract_used_selectors($html);
        return $this->filter_css($css_content, $used_selectors);
    }

    private function filter_css($css, $used_selectors) {
        $filtered = '';
        
        // Preserve @media queries and @font-face rules
        preg_match_all('/@(media|font-face)[^{]*{[^}]*}/', $css, $matches);
        if (!empty($matches[0])) {
            $filtered .= implode("\n", $matches[0]) . "\n";
        }
        
        // Split remaining CSS into rules
        preg_match_all('/([^{]+){[^}]*}/s', $css, $matches);
        
        foreach ($matches[0] as $rule) {
            if ($this->should_keep_rule($rule, $used_selectors)) {
                $filtered .= $rule . "\n";
            }
        }
        
        return $filtered;
    }

    private function should_keep_rule($rule, $used_selectors) {
        // Always keep @-rules
        if (strpos($rule, '@') === 0) {
            return true;
        }

        $selectors = explode(',', trim(preg_replace('/\s*{.*$/s', '', $rule)));
        
        foreach ($selectors as $selector) {
            $selector = trim($selector);
            
            // Keep if in safelist
            if ($this->is_safelisted($selector)) {
                return true;
            }
            
            // Keep if used in HTML
            if ($this->is_selector_used($selector, $used_selectors)) {
                return true;
            }

            // Keep Font Awesome related selectors
            if ($this->is_font_awesome_selector($selector)) {
                return true;
            }
        }
        
        return false;
    }

    private function is_safelisted($selector) {
        // Always keep essential selectors
        if (in_array($selector, ['html', 'body', '*']) || strpos($selector, '@') === 0) {
            return true;
        }

        foreach ($this->safelist as $pattern) {
            if (fnmatch($pattern, $selector)) {
                return true;
            }
        }

        return false;
    }

    private function is_font_awesome_selector($selector) {
        $fa_patterns = ['/\bfa-/', '/\bfas?\b/', '/\bfar\b/', '/\bfal\b/', '/\bfab\b/', '/\bfad\b/'];
        foreach ($fa_patterns as $pattern) {
            if (preg_match($pattern, $selector)) {
                return true;
            }
        }
        return false;
    }

    private function is_selector_used($selector, $used_selectors) {
        foreach ($used_selectors as $used_selector) {
            if (strpos($used_selector, $selector) !== false) {
                return true;
            }
        }
        return false;
    }
}
