<?php
/**
 * Book Shelf Block Module
 * Handles registration and rendering of the book-shelf block
 */

if (!defined('ABSPATH')) {
	exit;
}

class TTBP_Book_Shelf_Block {
	
	public function __construct() {
		add_action('init', array($this, 'register_book_shelf_block'));
	}
	
	public function register_book_shelf_block() {
		$block_path = plugin_dir_path(__FILE__) . '../../dist/book-shelf/block.json';
		
		if (file_exists($block_path)) {
			register_block_type($block_path, array(
				'render_callback' => array($this, 'book_shelf_render_callback'),
			));

			// Enqueue shortcode styles in editor for preview
			add_action('enqueue_block_editor_assets', function() {
				wp_enqueue_style(
					'ttbp-shortcodes-editor',
					plugin_dir_url(dirname(dirname(__FILE__))) . 'CSS/book-shortcodes.css',
					array(),
					'1.0.0'
				);
			});
		}
	}

	public function book_shelf_render_callback($attributes, $content) {
		// Default attributes matching the shortcode
		$defaults = array(
			'category' => array(),
			'limit' => 10,
			'orderby' => 'title',
			'order' => 'ASC',
			'showMeta' => true,
			'showExcerpt' => true,
			'columns' => 3,
			'template' => 'grid'
		);

		$atts = wp_parse_args($attributes, $defaults);

		// Convert category array to comma-separated string for query
		$category_slugs = array();
		if (!empty($atts['category']) && is_array($atts['category'])) {
			$category_slugs = $atts['category'];
		} elseif (!empty($atts['category']) && is_string($atts['category'])) {
			$category_slugs = explode(',', $atts['category']);
		}

		// Build query arguments
		$args = array(
			'post_type' => 'ttbp-book',
			'posts_per_page' => intval($atts['limit']),
			'orderby' => $atts['orderby'],
			'order' => $atts['order'],
			'post_status' => 'publish'
		);

		// Add category filter if specified
		if (!empty($category_slugs)) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Cached query with reasonable limits
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'ttbp_book_category',
					'field' => 'slug',
					'terms' => array_map('trim', $category_slugs)
				)
			);
		}

		// Cache the query results to improve performance
		$cache_key = 'ttbp_books_block_' . md5(serialize($args));
		$books = wp_cache_get($cache_key, 'ttbp');
		
		if (false === $books) {
			$books = get_posts($args);
			wp_cache_set($cache_key, $books, 'ttbp', HOUR_IN_SECONDS);
		}

		if (empty($books)) {
			return '<p class="total-book-no-results">' . esc_html__('No books found.', 'the-total-book-project') . '</p>';
		}

		// Render the books list
		ob_start();
		$this->render_books_list($books, $atts);
		return ob_get_clean();
	}

	/**
	 * Render books list (matching shortcode functionality)
	 */
	private function render_books_list($books, $atts) {
		$show_meta = filter_var($atts['showMeta'], FILTER_VALIDATE_BOOLEAN);
		$show_excerpt = filter_var($atts['showExcerpt'], FILTER_VALIDATE_BOOLEAN);
		$columns = intval($atts['columns']);
		$template = $atts['template'];

		$column_class = 'book-col-' . $columns;
		?>
		<div class="ttbp-books-list total-books-list ttbp-books-<?php echo esc_attr($template); ?>">
			<div class="books-grid <?php echo esc_attr($column_class); ?>">
				<?php foreach ($books as $book) : ?>
					<div class="book-item">
						<div class="book-cover">
							<a href="<?php echo esc_url(get_permalink($book->ID)); ?>">
								<?php if (has_post_thumbnail($book->ID)) : ?>
									<?php echo get_the_post_thumbnail($book->ID, 'medium'); ?>
								<?php else : ?>
									<div class="book-cover-placeholder">
										<span class="dashicons dashicons-book"></span>
									</div>
								<?php endif; ?>
							</a>
							<div class="book-overlay">
								<a href="<?php echo esc_url(get_permalink($book->ID)); ?>" class="book-overlay-link">
									<div class="book-title"><?php echo esc_html($book->post_title); ?></div>
									<div class="book-author">
										<?php
										$authors = TTBP_Book::get_book_authors($book->ID);
										if (!empty($authors)) {
											echo esc_html(implode(', ', $authors));
										}
										?>
									</div>
								</a>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}

// Initialize the book shelf block
new TTBP_Book_Shelf_Block();
