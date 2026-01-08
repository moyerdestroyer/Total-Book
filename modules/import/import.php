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
        // Import page will be implemented in PHP
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Import Book', 'the-total-book-project') . '</h1>';
        echo '<p>' . esc_html__('Import functionality will be available here.', 'the-total-book-project') . '</p>';
        echo '</div>';
    }
}
new TTBP_Import();

