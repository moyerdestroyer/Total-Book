<?php
/**
 * Bookshelf Block Render Template
 *
 * @var array $attributes Block attributes.
 */

if (!defined('ABSPATH')) {
	exit;
}

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
	echo '<p class="ttbp-no-results">' . esc_html__('No books found.', 'the-total-book-project') . '</p>';
	return;
}
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

