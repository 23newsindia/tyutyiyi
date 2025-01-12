<?php
/**
 * Handles the HTML processing pipeline
 */
class MACP_HTML_Processor {
    private $minifier;
    private $options;
    private $css_optimizer;
    
    // Add critical patterns property
    private $critical_patterns = [
        '@media', // Preserve all media queries for responsive design
        'display:', // Layout properties
        'flex',
        'grid',
        'width',
        'height',
        'margin',
        'padding',
        'position',
        '@supports', // Browser feature queries
        'min-width',
        'max-width',
        'min-height', 
        'max-height'
    ];

    public function __construct() {
        $this->minifier = new MACP_HTML_Minifier();
        $this->css_optimizer = new MACP_CSS_Optimizer();
        $this->options = [
            'minify_html' => get_option('macp_minify_html', 0),
            'minify_css' => get_option('macp_minify_css', 0),
            'minify_js' => get_option('macp_minify_js', 0),
            'remove_unused_css' => get_option('macp_remove_unused_css', 0)
        ];
    }

    public function process($html) {
        if (empty($html)) {
            return $html;
        }

        $should_minify = $this->options['minify_css'];
        $should_remove_unused = $this->options['remove_unused_css'];

        // Extract CSS links before processing
        $css_links = [];
        if ($should_minify || $should_remove_unused) {
            preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
            if (!empty($matches[0])) {
                $css_links = array_combine($matches[1], $matches[0]);
            }
        }

        // Process CSS
        if (!empty($css_links)) {
            foreach ($css_links as $url => $original_tag) {
                $processed_css = $this->process_css_file($url, $html);
                if ($processed_css) {
                    // Preserve critical CSS patterns
                    foreach ($this->critical_patterns as $pattern) {
                        if (preg_match_all('/' . preg_quote($pattern, '/') . '[^}]+\{[^}]+\}/s', $processed_css, $matches)) {
                            foreach ($matches[0] as $match) {
                                $processed_css .= "\n" . $match;
                            }
                        }
                    }
                    
                    // Create new style tag with processed CSS
                    $new_tag = "<style id=\"" . sanitize_title($url) . "\">" . $processed_css . "</style>";
                    $html = str_replace($original_tag, $new_tag, $html);
                }
            }
        }

        return $html;
    }

    private function is_responsive_rule($css_rule) {
        foreach ($this->critical_patterns as $pattern) {
            if (strpos($css_rule, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    private function process_css_file($url, $html) {
        $css_content = wp_remote_get($url);
        if (is_wp_error($css_content)) {
            return false;
        }
        
        $css_content = wp_remote_retrieve_body($css_content);
        if (empty($css_content)) {
            return false;
        }

        // Always preserve responsive/media query rules
        preg_match_all('/@media[^{]+\{([^{}]|{[^{}]*})*\}/i', $css_content, $media_matches);
        $responsive_css = implode("\n", $media_matches[0]);

        // Process remaining CSS
        $processed_css = $this->css_optimizer->optimize($css_content, $html);

        // Combine processed CSS with preserved responsive rules
        return $processed_css . "\n" . $responsive_css;
    }
}
