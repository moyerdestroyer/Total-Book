<?php
/*
 * Settings Submodule
 */

 if (!defined('ABSPATH')) {
    exit;
}

Class TTBP_Settings {
    private $options;
    private $option_name = 'ttbp_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'ttbp_add_settings_page'));
        add_action('admin_init', array($this, 'ttbp_register_settings'));
        $this->options = get_option($this->option_name, array(
            'template' => 'default',
            'show_meta' => true,
            'show_toc' => true,
            'disable_auto_copyright' => false
        ));
    }

    public function ttbp_add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=ttbp-book',
            __('Total Book Settings', 'the-total-book-project'),
            __('Settings', 'the-total-book-project'),
            'manage_options',
            'ttbp-settings',
            array($this, 'ttbp_render_settings_page')
        );
    }

    public function ttbp_register_settings() {
        register_setting(
            'ttbp_settings',
            $this->option_name,
            array($this, 'ttbp_sanitize_settings')
        );

        add_settings_section(
            'ttbp_general',
            __('General Settings', 'the-total-book-project'),
            array($this, 'ttbp_render_section_info'),
            'ttbp-settings'
        );

        add_settings_field(
            'template',
            __('Book Template', 'the-total-book-project'),
            array($this, 'ttbp_render_template_field'),
            'ttbp-settings',
            'ttbp_general'
        );

        add_settings_field(
            'show_meta',
            __('Display Options', 'the-total-book-project'),
            array($this, 'ttbp_render_display_options'),
            'ttbp-settings',
            'ttbp_general'
        );
    }

    public function ttbp_render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('ttbp_settings');
                do_settings_sections('ttbp-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function ttbp_render_section_info() {
        echo '<p>' . esc_html__('Configure how your books are displayed on the front end.', 'the-total-book-project') . '</p>';
    }

    public function ttbp_render_template_field() {
        $templates = $this->get_available_templates();
        $current = isset($this->options['template']) ? $this->options['template'] : 'default';
        ?>
        <select name="<?php echo esc_attr($this->option_name); ?>[template]" id="template">
            <?php foreach ($templates as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($current, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the template to use for displaying books.', 'the-total-book-project'); ?>
        </p>
        <?php
    }

    public function ttbp_render_display_options() {
        $show_meta = isset($this->options['show_meta']) ? $this->options['show_meta'] : true;
        $show_toc = isset($this->options['show_toc']) ? $this->options['show_toc'] : true;
        $disable_auto_copyright = isset($this->options['disable_auto_copyright']) ? $this->options['disable_auto_copyright'] : false;
        ?>
        <fieldset>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[show_meta]" value="1" <?php checked($show_meta); ?>>
                <?php esc_html_e('Show book metadata (author, ISBN, etc.)', 'the-total-book-project'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[show_toc]" value="1" <?php checked($show_toc); ?>>
                <?php esc_html_e('Show table of contents', 'the-total-book-project'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[disable_auto_copyright]" value="1" <?php checked($disable_auto_copyright); ?>>
                <?php esc_html_e('Disable automatic copyright notice (keep other metadata)', 'the-total-book-project'); ?>
            </label>
        </fieldset>
        <?php
    }

    public function ttbp_sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize template
        $templates = array_keys($this->get_available_templates());
        $sanitized['template'] = in_array($input['template'], $templates) ? $input['template'] : 'default';
        
        // Sanitize checkboxes
        $sanitized['show_meta'] = isset($input['show_meta']) ? (bool) $input['show_meta'] : false;
        $sanitized['show_toc'] = isset($input['show_toc']) ? (bool) $input['show_toc'] : false;
        $sanitized['disable_auto_copyright'] = isset($input['disable_auto_copyright']) ? (bool) $input['disable_auto_copyright'] : false;
        
        return $sanitized;
    }

    private function get_available_templates() {
        // Get all PHP files from the templates directory
        $template_files = glob(plugin_dir_path(dirname(__FILE__)) . 'templates/*.php');
        $available_templates = array();
        
        // Add all template files from the directory
        foreach ($template_files as $template) {
            $template_name = basename($template, '.php');
            $available_templates[$template_name] = ucfirst($template_name);
        }
        
        // Add the Blog option
        $available_templates['blog'] = 'Blog';
        
        return $available_templates;
    }

    public function get_option($key, $default = null) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }
} 