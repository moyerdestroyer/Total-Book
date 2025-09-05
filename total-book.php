<?php

/*
Plugin Name: The Total Book Plugin
Description: A Book plugin for hosting books on your website.
Version: 1.0
Author: Ryan Moyer
License: GPLv2 or later
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0
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
		
		add_action('admin_enqueue_scripts', array($this, 'ttbp_enqueue_admin_styles'));
		add_action('wp_enqueue_scripts', array($this, 'ttbp_enqueue_frontend_styles'));
		
		// Register activation and deactivation hooks
		register_activation_hook(__FILE__, array($this, 'ttbp_activate'));
		register_deactivation_hook(__FILE__, array($this, 'ttbp_deactivate'));

		// Initialize settings
		$this->settings = new TTBP_Settings();
		
		// Load blog module if blog template is selected
		if ($this->settings->get_option('template') === 'blog') {
			require_once plugin_dir_path( __FILE__ ) . 'modules/blog.php';
			new TTBP_Blog();
		} else {
			add_filter('single_template', array($this, 'ttbp_load_book_template'));
		}
	}

	public function ttbp_enqueue_admin_styles($hook) {
		// Only load on book and chapter post type screens
		$screen = get_current_screen();
		if ($screen->post_type === 'ttbp-book' || $screen->post_type === 'ttbp_chapter') {
							wp_enqueue_style(
					'ttbp-admin',
					plugin_dir_url(__FILE__) . 'CSS/book-admin.css',
					array(),
					'1.0.0'
				);

							wp_enqueue_style(
					'ttbp-tagify',
					plugin_dir_url(__FILE__) . 'dist/tagify.css',
					array(),
					'1.0.0'
				);

							wp_enqueue_script(
					'ttbp-tagify',
					plugin_dir_url(__FILE__) . 'dist/tagify.js',
					array('jquery'),
					'1.0.0',
					true
				);
							wp_enqueue_script(
					'ttbp-admin',
					plugin_dir_url(__FILE__) . 'js/book-admin.js',
					array('jquery', 'jquery-ui-sortable'),
					'1.0.0',
					true
				);

							wp_localize_script('ttbp-admin', 'ttbpAdmin', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
									'nonce' => wp_create_nonce('ttbp_nonce'),
									'messages' => array(
						'selectBook' => __('Please select a book', 'ttbp'),
						'assigning' => __('Assigning...', 'ttbp'),
						'assign' => __('Assign', 'ttbp'),
						'assignFailed' => __('Failed to assign chapter', 'ttbp')
					)
			));
		}
	}

	public function ttbp_enqueue_frontend_styles() {
		// Always enqueue shortcode styles
					wp_enqueue_style(
				'ttbp-shortcodes',
				plugin_dir_url(__FILE__) . 'CSS/book-shortcodes.css',
				array(),
				'1.0.0'
			);
		
		if (is_singular('ttbp-book')) {
			$settings = new TTBP_Settings();
			$template = $settings->get_option('template', 'plain');
			
			if ($template === 'reader') {
				// Enqueue Next.js app
				wp_enqueue_script('ttbp-widget', plugin_dir_url(__FILE__) . 'dist/book-reader.min.js', array(), '1.0.0', true);
				wp_enqueue_style('ttbp-widget', plugin_dir_url(__FILE__) . 'dist/book-reader.min.css', array(), '1.0.0');
			}
		}
	}

	public function ttbp_load_book_template($template) {
		$settings = new TTBP_Settings();
		$template_name = $settings->get_option('template', 'plain');
		
		// Handle chapter URLs only for reader template
		if ($template_name === 'reader' && is_singular('ttbp_chapter')) {
			$chapter_id = get_the_ID();
			$book_id = get_post_field('post_parent', $chapter_id);
			
			if ($book_id) {
				$book_url = get_permalink($book_id);
				wp_redirect($book_url);
				exit;
			}
		}
		
		// Handle book template
		if (is_singular('ttbp-book')) {
			$custom_template = plugin_dir_path(__FILE__) . 'templates/' . $template_name . '.php';
			
			if (file_exists($custom_template)) {
				return $custom_template;
			}
		}
		return $template;
	}

	public function ttbp_activate() {
		// Register post types
		$book = new TTBP_Book();
		$book->ttbp_register_post_type();
		
		$chapter = new TTBP_Chapter();
		$chapter->ttbp_register_post_type();
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	public function ttbp_deactivate() {
		// Flush rewrite rules on deactivation
		flush_rewrite_rules();
	}
}

$total_book = new Total_Book_Plugin();