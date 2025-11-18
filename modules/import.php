<?php

if (!defined('ABSPATH')) {
    exit;
}

Class TTBP_Import {
    public function __construct() {
        //"Import E-pub" submenu item
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
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        </div>
        <?php
    }
}

new TTBP_Import();