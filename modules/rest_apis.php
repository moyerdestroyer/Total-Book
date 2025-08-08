<?php
/**
 * REST API endpoints for the Total Book plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Total_Book_REST_API {
    /**
     * Initialize the REST API endpoints
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        //generate epub file by id
        register_rest_route('get_chapter_ids', '/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_chapter_ids'),
            'permission_callback' => '__return_true',
        ));

        // Get book categories
        register_rest_route('total-book/v1', '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_book_categories'),
            'permission_callback' => '__return_true',
        ));

        // Get complete book content
        register_rest_route('total-book/v1', '/book/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_book_content'),
            'permission_callback' => '__return_true',
        ));
    }

    public function get_chapter_ids($request) {
        $book_id = $request->get_param('id');
        //find all children of the book id
        $children = get_children(array(
            'post_parent' => $book_id,
            'post_type' => 'chapter',
            'numberposts' => -1,
        ));

        // Get book categories
        $categories = wp_get_post_terms($book_id, 'book_category', array('fields' => 'all'));
        $category_data = array();
        foreach ($categories as $category) {
            $category_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
            );
        }

        $response = array(
            'chapters' => $children,
            'categories' => $category_data
        );

        return rest_ensure_response($response);
    }

    public function get_book_categories($request) {
        $categories = get_terms(array(
            'taxonomy' => 'book_category',
            'hide_empty' => false,
        ));

        $category_data = array();
        foreach ($categories as $category) {
            $category_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
            );
        }

        return rest_ensure_response($category_data);
    }

    public function get_book_content($request) {
        $book_id = $request->get_param('id');
        $book = get_post($book_id);

        if (!$book || $book->post_type !== 'book') {
            return new WP_Error('not_found', 'Book not found', array('status' => 404));
        }

        // Get all book meta with defaults
        $meta = array(
            'author' => TB_Book::get_book_authors($book_id),
            'isbn' => get_post_meta($book_id, '_book_isbn', true) ?: '',
            'publication_date' => get_post_meta($book_id, '_book_publication_date', true) ?: '',
            'publisher' => get_post_meta($book_id, '_book_publisher', true) ?: '',
            'description' => get_post_meta($book_id, '_book_description', true) ?: '',
            'subtitle' => get_post_meta($book_id, '_book_subtitle', true) ?: '',
            'dedication' => get_post_meta($book_id, '_book_dedication', true) ?: '',
            'acknowledgments' => get_post_meta($book_id, '_book_acknowledgments', true) ?: '',
            'about_author' => get_post_meta($book_id, '_book_about_author', true) ?: '',
        );

        // Remove any meta fields with empty values
        $meta = array_filter($meta, function($value) {
            return !empty($value);
        });

        // Get book categories
        $categories = wp_get_post_terms($book_id, 'book_category', array('fields' => 'all'));
        $category_data = array();
        foreach ($categories as $category) {
            $category_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
            );
        }

        // Get featured image
        $featured_image = null;
        if (has_post_thumbnail($book_id)) {
            $image_id = get_post_thumbnail_id($book_id);
            $image_data = wp_get_attachment_image_src($image_id, 'full');
            if ($image_data) {
                $featured_image = array(
                    'url' => $image_data[0],
                    'width' => $image_data[1],
                    'height' => $image_data[2],
                );
            }
        }

        // Get chapters
        $chapters = get_posts(array(
            'post_type' => 'chapter',
            'post_parent' => $book_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        $chapter_data = array();
        foreach ($chapters as $chapter) {
            $chapter_data[] = array(
                'id' => $chapter->ID,
                'title' => $chapter->post_title,
                //prepend the title to the content
                'html' => '<h1 class="book-chapter-title" id="chapter-' . $chapter->ID . '">' . $chapter->post_title . '</h1>' . apply_filters('the_content', $chapter->post_content),
                'order' => $chapter->menu_order,
            );
        }

        // Structure the book content
        $content = array();

        // Cover
        if (!empty($featured_image['url'])) {
            $content['cover'] = apply_filters('tb_book_cover_rest', array(
                'html' => '<img class="book-cover" src="' . esc_attr($featured_image['url']) . '" alt="' . esc_attr($book->post_title) . '" />',
            ), $book_id);
        }

        // Title Page
        if (!empty($book->post_title) || !empty($meta['subtitle'])) {
            $content['title_page'] = apply_filters('tb_book_title_page_rest', array(
                'html' => '<h1 class="book-title">' . esc_html($book->post_title) . '</h1>' .
                          (!empty($meta['subtitle']) ? '<h2 class="book-subtitle">' . esc_html($meta['subtitle']) . '</h2>' : ''),
            ), $book_id);
        }

        // Author Page
        if (!empty($meta['author'])) {
            $author_names = is_array($meta['author']) ? $meta['author'] : array($meta['author']);
            $author_links = TB_Book::get_book_authors_links($book_id);
            $content['author_page'] = apply_filters('tb_book_author_page_rest', array(
                'author' => '<p class="book-author">By ' . implode(', ', $author_links) . '</p>',
            ), $book_id);
        }

        // Copyright Page
        $settings = get_option('total_book_settings', array());
        $disable_auto_copyright = isset($settings['disable_auto_copyright']) ? $settings['disable_auto_copyright'] : false;
        
        if (!empty($meta['publication_date']) || !empty($meta['author']) || !empty($meta['isbn']) || !empty($meta['publisher']) || !empty($meta['language'])) {
            $pub_date = !empty($meta['publication_date']) ? strtotime($meta['publication_date']) : false;
            $author_names = is_array($meta['author']) ? $meta['author'] : array($meta['author']);
            $first_author = !empty($author_names) ? $author_names[0] : '';
            
            $content['copyright_page'] = apply_filters('tb_book_copyright_page_rest', array(
                'html' => '<div class="book-copyright-page">' .
                    (!$disable_auto_copyright && !empty($first_author) ? '<p class="book-copyright">© ' . ($pub_date ? gmdate('Y', $pub_date) : '') . ' ' . esc_html($first_author) . '</p>' : '') .
                    (!empty($meta['isbn']) ? '<p class="book-isbn">ISBN: ' . esc_html($meta['isbn']) . '</p>' : '') .
                    (!empty($meta['publisher']) ? '<p class="book-publisher">Published by ' . esc_html($meta['publisher']) . '</p>' : '') .
                    ($pub_date ? '<p class="book-publication-date">' . gmdate('F j, Y', $pub_date) . '</p>' : '') .
                    (!empty($meta['language']) ? '<p class="book-language">Language: ' . esc_html($meta['language']) . '</p>' : '') .
                    '</div>',
            ), $book_id);
        }

        // Dedication Page
        if (!empty($meta['dedication'])) {
            $content['dedication_page'] = apply_filters('tb_book_dedication_rest', array(
                'html' => '<p class="book-dedication">' . esc_html($meta['dedication']) . '</p>',
            ), $book_id);
        }

        // Table of Contents Page
        if (!empty($chapter_data)) {
            $content['table_of_contents_page'] = apply_filters('tb_book_toc_rest', array(
                'html' => '<h1 class="book-toc-title">Table of Contents</h1><ul class="book-toc-list">' . implode('', array_map(function($chapter) {
                    return '<li>' . esc_html($chapter['title']) . '</li>';
                }, $chapter_data)) . '</ul>',
            ), $book_id);
        }

        // Main Body (always included if chapters exist)
        if (!empty($chapter_data)) {
            $content['main_body'] = apply_filters('tb_book_main_body_rest', array(
                'chapters' => $chapter_data,
            ), $book_id);
        }

        // Acknowledgments Page
        if (!empty($meta['acknowledgments'])) {
            $content['acknowledgments_page'] = apply_filters('tb_book_acknowledgments_rest', array(
                'html' => '<h1 class="book-acknowledgments-title">Acknowledgments</h1><p class="book-acknowledgments">' . esc_html($meta['acknowledgments']) . '</p>',
            ), $book_id);
        }

        // About Author Page
        if (!empty($meta['about_author'])) {
            $content['about_author_page'] = apply_filters('tb_book_about_author_rest', array(
                'html' => '<h1 class="book-about-author-title">About the Author</h1><p class="book-about-author">' . esc_html($meta['about_author']) . '</p>',
            ), $book_id);
        }

        // Description Page
        if (!empty($meta['description'])) {
            $content['description_page'] = apply_filters('tb_book_description_rest', array(
                'html' => '<h1 class="book-description-title">Description</h1><p class="book-description">' . esc_html($meta['description']) . '</p>',
            ), $book_id);
        }

        // Structure the book content
        $book_content = array(
            'id' => $book_id,
            'title' => apply_filters('tb_book_title_rest', $book->post_title, $book_id),
            'categories' => apply_filters('tb_book_categories_rest', $category_data, $book_id),
            'image_url' => apply_filters('tb_book_image_url_rest', $featured_image, $book_id),
            'table_of_contents' => apply_filters('tb_book_toc_rest', array_map(function($chapter) {
                return array(
                    'id' => $chapter['id'],
                    'title' => $chapter['title'],
                    'order' => $chapter['order'],
                );
            }, $chapter_data), $book_id),
            
            'content' => $content,
        );

        // Allow filtering of the entire book content
        $book_content = apply_filters('tb_book_content_rest', $book_content, $book_id);

        return rest_ensure_response($book_content);
    }

}

// Initialize the REST API
new Total_Book_REST_API(); 