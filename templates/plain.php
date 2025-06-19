<?php
/**
 * Plain template for displaying a book
 */

// Get book meta
$author = get_post_meta(get_the_ID(), '_book_author', true);
$isbn = get_post_meta(get_the_ID(), '_book_isbn', true);
$publication_date = get_post_meta(get_the_ID(), '_book_publication_date', true);
$publisher = get_post_meta(get_the_ID(), '_book_publisher', true);
$description = get_post_meta(get_the_ID(), '_book_description', true);
$subtitle = get_post_meta(get_the_ID(), '_book_subtitle', true);
$dedication = get_post_meta(get_the_ID(), '_book_dedication', true);
$acknowledgments = get_post_meta(get_the_ID(), '_book_acknowledgments', true);
$about_author = get_post_meta(get_the_ID(), '_book_about_author', true);

// Get chapters
$chapters = get_posts(array(
    'post_type' => 'chapter',
    'post_parent' => get_the_ID(),
    'posts_per_page' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC'
));
// Get settings
$settings = new TB_Settings();
$show_meta = $settings->get_option('show_meta', true);
$show_toc = $settings->get_option('show_toc', true);
?>

<article id="book-<?php the_ID(); ?>" <?php post_class('total-book'); ?>>
    <header class="book-header">
        <h1 class="book-title"><?php the_title(); ?></h1>
        
        <?php if ($subtitle) : ?>
            <h2 class="book-subtitle"><?php echo esc_html($subtitle); ?></h2>
        <?php endif; ?>
        
        <?php if ($show_meta && ($author || $isbn || $publication_date || $publisher)) : ?>
            <div class="book-meta">
                <?php if ($author) : ?>
                    <div class="book-author">
                        <strong><?php _e('Author:', 'total-book'); ?></strong>
                        <span><?php echo esc_html($author); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($isbn) : ?>
                    <div class="book-isbn">
                        <strong><?php _e('ISBN:', 'total-book'); ?></strong>
                        <span><?php echo esc_html($isbn); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($publication_date) : ?>
                    <div class="book-publication-date">
                        <strong><?php _e('Publication Date:', 'total-book'); ?></strong>
                        <span><?php echo esc_html($publication_date); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($publisher) : ?>
                    <div class="book-publisher">
                        <strong><?php _e('Publisher:', 'total-book'); ?></strong>
                        <span><?php echo esc_html($publisher); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($dedication) : ?>
        <div class="book-dedication">
            <?php echo wpautop(esc_html($dedication)); ?>
        </div>
    <?php endif; ?>

    <?php if ($description) : ?>
        <div class="book-description">
            <?php echo wpautop(esc_html($description)); ?>
        </div>
    <?php endif; ?>

    <?php if ($show_toc && !empty($chapters)) : ?>
        <div class="book-toc">
            <h2><?php _e('Table of Contents', 'total-book'); ?></h2>
            <ul class="chapter-list">
                <?php foreach ($chapters as $chapter) : ?>
                    <li class="chapter-item">
                        <a href="<?php echo get_permalink($chapter->ID); ?>">
                            <?php echo esc_html($chapter->post_title); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($chapters)) : ?>
        <div class="book-chapters">
            <?php foreach ($chapters as $chapter) : ?>
                <div class="chapter-content">
                    <h2 id="chapter-<?php echo $chapter->ID; ?>">
                        <?php echo esc_html($chapter->post_title); ?>
                    </h2>
                    <?php echo apply_filters('the_content', $chapter->post_content); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($acknowledgments) : ?>
        <div class="book-acknowledgments">
            <h2><?php _e('Acknowledgments', 'total-book'); ?></h2>
            <?php echo wpautop(esc_html($acknowledgments)); ?>
        </div>
    <?php endif; ?>

    <?php if ($about_author) : ?>
        <div class="book-about-author">
            <h2><?php _e('About The Author', 'total-book'); ?></h2>
            <?php echo wpautop(esc_html($about_author)); ?>
        </div>
    <?php endif; ?>
</article>
