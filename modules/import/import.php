<?php

if (!defined('ABSPATH')) {
    exit;
}

// Load block converter
require_once plugin_dir_path(__FILE__) . 'block-converter.php';

Class TTBP_Import {
    public function __construct() {
        // Add import submenu item
        add_action('admin_menu', array($this, 'ttbp_add_import_submenu'));
        add_action('admin_enqueue_scripts', function() {
            wp_enqueue_script('ttbp-import-scripts', plugin_dir_url(__FILE__) . '../../dist/book-importer.min.js', array('react', 'react-dom'), '1.0.0', true);
            wp_enqueue_style('ttbp-import-styles', plugin_dir_url(__FILE__) . '../../dist/book-importer.min.css', array(), '1.0.0');
        });
    }
    public function ttbp_add_import_submenu() {
        add_submenu_page(
            'edit.php?post_type=ttbp-book',
            __('Import', 'the-total-book-project'),
            __('Import', 'the-total-book-project'),
            'manage_options',
            'ttbp-import',
            array($this, 'ttbp_render_import_page')
        );
    }
    public function ttbp_render_import_page() {
        echo '<div id="ttbp-import-page"></div>';
    }
}
new TTBP_Import();

