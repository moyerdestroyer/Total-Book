<?php

/*
Plugin Name: Total Book
Description: A Book plugin for easily uploading books into a searchable, readable format for the web.
Version: 1.0
Author: Ryan Moyer
License: GPLv2 or later

*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit;
}

Class Total_Book_Plugin {
	public function __construct() {
		require_once plugin_dir_path( __FILE__ ) . 'modules/book.php';
		require_once plugin_dir_path( __FILE__ ) . 'modules/chapter.php';
		require_once plugin_dir_path( __FILE__ ) . 'modules/settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'modules/rest_apis.php';
		require_once plugin_dir_path( __FILE__ ) . 'modules/shortcodes.php';
		
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
		
		// Register activation and deactivation hooks
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));

		// Initialize settings
		$this->settings = new TB_Settings();
		
		// Load blog module if blog template is selected
		if ($this->settings->get_option('template') === 'blog') {
			require_once plugin_dir_path( __FILE__ ) . 'modules/blog.php';
			new TB_Blog();
		} else {
			add_filter('single_template', array($this, 'load_book_template'));
		}
	}

	public function enqueue_admin_styles($hook) {
		// Only load on book post type screens
		$screen = get_current_screen();
		if ($screen->post_type === 'book') {
			wp_enqueue_style(
				'total-book-admin',
				plugin_dir_url(__FILE__) . 'CSS/book-admin.css',
				array(),
				'1.0.0'
			);
			
			// Enqueue Select2 for enhanced dropdowns
			wp_enqueue_style(
				'select2',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
				array(),
				'4.1.0'
			);
			
			wp_enqueue_script(
				'total-book-admin',
				plugin_dir_url(__FILE__) . 'js/book-admin.js',
				array('jquery', 'jquery-ui-sortable'),
				'1.0.0',
				true
			);
			
			// Enqueue Select2 JS
			wp_enqueue_script(
				'select2',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
				array('jquery'),
				'4.1.0',
				true
			);
			
			wp_localize_script('total-book-admin', 'totalBookAdmin', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('total_book_nonce')
			));
		}
	}

	public function enqueue_frontend_styles() {
		// Always enqueue shortcode styles
		wp_enqueue_style(
			'total-book-shortcodes',
			plugin_dir_url(__FILE__) . 'CSS/book-shortcodes.css',
			array(),
			'1.0.0'
		);
		
		if (is_singular('book')) {
			$settings = new TB_Settings();
			$template = $settings->get_option('template', 'plain');
			
			if ($template === 'reader') {
				// Enqueue Next.js app
				wp_enqueue_script('total-book-widget', plugin_dir_url(__FILE__) . 'dist/book-reader.min.js', array(), '1.0.0', true);
				wp_enqueue_style('total-book-widget', plugin_dir_url(__FILE__) . 'dist/book-reader.min.css', array(), '1.0.0');
			}
		}
	}

	public function load_book_template($template) {
		$settings = new TB_Settings();
		$template_name = $settings->get_option('template', 'plain');
		
		// Handle chapter URLs only for reader template
		if ($template_name === 'reader' && is_singular('chapter')) {
			$chapter_id = get_the_ID();
			$book_id = get_post_field('post_parent', $chapter_id);
			
			if ($book_id) {
				$book_url = get_permalink($book_id);
				wp_redirect($book_url);
				exit;
			}
		}
		
		// Handle book template
		if (is_singular('book')) {
			$custom_template = plugin_dir_path(__FILE__) . 'templates/' . $template_name . '.php';
			
			if (file_exists($custom_template)) {
				return $custom_template;
			}
		}
		return $template;
	}

	public function activate() {
		// Register post types
		$book = new TB_Book();
		$book->register_post_type();
		
		$chapter = new TB_Chapter();
		$chapter->register_post_type();
		
		// Flush rewrite rules
		flush_rewrite_rules();
		
		// Migrate legacy author fields to taxonomy
		$this->migrate_legacy_authors();
	}

	public function deactivate() {
		// Flush rewrite rules on deactivation
		flush_rewrite_rules();
	}

	/**
	 * Migrate legacy author fields to the new taxonomy system
	 */
	private function migrate_legacy_authors() {
		// Only run migration if we haven't done it before
		if (get_option('total_book_authors_migrated', false)) {
			return;
		}
		
		$migrated_count = TB_Book::migrate_legacy_authors();
		
		// Mark migration as complete
		update_option('total_book_authors_migrated', true);
		
		// Log migration results
		if ($migrated_count > 0) {
			error_log("Total Book: Migrated {$migrated_count} books to use author taxonomy");
		}
	}
}

$total_book = new Total_Book_Plugin();