<?php
/**
 * Plain template for displaying a book
 */

 if (!defined('ABSPATH')) {
    exit;
}

// Get book meta
$ttbp_author = get_post_meta(get_the_ID(), '_book_author', true);
$ttbp_isbn = get_post_meta(get_the_ID(), '_book_isbn', true);
$ttbp_publication_date = get_post_meta(get_the_ID(), '_book_publication_date', true);
$ttbp_publisher = get_post_meta(get_the_ID(), '_book_publisher', true);
$ttbp_description = get_post_meta(get_the_ID(), '_book_description', true);
$ttbp_subtitle = get_post_meta(get_the_ID(), '_book_subtitle', true);
$ttbp_dedication = get_post_meta(get_the_ID(), '_book_dedication', true);
$ttbp_acknowledgments = get_post_meta(get_the_ID(), '_book_acknowledgments', true);
$ttbp_about_author = get_post_meta(get_the_ID(), '_book_about_author', true);

// Get chapters
$ttbp_chapters = get_posts(array(
    'post_type' => 'ttbp_chapter',
    'post_parent' => get_the_ID(),
    'posts_per_page' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC'
));
// Get settings
$ttbp_settings = new TTBP_Settings();
$ttbp_show_meta = $ttbp_settings->get_option('show_meta', true);
$ttbp_show_toc = $ttbp_settings->get_option('show_toc', true);
?>

<article id="book-<?php the_ID(); ?>" <?php post_class('ttbp'); ?>>
    <header class="book-header">
        <h1 class="book-title"><?php the_title(); ?></h1>
        
        <?php if ($ttbp_subtitle) : ?>
            <h2 class="book-subtitle"><?php echo esc_html($ttbp_subtitle); ?></h2>
        <?php endif; ?>
        
        <?php if ($ttbp_show_meta && ($ttbp_author || $ttbp_isbn || $ttbp_publication_date || $ttbp_publisher)) : ?>
            <div class="book-meta">
                <?php if ($ttbp_author) : ?>
                    <div class="book-author">
                        <strong><?php esc_html_e('Author:', 'the-total-book-project'); ?></strong>
                        <span><?php echo esc_html($ttbp_author); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($ttbp_isbn) : ?>
                    <div class="book-isbn">
                        <strong><?php esc_html_e('ISBN:', 'the-total-book-project'); ?></strong>
                        <span><?php echo esc_html($ttbp_isbn); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($ttbp_publication_date) : ?>
                    <div class="book-publication-date">
                        <strong><?php esc_html_e('Publication Date:', 'the-total-book-project'); ?></strong>
                        <span><?php echo esc_html($ttbp_publication_date); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($ttbp_publisher) : ?>
                    <div class="book-publisher">
                        <strong><?php esc_html_e('Publisher:', 'the-total-book-project'); ?></strong>
                        <span><?php echo esc_html($ttbp_publisher); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($ttbp_dedication) : ?>
        <div class="book-dedication">
            <?php echo wp_kses_post(wpautop($ttbp_dedication)); ?>
        </div>
    <?php endif; ?>

    <?php if ($ttbp_description) : ?>
        <div class="book-description">
            <?php echo wp_kses_post(wpautop($ttbp_description)); ?>
        </div>
    <?php endif; ?>

    <?php if ($ttbp_show_toc && !empty($ttbp_chapters)) : ?>
        <div class="book-toc">
            <h2><?php esc_html_e('Table of Contents', 'the-total-book-project'); ?></h2>
            <ul class="chapter-list">
                <?php foreach ($ttbp_chapters as $ttbp_chapter) : ?>
                    <li class="chapter-item">
                        <a href="<?php echo esc_url(get_permalink($ttbp_chapter->ID)); ?>">
                            <?php echo esc_html($ttbp_chapter->post_title); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($ttbp_chapters)) : ?>
        <div class="book-chapters">
            <?php foreach ($ttbp_chapters as $ttbp_chapter) : ?>
                <div class="chapter-content">
                    <h2 id="chapter-<?php echo esc_attr($ttbp_chapter->ID); ?>">
                        <?php echo esc_html($ttbp_chapter->post_title); ?>
                    </h2>
                    <?php echo wp_kses_post(apply_filters('the_content', $ttbp_chapter->post_content)); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($ttbp_acknowledgments) : ?>
        <div class="book-acknowledgments">
            <h2><?php esc_html_e('Acknowledgments', 'the-total-book-project'); ?></h2>
            <?php echo wp_kses_post(wpautop($ttbp_acknowledgments)); ?>
        </div>
    <?php endif; ?>

    <?php if ($ttbp_about_author) : ?>
        <div class="book-about-author">
            <h2><?php esc_html_e('About The Author', 'the-total-book-project'); ?></h2>
            <?php echo wp_kses_post(wpautop($ttbp_about_author)); ?>
        </div>
    <?php endif; ?>
</article>
