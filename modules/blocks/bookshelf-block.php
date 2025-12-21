<?php
/**
 * Bookshelf Block Module
 * Handles registration and rendering of the bookshelf block
 */

if (!defined('ABSPATH')) {
	exit;
}

class TTBP_Bookshelf_Block {
	
	public function __construct() {
		add_action('init', array($this, 'register_bookshelf_block'));
	}
	
	/**
	 * Register the bookshelf block
	 */
	public function register_bookshelf_block() {
		$block_dir = plugin_dir_path(dirname(__FILE__)) . 'dist/bookshelf';
		
		if (!file_exists($block_dir . '/block.json')) {
			return;
		}
		
		// Register block with render callback
		register_block_type($block_dir, array(
			'render_callback' => array($this, 'render_bookshelf_block'),
		));
	}
	
	/**
	 * Render callback for the bookshelf block
	 *
	 * @param array $attributes Block attributes
	 * @param string $content Block content
	 * @return string Rendered HTML
	 */
	public function render_bookshelf_block($attributes, $content) {
		$count = isset($attributes['count']) ? intval($attributes['count']) : 6;
		$show_excerpt = isset($attributes['showExcerpt']) ? $attributes['showExcerpt'] : true;
		
		$args = array(
			'post_type' => 'ttbp-book',
			'posts_per_page' => $count,
			'orderby' => 'date',
			'order' => 'DESC',
			'post_status' => 'publish',
		);
		
		$books = get_posts($args);
		
		if (empty($books)) {
			return '<p class="ttbp-no-results">' . esc_html__('No books found.', 'the-total-book-project') . '</p>';
		}
		
		ob_start();
		?>
		<div class="ttbp-book-shelf" data-count="<?php echo esc_attr($count); ?>" data-show-excerpt="<?php echo esc_attr($show_excerpt ? 'true' : 'false'); ?>">
			<div class="books-grid book-col-3">
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
										echo esc_html(implode(', ', $authors));
										?>
									</div>
								</a>
							</div>
						</div>
						<?php if ($show_excerpt && !empty($book->post_excerpt)) : ?>
							<div class="book-excerpt">
								<?php echo esc_html(wp_trim_words($book->post_excerpt, 20)); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

// Initialize the bookshelf block
new TTBP_Bookshelf_Block();

