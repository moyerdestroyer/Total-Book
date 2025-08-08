<?php

class Shortcodes {
    public function __construct() {
        add_shortcode('total_book', array($this, 'book_shortcode'));
        add_shortcode('total_books', array($this, 'books_list_shortcode'));
    }

    /**
     * Display a single book
     * Usage: [total_book id="123"]
     */
    public function book_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_meta' => 'true',
            'show_toc' => 'true',
            'show_description' => 'true',
            'template' => 'default'
        ), $atts, 'total_book');

        $book_id = intval($atts['id']);
        if (!$book_id) {
            return '<p class="total-book-error">' . __('Book ID is required.', 'total-book') . '</p>';
        }

        $book = get_post($book_id);
        if (!$book || $book->post_type !== 'book') {
            return '<p class="total-book-error">' . __('Book not found.', 'total-book') . '</p>';
        }

        ob_start();
        $this->render_single_book($book, $atts);
        return ob_get_clean();
    }

    /**
     * Display a list of books
     * Usage: [total_books category="fiction" limit="10" orderby="title" order="ASC"]
     */
    public function books_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => 10,
            'orderby' => 'title',
            'order' => 'ASC',
            'show_meta' => 'true',
            'show_excerpt' => 'true',
            'columns' => 3,
            'template' => 'grid'
        ), $atts, 'total_books');

        $args = array(
            'post_type' => 'book',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish'
        );

        // Add category filter if specified
        if (!empty($atts['category'])) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Cached query with reasonable limits
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'book_category',
                    'field' => 'slug',
                    'terms' => explode(',', $atts['category'])
                )
            );
        }

        // Cache the query results to improve performance
        $cache_key = 'total_books_shortcode_' . md5(serialize($args));
        $books = wp_cache_get($cache_key, 'total_book');
        
        if (false === $books) {
            $books = get_posts($args);
            wp_cache_set($cache_key, $books, 'total_book', HOUR_IN_SECONDS);
        }

        if (empty($books)) {
            return '<p class="total-book-no-results">' . __('No books found.', 'total-book') . '</p>';
        }

        ob_start();
        $this->render_books_list($books, $atts);
        return ob_get_clean();
    }

    /**
     * Render a single book
     */
    private function render_single_book($book, $atts) {
        $book_id = $book->ID;
        $show_meta = filter_var($atts['show_meta'], FILTER_VALIDATE_BOOLEAN);
        $show_toc = filter_var($atts['show_toc'], FILTER_VALIDATE_BOOLEAN);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);

        // Get book meta
        $authors = TB_Book::get_book_authors($book_id);
        $isbn = get_post_meta($book_id, '_book_isbn', true);
        $publication_date = get_post_meta($book_id, '_book_publication_date', true);
        $publisher = get_post_meta($book_id, '_book_publisher', true);
        $description = get_post_meta($book_id, '_book_description', true);
        $subtitle = get_post_meta($book_id, '_book_subtitle', true);

        ?>
        <div class="total-book-single" data-book-id="<?php echo esc_attr($book_id); ?>">
            <div class="book-header">
                <h1 class="book-title"><?php echo esc_html($book->post_title); ?></h1>
                <?php if ($subtitle) : ?>
                    <h2 class="book-subtitle"><?php echo esc_html($subtitle); ?></h2>
                <?php endif; ?>
            </div>

            <?php if ($show_meta && (!empty($authors) || $isbn || $publication_date || $publisher)) : ?>
                <div class="book-meta">
                    <?php if (!empty($authors)) : ?>
                        <div class="book-author">
                            <strong><?php esc_html_e('Author:', 'total-book'); ?></strong> 
                            <?php 
                            $author_links = TB_Book::get_book_authors_links($book_id);
                            echo esc_html(implode(', ', $author_links));
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($isbn) : ?>
                        <div class="book-isbn">
                            <strong><?php esc_html_e('ISBN:', 'total-book'); ?></strong> 
                            <?php echo esc_html($isbn); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($publication_date) : ?>
                        <div class="book-publication-date">
                            <strong><?php esc_html_e('Publication Date:', 'total-book'); ?></strong> 
                            <?php echo esc_html($publication_date); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($publisher) : ?>
                        <div class="book-publisher">
                            <strong><?php esc_html_e('Publisher:', 'total-book'); ?></strong> 
                            <?php echo esc_html($publisher); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($show_description && $description) : ?>
                <div class="book-description">
                    <?php echo esc_html(wpautop(($description))); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_toc) : ?>
                <div class="book-toc">
                    <?php echo esc_html($this->render_book_toc($book_id)); ?>
                </div>
            <?php endif; ?>

            <div class="book-actions">
                <a href="<?php echo esc_url(get_permalink($book_id)); ?>" class="button book-view-btn">
                    <?php esc_html_e('View Book', 'total-book'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render books list
     */
    private function render_books_list($books, $atts) {
        $show_meta = filter_var($atts['show_meta'], FILTER_VALIDATE_BOOLEAN);
        $show_excerpt = filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN);
        $columns = intval($atts['columns']);
        $template = $atts['template'];

        $column_class = 'book-col-' . $columns;
        ?>
        <div class="total-books-list total-books-<?php echo esc_attr($template); ?>">
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
                                <div class="book-title"><?php echo esc_html($book->post_title); ?></div>
                                <div class="book-author">
                                    <?php
                                    $author_links = TB_Book::get_book_authors_links($book->ID);
                                    echo esc_html(implode('', $author_links));
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render book table of contents
     */
    private function render_book_toc($book_id) {
        $chapters = get_posts(array(
            'post_type' => 'chapter',
            'post_parent' => $book_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        if (empty($chapters)) {
            return '<p class="total-book-no-chapters">' . __('No chapters available.', 'total-book') . '</p>';
        }

        ob_start();
        ?>
        <div class="total-book-toc">
            <h3><?php esc_html_e('Table of Contents', 'total-book'); ?></h3>
            <ul class="chapter-list">
                <?php foreach ($chapters as $index => $chapter) : ?>
                    <li class="chapter-item">
                        <a href="<?php echo esc_url(get_permalink($chapter->ID)); ?>">
                            <span class="chapter-number"><?php echo esc_html($index + 1); ?>.</span>
                            <span class="chapter-title"><?php echo esc_html($chapter->post_title); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the shortcodes
new Shortcodes();