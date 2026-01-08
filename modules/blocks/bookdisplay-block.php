<?php
/**
 * Book Display Block Module
 * Handles registration and rendering of the book-display block
 */

if (!defined('ABSPATH')) {
	exit;
}

class TTBP_Book_Display_Block {
	
	public function __construct() {
		add_action('init', array($this, 'register_book_display_block'));
	}
	
	public function register_book_display_block() {
		$block_path = plugin_dir_path(__FILE__) . '../../dist/book-display/block.json';
		
		if (file_exists($block_path)) {
			register_block_type($block_path, array(
				'render_callback' => array($this, 'book_display_render_callback'),
			));
		}
	}

	public function book_display_render_callback($attributes, $content) {
		// Only display on book pages
		if (!is_singular('ttbp-book')) {
			return '';
		}

		// Get the book id
		$ttbp_book_id = get_the_ID();
		
		// Enqueue the reader scripts and styles (WordPress will handle deduplication)
		$plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
		wp_enqueue_script('ttbp-widget', $plugin_url . 'dist/book-reader.min.js', array(), '1.0.0', true);
		wp_enqueue_style('ttbp-widget', $plugin_url . 'dist/book-reader.min.css', array(), '1.0.0');

		// Output the same structure as reader.php template
		// Use id="book-reader" to match the widget.tsx selector
		$output = '<div class="wp-block-ttbp-book-display">';
		$output .= '<div id="book-reader" data-book-id="' . esc_attr($ttbp_book_id) . '"></div>';
		$output .= '</div>';

		return $output;
	}
}

// Initialize the book display block
new TTBP_Book_Display_Block();
