<?php
/*
 * Settings Submodule
 */

Class TB_Settings {
    private $options;
    private $option_name = 'total_book_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        $this->options = get_option($this->option_name, array(
            'template' => 'default',
            'show_meta' => true,
            'show_toc' => true,
            'disable_auto_copyright' => false
        ));
    }

    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=book',
            __('Total Book Settings', 'total-book'),
            __('Settings', 'total-book'),
            'manage_options',
            'total-book-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting(
            'total_book_settings',
            $this->option_name,
            array($this, 'sanitize_settings')
        );

        add_settings_section(
            'total_book_general',
            __('General Settings', 'total-book'),
            array($this, 'render_section_info'),
            'total-book-settings'
        );

        add_settings_field(
            'template',
            __('Book Template', 'total-book'),
            array($this, 'render_template_field'),
            'total-book-settings',
            'total_book_general'
        );

        add_settings_field(
            'show_meta',
            __('Display Options', 'total-book'),
            array($this, 'render_display_options'),
            'total-book-settings',
            'total_book_general'
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('total_book_settings');
                do_settings_sections('total-book-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_section_info() {
        echo '<p>' . __('Configure how your books are displayed on the front end.', 'total-book') . '</p>';
    }

    public function render_template_field() {
        $templates = $this->get_available_templates();
        $current = isset($this->options['template']) ? $this->options['template'] : 'default';
        ?>
        <select name="<?php echo $this->option_name; ?>[template]" id="template">
            <?php foreach ($templates as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($current, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php _e('Select the template to use for displaying books.', 'total-book'); ?>
        </p>
        <?php
    }

    public function render_display_options() {
        $show_meta = isset($this->options['show_meta']) ? $this->options['show_meta'] : true;
        $show_toc = isset($this->options['show_toc']) ? $this->options['show_toc'] : true;
        $disable_auto_copyright = isset($this->options['disable_auto_copyright']) ? $this->options['disable_auto_copyright'] : false;
        ?>
        <fieldset>
            <label>
                <input type="checkbox" name="<?php echo $this->option_name; ?>[show_meta]" value="1" <?php checked($show_meta); ?>>
                <?php _e('Show book metadata (author, ISBN, etc.)', 'total-book'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" name="<?php echo $this->option_name; ?>[show_toc]" value="1" <?php checked($show_toc); ?>>
                <?php _e('Show table of contents', 'total-book'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" name="<?php echo $this->option_name; ?>[disable_auto_copyright]" value="1" <?php checked($disable_auto_copyright); ?>>
                <?php _e('Disable automatic copyright notice (keep other metadata)', 'total-book'); ?>
            </label>
        </fieldset>
        <?php
    }

    public function sanitize_settings($input) {
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