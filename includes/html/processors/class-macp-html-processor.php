<?php
/**
 * Handles the HTML processing pipeline
 */
class MACP_HTML_Processor {
    private $minifier;
    private $options;
    private $css_optimizer;

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
                // Create new style tag with processed CSS
                $new_tag = "<style id=\"" . sanitize_title($url) . "\">" . $processed_css . "</style>";
                $html = str_replace($original_tag, $new_tag, $html);
            }
        }
    }

    return $html;
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

        return $this->css_optimizer->optimize($css_content, $html);
    }
}

