<?php
/**
 * Main CSS optimization controller
 */
class MACP_CSS_Optimizer {
    private $extractor;
    private $minifier;
    private $storage;
    private $debug;

    public function __construct() {
        $this->extractor = new MACP_CSS_Extractor();
        $this->minifier = new MACP_CSS_Minifier();
        $this->storage = new MACP_Used_CSS_Storage();
        $this->debug = new MACP_Debug();
    }

    public function optimize($html) {
        if (!$this->should_process()) {
            $this->debug->log('Skipping CSS optimization - conditions not met');
            return $html;
        }

        try {
            $url = $this->get_current_url();
            $this->debug->log('Starting CSS optimization for URL: ' . $url);

            // Extract and process CSS files
            $css_files = $this->extractor->extract_css_files($html);
            $this->debug->log('Found CSS files:', $css_files);

            $used_selectors = $this->extractor->extract_used_selectors($html);
            $this->debug->log('Found used selectors: ' . count($used_selectors));

            $optimized_css = '';
            foreach ($css_files as $file) {
                $css_content = $this->get_css_content($file);
                if (!$css_content) continue;
                
                $optimized_css .= $this->minifier->remove_unused_css($css_content, $used_selectors);
            }

            if (!empty($optimized_css)) {
                $this->debug->log('Successfully optimized CSS');
                $this->storage->save($url, $optimized_css);
                return $this->replace_css($html, $optimized_css);
            }

            $this->debug->log('No CSS was optimized');
            return $html;

        } catch (Exception $e) {
            $this->debug->log('CSS optimization error: ' . $e->getMessage());
            return $html;
        }
    }

    private function get_css_content($url) {
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            $this->debug->log('Failed to fetch CSS: ' . $response->get_error_message());
            return false;
        }
        return wp_remote_retrieve_body($response);
    }

    private function should_process() {
        return get_option('macp_remove_unused_css', 0) && 
               !is_admin() && 
               !is_user_logged_in();
    }

    private function get_current_url() {
        return MACP_URL_Helper::get_current_url();
    }
  
  
  public function process_css($css_content, $html = '') {
    if (empty($css_content)) {
        return $css_content;
    }

    // Get settings
    $should_minify = get_option('macp_minify_css', 0);
    $should_remove_unused = get_option('macp_remove_unused_css', 0);

    // If both are enabled, minify first then remove unused
    if ($should_minify && $should_remove_unused) {
        $minifier = new MACP_CSS_Minifier();
        $css_content = $minifier->minify($css_content);

        $used_selectors = $this->extractor->extract_used_selectors($html);
        $css_content = $this->minifier->remove_unused_css($css_content, $used_selectors);

        return $css_content;
    }

    // If only minify is enabled
    if ($should_minify) {
        $minifier = new MACP_CSS_Minifier();
        return $minifier->minify($css_content);
    }

    // If only unused CSS removal is enabled
    if ($should_remove_unused) {
        $used_selectors = $this->extractor->extract_used_selectors($html);
        return $this->minifier->remove_unused_css($css_content, $used_selectors);
    }

    return $css_content;
}




    private function replace_css($html, $optimized_css) {
        // Remove original CSS links
        $html = preg_replace('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', '', $html);

        // Add optimized CSS
        $css_tag = sprintf(
            '<style id="macp-optimized-css">%s</style>',
            $optimized_css
        );

        return str_replace('</head>', $css_tag . '</head>', $html);
    }
}