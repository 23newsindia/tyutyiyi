<?php
class MACP_CSS_Config {
    public static function get_safelist() {
        $saved_safelist = get_option('macp_css_safelist', []);
        
        // Default WordPress classes that should never be removed
        $default_safelist = [
            // WordPress defaults
            'wp-*',
            'admin-bar*',
            'dashicons*',
            'menu-item*',
            'current-menu-item',
            'page-numbers',
            'post-*',
            'sticky',
            'bypostauthor',
            'wp-caption*',
            'gallery*',
            
            // Font Awesome
            'fa',
            'fa-*',
            'fas',
            'far',
            'fab',
            'fal',
            'fad',
            
            // Common responsive classes
            'container*',
            'row*',
            'col-*',
            'hidden-*',
            'visible-*',
            'd-*',
            'flex-*',
            'justify-*',
            'align-*',
            'order-*',
            'offset-*',
            'm-*',
            'p-*',
            'text-*',
            'bg-*',
            'w-*',
            'h-*',
            
            // Media query related classes
            '*-xs-*',
            '*-sm-*',
            '*-md-*',
            '*-lg-*',
            '*-xl-*'
        ];

        return array_merge($default_safelist, $saved_safelist);
    }

    public static function get_excluded_patterns() {
        $saved_patterns = get_option('macp_css_excluded_patterns', []);
        
        $default_patterns = [
            'admin-bar.min.css',
            'dashicons.min.css',
            'fontawesome',
            'font-awesome',
            'bootstrap'
        ];

        return array_merge($default_patterns, $saved_patterns);
    }

    public static function save_safelist($patterns) {
        $patterns = array_map('sanitize_text_field', $patterns);
        update_option('macp_css_safelist', array_filter($patterns));
    }

    public static function save_excluded_patterns($patterns) {
        $patterns = array_map('sanitize_text_field', $patterns);
        update_option('macp_css_excluded_patterns', array_filter($patterns));
    }
}
