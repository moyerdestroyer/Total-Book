<?php
/*
 * Blog Template Module
 * Handles WordPress theme integration and navigation features
 */

if (!defined('ABSPATH')) {
    exit;
}

Class TTBP_Blog {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'ttbp_enqueue_styles'));
        add_filter('the_content', array($this, 'ttbp_modify_content'));
        add_action('wp_head', array($this, 'ttbp_add_meta_tags'));
    }

    public function ttbp_enqueue_styles() {
        if (is_singular('ttbp-book') || is_singular('ttbp_chapter')) {
            wp_enqueue_style(
                'ttbp-blog',
                plugin_dir_url(dirname(__FILE__)) . 'CSS/book-blog.css',
                array(),
                '1.0.0'
            );
        }
    }

    public function ttbp_modify_content($content) {
        if (is_singular('ttbp-book')) {
            // Get book meta
            $authors = TTBP_Book::get_book_authors(get_the_ID());
            $isbn = get_post_meta(get_the_ID(), '_book_isbn', true);
            $publication_date = get_post_meta(get_the_ID(), '_book_publication_date', true);
            $publisher = get_post_meta(get_the_ID(), '_book_publisher', true);
            $description = get_post_meta(get_the_ID(), '_book_description', true);
            $subtitle = get_post_meta(get_the_ID(), '_book_subtitle', true);
            $dedication = get_post_meta(get_the_ID(), '_book_dedication', true);
            $acknowledgments = get_post_meta(get_the_ID(), '_book_acknowledgments', true);
            $about_author = get_post_meta(get_the_ID(), '_book_about_author', true);

            // Build book content
            $book_content = '<div class="book-content">';

            // Add subtitle if exists
            if ($subtitle) {
                $book_content .= sprintf(
                    '<h2 class="book-subtitle">%s</h2>',
                    esc_html($subtitle)
                );
            }

            // Add meta information
            if (!empty($authors) || $isbn || $publication_date || $publisher) {
                $book_content .= '<div class="book-meta">';
                if (!empty($authors)) {
                    $author_links = TTBP_Book::get_book_authors_links(get_the_ID());
                    $book_content .= sprintf(
                        '<div class="book-author"><strong>%s</strong> %s</div>',
                        __('Author:', 'ttbp'),
                        implode(', ', $author_links)
                    );
                }
                if ($isbn) {
                    $book_content .= sprintf(
                        '<div class="book-isbn"><strong>%s</strong> %s</div>',
                        __('ISBN:', 'ttbp'),
                        esc_html($isbn)
                    );
                }
                if ($publication_date) {
                    $book_content .= sprintf(
                        '<div class="book-publication-date"><strong>%s</strong> %s</div>',
                        __('Publication Date:', 'ttbp'),
                        esc_html($publication_date)
                    );
                }
                if ($publisher) {
                    $book_content .= sprintf(
                        '<div class="book-publisher"><strong>%s</strong> %s</div>',
                        __('Publisher:', 'ttbp'),
                        esc_html($publisher)
                    );
                }
                $book_content .= '</div>';
            }

            // Add dedication if exists
            if ($dedication) {
                $book_content .= sprintf(
                    '<div class="book-dedication">%s</div>',
                    wpautop(esc_html($dedication))
                );
            }

            // Add description
            if ($description) {
                $book_content .= sprintf(
                    '<div class="book-description">%s</div>',
                    wpautop(esc_html($description))
                );
            }

            // Add table of contents
            $book_content .= $this->ttbp_render_book_toc(get_the_ID());

            // Add acknowledgments if exists
            if ($acknowledgments) {
                $book_content .= sprintf(
                    '<div class="book-acknowledgments"><h2>%s</h2>%s</div>',
                    __('Acknowledgments', 'ttbp'),
                    wpautop(esc_html($acknowledgments))
                );
            }

            // Add about the author if exists
            if ($about_author) {
                $book_content .= sprintf(
                    '<div class="book-about-author"><h2>%s</h2>%s</div>',
                    __('About The Author', 'ttbp'),
                    wpautop(esc_html($about_author))
                );
            }

            $book_content .= '</div>';

            return $book_content;
        } elseif (is_singular('ttbp_chapter')) {
            // Add chapter navigation
            return $this->ttbp_add_chapter_navigation($content);
        }

        return $content;
    }

    public function ttbp_add_chapter_navigation($content) {
        // Get current chapter
        $current_chapter = get_post();
        $book_id = $current_chapter->post_parent;
        
        // Get all chapters for this book
        $chapters = get_posts(array(
            'post_type' => 'ttbp_chapter',
            'post_parent' => $book_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        // Find current chapter index
        $current_index = array_search($current_chapter->ID, wp_list_pluck($chapters, 'ID'));
        
        // Get previous and next chapters
        $prev_chapter = ($current_index > 0) ? $chapters[$current_index - 1] : null;
        $next_chapter = ($current_index < count($chapters) - 1) ? $chapters[$current_index + 1] : null;

        // Build navigation HTML
        $navigation = '<div class="chapter-navigation">';
        
        if ($prev_chapter) {
            $navigation .= sprintf(
                '<a href="%s" class="nav-prev">← %s</a>',
                get_permalink($prev_chapter->ID),
                esc_html($prev_chapter->post_title)
            );
        }
        
        $navigation .= sprintf(
            '<a href="%s" class="nav-toc">%s</a>',
            get_permalink($book_id),
            __('Table of Contents', 'ttbp')
        );
        
        if ($next_chapter) {
            $navigation .= sprintf(
                '<a href="%s" class="nav-next">%s →</a>',
                get_permalink($next_chapter->ID),
                esc_html($next_chapter->post_title)
            );
        }
        
        $navigation .= '</div>';

        // Add navigation before and after content
        return $navigation . $content . $navigation;
    }

    public function ttbp_add_meta_tags() {
        if (is_singular('ttbp-book') || is_singular('ttbp_chapter')) {
            $post = get_post();
            
            // Get book ID (either the post itself or its parent)
            $book_id = is_singular('ttbp-book') ? $post->ID : $post->post_parent;
            $book = get_post($book_id);
            
            // Get book meta
            $authors = TTBP_Book::get_book_authors($book_id);
            $description = get_post_meta($book_id, '_book_description', true);
            
            // Output meta tags
            echo '<meta property="og:title" content="' . esc_attr($post->post_title) . '" />' . "\n";
            echo '<meta property="og:type" content="' . (is_singular('ttbp-book') ? 'book' : 'article') . '" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
            
            if ($description) {
                echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
            }
            
            if (!empty($authors)) {
                $first_author = $authors[0];
                echo '<meta property="book:author" content="' . esc_attr($first_author) . '" />' . "\n";
            }
        }
    }

    public function ttbp_render_book_toc($book_id) {
        $chapters = get_posts(array(
            'post_type' => 'ttbp_chapter',
            'post_parent' => $book_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        if (empty($chapters)) {
            return '<p>' . __('No chapters available.', 'ttbp') . '</p>';
        }

        $output = '<div class="book-toc">';
        $output .= '<h2>' . __('Table of Contents', 'ttbp') . '</h2>';
        $output .= '<ul class="chapter-list">';

        foreach ($chapters as $chapter) {
            $output .= sprintf(
                '<li class="chapter-item"><a href="%s">%s</a></li>',
                get_permalink($chapter->ID),
                esc_html($chapter->post_title)
            );
        }

        $output .= '</ul></div>';
        return $output;
    }
} 