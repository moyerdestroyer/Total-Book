<?php
/*
 * Blog Template Module
 * Handles WordPress theme integration and navigation features
 */

Class TB_Blog {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_filter('the_content', array($this, 'modify_content'));
        add_action('wp_head', array($this, 'add_meta_tags'));
    }

    public function enqueue_styles() {
        if (is_singular('book') || is_singular('chapter')) {
            wp_enqueue_style(
                'total-book-blog',
                plugin_dir_url(dirname(__FILE__)) . 'CSS/book-blog.css',
                array(),
                '1.0.0'
            );
        }
    }

    public function modify_content($content) {
        if (is_singular('book')) {
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
            if ($author || $isbn || $publication_date || $publisher) {
                $book_content .= '<div class="book-meta">';
                if ($author) {
                    $book_content .= sprintf(
                        '<div class="book-author"><strong>%s</strong> %s</div>',
                        __('Author:', 'total-book'),
                        esc_html($author)
                    );
                }
                if ($isbn) {
                    $book_content .= sprintf(
                        '<div class="book-isbn"><strong>%s</strong> %s</div>',
                        __('ISBN:', 'total-book'),
                        esc_html($isbn)
                    );
                }
                if ($publication_date) {
                    $book_content .= sprintf(
                        '<div class="book-publication-date"><strong>%s</strong> %s</div>',
                        __('Publication Date:', 'total-book'),
                        esc_html($publication_date)
                    );
                }
                if ($publisher) {
                    $book_content .= sprintf(
                        '<div class="book-publisher"><strong>%s</strong> %s</div>',
                        __('Publisher:', 'total-book'),
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
            $book_content .= $this->render_book_toc(get_the_ID());

            // Add acknowledgments if exists
            if ($acknowledgments) {
                $book_content .= sprintf(
                    '<div class="book-acknowledgments"><h2>%s</h2>%s</div>',
                    __('Acknowledgments', 'total-book'),
                    wpautop(esc_html($acknowledgments))
                );
            }

            // Add about the author if exists
            if ($about_author) {
                $book_content .= sprintf(
                    '<div class="book-about-author"><h2>%s</h2>%s</div>',
                    __('About The Author', 'total-book'),
                    wpautop(esc_html($about_author))
                );
            }

            $book_content .= '</div>';

            return $book_content;
        } elseif (is_singular('chapter')) {
            // Add chapter navigation
            return $this->add_chapter_navigation($content);
        }

        return $content;
    }

    public function add_chapter_navigation($content) {
        // Get current chapter
        $current_chapter = get_post();
        $book_id = $current_chapter->post_parent;
        
        // Get all chapters for this book
        $chapters = get_posts(array(
            'post_type' => 'chapter',
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
            __('Table of Contents', 'total-book')
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

    public function add_meta_tags() {
        if (is_singular('book') || is_singular('chapter')) {
            $post = get_post();
            
            // Get book ID (either the post itself or its parent)
            $book_id = is_singular('book') ? $post->ID : $post->post_parent;
            $book = get_post($book_id);
            
            // Get book meta
            $author = get_post_meta($book_id, '_book_author', true);
            $description = get_post_meta($book_id, '_book_description', true);
            
            // Output meta tags
            echo '<meta property="og:title" content="' . esc_attr($post->post_title) . '" />' . "\n";
            echo '<meta property="og:type" content="' . (is_singular('book') ? 'book' : 'article') . '" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
            
            if ($description) {
                echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
            }
            
            if ($author) {
                echo '<meta property="book:author" content="' . esc_attr($author) . '" />' . "\n";
            }
        }
    }

    public function render_book_toc($book_id) {
        $chapters = get_posts(array(
            'post_type' => 'chapter',
            'post_parent' => $book_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        if (empty($chapters)) {
            return '<p>' . __('No chapters available.', 'total-book') . '</p>';
        }

        $output = '<div class="book-toc">';
        $output .= '<h2>' . __('Table of Contents', 'total-book') . '</h2>';
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