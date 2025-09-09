<?php
/**
 * Template for displaying the book reader
 */

if (!defined('ABSPATH')) {
  exit;
}

get_header();

// Get the book id
$book_id = get_the_ID();
$book = get_post($book_id);

// Get book meta data
$subtitle = get_post_meta($book_id, '_book_subtitle', true);
$description = get_post_meta($book_id, '_book_description', true);
$isbn = get_post_meta($book_id, '_book_isbn', true);
$publisher = get_post_meta($book_id, '_book_publisher', true);
$publication_date = get_post_meta($book_id, '_book_publication_date', true);
$authors = TTBP_Book::get_book_authors($book_id);
$author = !empty($authors) ? implode(', ', $authors) : '';

// Get chapters
$chapters = get_posts(array(
    'post_type' => 'ttbp_chapter',
    'post_parent' => $book_id,
    'posts_per_page' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC'
));

// Get categories
$category_terms = wp_get_post_terms($book_id, 'ttbp_book_category', array('fields' => 'names'));
$categories = !empty($category_terms) ? $category_terms : array();
?>

<!-- Interactive Book Reader -->
<div id="book-reader" data-book-id="<?php echo esc_attr($book_id); ?>"></div>

<!-- Fallback content for search engines and users without JavaScript -->
<noscript>
  <div class="book-fallback-content">
    <article class="book-content-fallback">
      <header class="book-header-fallback">
        <h1><?php echo esc_html($book->post_title); ?></h1>
        <?php if ($subtitle): ?>
          <h2><?php echo esc_html($subtitle); ?></h2>
        <?php endif; ?>
        <?php if ($author): ?>
          <p class="book-author-fallback">By <?php echo esc_html($author); ?></p>
        <?php endif; ?>
      </header>

      <?php if ($description): ?>
        <div class="book-description-fallback">
          <h3>Description</h3>
          <p><?php echo esc_html($description); ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($chapters)): ?>
        <div class="book-toc-fallback">
          <h3>Table of Contents</h3>
          <ul>
            <?php foreach ($chapters as $chapter): ?>
              <li><a href="<?php echo esc_url(get_permalink($chapter->ID)); ?>"><?php echo esc_html($chapter->post_title); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="book-meta-fallback">
        <?php if ($isbn): ?>
          <p><strong>ISBN:</strong> <?php echo esc_html($isbn); ?></p>
        <?php endif; ?>
        <?php if ($publisher): ?>
          <p><strong>Publisher:</strong> <?php echo esc_html($publisher); ?></p>
        <?php endif; ?>
        <?php if ($publication_date): ?>
          <p><strong>Publication Date:</strong> <?php echo esc_html($publication_date); ?></p>
        <?php endif; ?>
        <?php if (!empty($categories)): ?>
          <p><strong>Categories:</strong> <?php echo esc_html(implode(', ', $categories)); ?></p>
        <?php endif; ?>
      </div>

      <div class="book-reader-notice">
        <p><strong>Interactive Reader Available:</strong> Enable JavaScript to access the full interactive book reader with navigation, settings, and enhanced reading experience.</p>
      </div>
    </article>
  </div>
</noscript>



<?php get_footer(); ?>