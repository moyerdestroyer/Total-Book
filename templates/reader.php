<?php
/**
 * Template for displaying the book reader
 */

if (!defined('ABSPATH')) {
  exit;
}

// Check if theme is a block theme (WordPress 5.9+)
$ttbp_is_block_theme = function_exists('wp_is_block_theme') && wp_is_block_theme();

if ($ttbp_is_block_theme) {
  // For block themes, output minimal HTML structure
  ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>
  <div class="wp-site-blocks">
  <?php
} else {
  // For classic themes, use standard header
  get_header();
}

// Get the book id
$ttbp_book_id = get_the_ID();
$ttbp_book = get_post($ttbp_book_id);

// Get book meta data
$ttbp_subtitle = get_post_meta($ttbp_book_id, '_book_subtitle', true);
$ttbp_description = get_post_meta($ttbp_book_id, '_book_description', true);
$ttbp_isbn = get_post_meta($ttbp_book_id, '_book_isbn', true);
$ttbp_publisher = get_post_meta($ttbp_book_id, '_book_publisher', true);
$ttbp_publication_date = get_post_meta($ttbp_book_id, '_book_publication_date', true);
$ttbp_authors = TTBP_Book::get_book_authors($ttbp_book_id);
$ttbp_author = !empty($ttbp_authors) ? implode(', ', $ttbp_authors) : '';

// Get chapters
$ttbp_chapters = get_posts(array(
    'post_type' => 'ttbp_chapter',
    'post_parent' => $ttbp_book_id,
    'posts_per_page' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC'
));

// Get categories
$ttbp_category_terms = wp_get_post_terms($ttbp_book_id, 'ttbp_book_category', array('fields' => 'names'));
$ttbp_categories = !empty($ttbp_category_terms) ? $ttbp_category_terms : array();
?>

<!-- Interactive Book Reader -->
<div id="book-reader" data-book-id="<?php echo esc_attr($ttbp_book_id); ?>"></div>

<!-- Fallback content for search engines and users without JavaScript -->
<noscript>
  <div class="book-fallback-content">
    <article class="book-content-fallback">
      <header class="book-header-fallback">
        <h1><?php echo esc_html($ttbp_book->post_title); ?></h1>
        <?php if ($ttbp_subtitle): ?>
          <h2><?php echo esc_html($ttbp_subtitle); ?></h2>
        <?php endif; ?>
        <?php if ($ttbp_author): ?>
          <p class="book-author-fallback">By <?php echo esc_html($ttbp_author); ?></p>
        <?php endif; ?>
      </header>

      <?php if ($ttbp_description): ?>
        <div class="book-description-fallback">
          <h3>Description</h3>
          <p><?php echo esc_html($ttbp_description); ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($ttbp_chapters)): ?>
        <div class="book-toc-fallback">
          <h3>Table of Contents</h3>
          <ul>
            <?php foreach ($ttbp_chapters as $ttbp_chapter): ?>
              <li><a href="<?php echo esc_url(get_permalink($ttbp_chapter->ID)); ?>"><?php echo esc_html($ttbp_chapter->post_title); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="book-meta-fallback">
        <?php if ($ttbp_isbn): ?>
          <p><strong>ISBN:</strong> <?php echo esc_html($ttbp_isbn); ?></p>
        <?php endif; ?>
        <?php if ($ttbp_publisher): ?>
          <p><strong>Publisher:</strong> <?php echo esc_html($ttbp_publisher); ?></p>
        <?php endif; ?>
        <?php if ($ttbp_publication_date): ?>
          <p><strong>Publication Date:</strong> <?php echo esc_html($ttbp_publication_date); ?></p>
        <?php endif; ?>
        <?php if (!empty($ttbp_categories)): ?>
          <p><strong>Categories:</strong> <?php echo esc_html(implode(', ', $ttbp_categories)); ?></p>
        <?php endif; ?>
      </div>

      <div class="book-reader-notice">
        <p><strong>Interactive Reader Available:</strong> Enable JavaScript to access the full interactive book reader with navigation, settings, and enhanced reading experience.</p>
      </div>
    </article>
  </div>
</noscript>

<?php
// Close the template based on theme type
if ($ttbp_is_block_theme) {
  ?>
  </div><!-- .wp-site-blocks -->
  <?php wp_footer(); ?>
</body>
</html>
  <?php
} else {
  get_footer();
}
?>