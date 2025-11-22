<?php
/*
 * Book Submodule
 */
if (!defined('ABSPATH')) {
    exit;
}

Class TTBP_Book {
	public function __construct() {
		add_action('init', array($this, 'ttbp_register_post_type'));
		add_action('wp_ajax_ttbp_add_chapter', array($this, 'ttbp_ajax_add_chapter'));
		add_action('wp_ajax_ttbp_delete_chapter', array($this, 'ttbp_ajax_delete_chapter'));
		add_action('wp_ajax_ttbp_update_chapter_order', array($this, 'ttbp_ajax_update_chapter_order'));
		add_action('wp_ajax_ttbp_get_authors', array($this, 'ttbp_ajax_get_authors'));
		add_action('admin_notices', array($this, 'display_admin_notices'));
		add_action('wp_trash_post', array($this, 'ttbp_trash_book'));
		add_action('untrash_post', array($this, 'ttbp_untrash_book'));
	}

	public function ttbp_untrash_book($post_id) {
		if (get_post_type($post_id) === 'ttbp-book') {
			$chapters = get_posts(array(
				'post_type' => 'ttbp_chapter',
				'post_parent' => $post_id,
				'posts_per_page' => -1
			));
		}
		foreach ($chapters as $chapter) {
			wp_untrash_post($chapter->ID);
		}
	}

	public function ttbp_trash_book($post_id) {
		if (get_post_type($post_id) === 'ttbp-book') {
			$chapters = get_posts(array(
				'post_type' => 'ttbp_chapter',
				'post_parent' => $post_id,
				'posts_per_page' => -1
			));
			foreach ($chapters as $chapter) {
				wp_trash_post($chapter->ID);
			}
		}
	}

	public function ttbp_register_post_type() {
		$labels = array(
			'name'               => _x('Books', 'post type general name', 'the-total-book-project'),
			'singular_name'      => _x('Book', 'post type singular name', 'the-total-book-project'),
			'menu_name'          => _x('Books', 'admin menu', 'the-total-book-project'),
			'name_admin_bar'     => _x('Book', 'add new on admin bar', 'the-total-book-project'),
			'add_new'            => _x('Add New', 'book', 'the-total-book-project'),
			'add_new_item'       => __('Add New Book', 'the-total-book-project'),
			'new_item'           => __('New Book', 'the-total-book-project'),
			'edit_item'          => __('Edit Book', 'the-total-book-project'),
			'view_item'          => __('View Book', 'the-total-book-project'),
			'all_items'          => __('All Books', 'the-total-book-project'),
			'search_items'       => __('Search Books', 'the-total-book-project'),
			'not_found'          => __('No books found.', 'the-total-book-project'),
			'not_found_in_trash' => __('No books found in Trash.', 'the-total-book-project')
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array('slug' => 'book'),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => true,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-book',
			'supports'           => array('title', 'thumbnail', 'excerpt', 'custom-fields'),
			'show_in_rest'       => true,
			'taxonomies'         => array('book_category', 'book_author'),
		);

		register_post_type('ttbp-book', $args);
		
		// Register Book Category Taxonomy
		$category_labels = array(
			'name'              => _x('Book Categories', 'taxonomy general name', 'the-total-book-project'),
			'singular_name'     => _x('Book Category', 'taxonomy singular name', 'the-total-book-project'),
			'search_items'      => __('Search Book Categories', 'the-total-book-project'),
			'all_items'         => __('All Book Categories', 'the-total-book-project'),
			'parent_item'       => __('Parent Book Category', 'the-total-book-project'),
			'parent_item_colon' => __('Parent Book Category:', 'the-total-book-project'),
			'edit_item'         => __('Edit Book Category', 'the-total-book-project'),
			'update_item'       => __('Update Book Category', 'the-total-book-project'),
			'add_new_item'      => __('Add New Book Category', 'the-total-book-project'),
			'new_item_name'     => __('New Book Category Name', 'the-total-book-project'),
			'menu_name'         => __('Categories', 'the-total-book-project'),
		);

		$category_args = array(
			'hierarchical'      => true,
			'labels'            => $category_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array('slug' => 'book-category'),
			'show_in_rest'      => true,
		);

		register_taxonomy('ttbp_book_category', array('ttbp-book'), $category_args);
		
		// Register Book Author Taxonomy
		$author_labels = array(
			'name'              => _x('Book Authors', 'taxonomy general name', 'the-total-book-project'),
			'singular_name'     => _x('Book Author', 'taxonomy singular name', 'the-total-book-project'),
			'search_items'      => __('Search Book Authors', 'the-total-book-project'),
			'all_items'         => __('All Book Authors', 'the-total-book-project'),
			'edit_item'         => __('Edit Book Author', 'the-total-book-project'),
			'update_item'       => __('Update Book Author', 'the-total-book-project'),
			'add_new_item'      => __('Add New Book Author', 'the-total-book-project'),
			'new_item_name'     => __('New Book Author Name', 'the-total-book-project'),
			'menu_name'         => __('Authors', 'the-total-book-project'),
		);

		$author_args = array(
			'hierarchical'      => false,
			'labels'            => $author_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array('slug' => 'book-author'),
			'show_in_rest'      => true,
		);

		register_taxonomy('ttbp_book_author', array('ttbp-book'), $author_args);
		
		// Add meta box for book details
		add_action('add_meta_boxes', array($this, 'ttbp_add_book_meta_boxes'));
		add_action('save_post', array($this, 'ttbp_save_book_meta'));
	}

	public function ttbp_add_book_meta_boxes() {
		add_meta_box(
			'ttbp_book_details',
			__('Book Details', 'the-total-book-project'),
			array($this, 'ttbp_render_book_meta_box'),
			'ttbp-book',
			'normal',
			'high'
		);

		add_meta_box(
			'ttbp_book_chapters',
			__('Book Chapters', 'the-total-book-project'),
			array($this, 'ttbp_render_chapters_meta_box'),
			'ttbp-book',
			'normal',
			'high'
		);

		// Remove the default taxonomy metabox for book_author
		remove_meta_box('tagsdiv-ttbp_book_author', 'ttbp-book', 'side');
	}

	public function ttbp_render_book_meta_box($post) {
		// Add nonce for security
		wp_nonce_field('ttbp_book_meta_box', 'ttbp_book_meta_box_nonce');

		// Get existing values
		$author = get_post_meta($post->ID, '_book_author', true);
		$isbn = get_post_meta($post->ID, '_book_isbn', true);
		$publication_date = get_post_meta($post->ID, '_book_publication_date', true);
		$publisher = get_post_meta($post->ID, '_book_publisher', true);
		$description = get_post_meta($post->ID, '_book_description', true);
		$subtitle = get_post_meta($post->ID, '_book_subtitle', true);
		$dedication = get_post_meta($post->ID, '_book_dedication', true);
		$acknowledgments = get_post_meta($post->ID, '_book_acknowledgments', true);
		$about_author = get_post_meta($post->ID, '_book_about_author', true);
		
		// Get all available authors (for possible future autocomplete, not used in tag mode)
		$all_authors = get_terms(array(
			'taxonomy' => 'ttbp_book_author',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC'
		));
		// Get current author terms
		$current_authors = wp_get_post_terms($post->ID, 'ttbp_book_author', array('fields' => 'names'));
		$current_author_names = implode(',', $current_authors);
		?>
		<div class="book-meta-fields">
			<p>
				<label for="book_subtitle"><?php esc_html_e('Subtitle', 'the-total-book-project'); ?></label>
				<input type="text" id="book_subtitle" name="book_subtitle" value="<?php echo esc_attr($subtitle); ?>" class="widefat">
			</p>
			<p>
				<label for="book_authors_tagify"><?php esc_html_e('Authors', 'the-total-book-project'); ?> <span style="color: red;">*</span></label>
				<input id="book_authors_tagify" class="widefat" value="<?php echo esc_attr($current_author_names); ?>">
				<p class="description">
					<?php esc_html_e('Type an author name and press Enter or comma. Add as many as you like. Names can include spaces and punctuation.', 'the-total-book-project'); ?>
				</p>
			</p>
			<p>
				<label for="book_isbn"><?php esc_html_e('ISBN', 'the-total-book-project'); ?></label>
				<input type="text" id="book_isbn" name="book_isbn" value="<?php echo esc_attr($isbn); ?>" class="widefat">
			</p>
			<p>
				<label for="book_publication_date"><?php esc_html_e('Publication Date', 'the-total-book-project'); ?></label>
				<input type="date" id="book_publication_date" name="book_publication_date" value="<?php echo esc_attr($publication_date); ?>" class="widefat">
			</p>
			<p>
				<label for="book_publisher"><?php esc_html_e('Publisher', 'the-total-book-project'); ?></label>
				<input type="text" id="book_publisher" name="book_publisher" value="<?php echo esc_attr($publisher); ?>" class="widefat">
			</p>
			<p>
				<label for="book_description"><?php esc_html_e('Description', 'the-total-book-project'); ?></label>
				<textarea id="book_description" name="book_description" class="widefat" rows="5"><?php echo esc_textarea($description); ?></textarea>
			</p>
			<p>
				<label for="book_dedication"><?php esc_html_e('Dedication', 'the-total-book-project'); ?></label>
				<textarea id="book_dedication" name="book_dedication" class="widefat" rows="3"><?php echo esc_textarea($dedication); ?></textarea>
			</p>
			<p>
				<label for="book_acknowledgments"><?php esc_html_e('Acknowledgments', 'the-total-book-project'); ?></label>
				<textarea id="book_acknowledgments" name="book_acknowledgments" class="widefat" rows="5"><?php echo esc_textarea($acknowledgments); ?></textarea>
			</p>
			<p>
				<label for="book_about_author"><?php esc_html_e('About The Author', 'the-total-book-project'); ?></label>
				<textarea id="book_about_author" name="book_about_author" class="widefat" rows="5"><?php echo esc_textarea($about_author); ?></textarea>
			</p>
		</div>
		<?php
	}

	public function ttbp_render_chapters_meta_box($post) {
		wp_nonce_field('ttbp_book_chapters_meta_box', 'ttbp_book_chapters_meta_box_nonce');
		
		// Get all chapters for this book
		$chapters = get_posts(array(
			'post_type' => 'ttbp_chapter',
			'post_parent' => $post->ID,
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC'
		));
		?>
		<div class="book-chapters-container">
			<div class="chapters-list">
				<?php if (!empty($chapters)) : ?>
					<ul class="chapters-sortable">
						<?php foreach ($chapters as $chapter) : ?>
							<li class="chapter-item" data-id="<?php echo esc_attr($chapter->ID); ?>">
								<span class="dashicons dashicons-menu"></span>
								<a href="<?php echo esc_url(get_edit_post_link($chapter->ID)); ?>"><?php echo esc_html($chapter->post_title); ?></a>
								<span class="chapter-actions">
									<a href="<?php echo esc_url(get_edit_post_link($chapter->ID)); ?>" class="button button-small"><?php esc_html_e('Edit', 'the-total-book-project'); ?></a>
									<a href="#" class="button button-small delete-chapter" data-id="<?php echo esc_attr($chapter->ID); ?>"><?php esc_html_e('Delete', 'the-total-book-project'); ?></a>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="no-chapters"><?php esc_html_e('No chapters added yet.', 'the-total-book-project'); ?></p>
				<?php endif; ?>
			</div>
			
			<div class="add-chapter-form">
				<h4><?php esc_html_e('Add New Chapter', 'the-total-book-project'); ?></h4>
				<p>
					<input type="text" id="new_chapter_title" class="widefat" placeholder="<?php esc_attr_e('Chapter Title', 'the-total-book-project'); ?>">
				</p>
				<p>
					<button type="button" class="button button-primary" id="add_chapter"><?php esc_html_e('Add Chapter', 'the-total-book-project'); ?></button>
				</p>
			</div>
		</div>
		<?php
	}

	public function ttbp_save_book_meta($post_id) {
		// Check if our nonce is set
		if (!isset($_POST['ttbp_book_meta_box_nonce'])) {
			return;
		}

		// Sanitize and verify that the nonce is valid
		$nonce = sanitize_text_field(wp_unslash($_POST['ttbp_book_meta_box_nonce']));
		if (!wp_verify_nonce($nonce, 'ttbp_book_meta_box')) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check the user's permissions
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Handle author taxonomy
		if (isset($_POST['book_authors_tagify_hidden'])) {
			$author_input = sanitize_text_field(wp_unslash($_POST['book_authors_tagify_hidden']));
			$author_names = array_map('sanitize_text_field', explode(',', $author_input));
			$author_names = array_filter($author_names, 'strlen');
			foreach ($author_names as $author_name) {
				$term = term_exists($author_name, 'ttbp_book_author');
				if (!$term) {
					wp_insert_term($author_name, 'ttbp_book_author');
				}
			}
			if (!empty($author_names)) {
				wp_set_object_terms($post_id, $author_names, 'ttbp_book_author');
			}
		}

		// Sanitize and save the data
		$fields = array(
			'book_isbn' => 'sanitize_text_field',
			'book_publication_date' => 'sanitize_text_field',
			'book_publisher' => 'sanitize_text_field',
			'book_description' => 'sanitize_textarea_field',
			'book_subtitle' => 'sanitize_text_field',
			'book_dedication' => 'sanitize_textarea_field',
			'book_acknowledgments' => 'sanitize_textarea_field',
			'book_about_author' => 'sanitize_textarea_field'
		);

		foreach ($fields as $field => $sanitize_callback) {
			if (isset($_POST[$field])) {
				$raw_value = sanitize_text_field(wp_unslash($_POST[$field]));
				$value = call_user_func($sanitize_callback, $raw_value);
				update_post_meta($post_id, '_' . $field, $value);
			}
		}

		// Save chapter order if it exists
		if (isset($_POST['chapter_order'])) {
			$order_input = sanitize_text_field(wp_unslash($_POST['chapter_order']));
			$chapter_order = json_decode($order_input, true);
			if (is_array($chapter_order)) {
				foreach ($chapter_order as $position => $chapter_id) {
					wp_update_post(array(
						'ID' => $chapter_id,
						'menu_order' => $position
					));
				}
			}
		}
	}

	public function display_admin_notices() {
		global $post;
		
		if (!$post || $post->post_type !== 'ttbp-book') {
			return;
		}
		
		$error_message = get_transient('book_author_error_' . $post->ID);
		if ($error_message) {
			delete_transient('book_author_error_' . $post->ID);
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html($error_message); ?></p>
			</div>
			<?php
		}
	}

	// Add AJAX handlers for chapter management
	public function ttbp_ajax_add_chapter() {
		check_ajax_referer('ttbp_nonce', 'nonce');

		if (!isset($_POST['book_id']) || !isset($_POST['title'])) {
			wp_send_json_error('Missing required data');
		}

		$book_id = intval(wp_unslash($_POST['book_id']));
		$title = sanitize_text_field(wp_unslash($_POST['title']));

		if (!$book_id || !$title) {
			wp_send_json_error('Invalid data');
		}

		// Check if user can edit the book
		if (!current_user_can('edit_post', $book_id)) {
			wp_send_json_error('Insufficient permissions');
		}

		$chapter_id = wp_insert_post(array(
			'post_title' => $title,
			'post_type' => 'ttbp_chapter',
			'post_parent' => $book_id,
			'post_status' => 'publish'
		));

		if (is_wp_error($chapter_id)) {
			wp_send_json_error($chapter_id->get_error_message());
		}

		wp_send_json_success(array(
			'id' => $chapter_id,
			'title' => $title,
			'edit_link' => get_edit_post_link($chapter_id, '')
		));
	}

	public function ttbp_ajax_delete_chapter() {
		check_ajax_referer('ttbp_nonce', 'nonce');

		if (!isset($_POST['chapter_id'])) {
			wp_send_json_error('Missing chapter ID');
		}

		$chapter_id = intval(wp_unslash($_POST['chapter_id']));
		if (!$chapter_id) {
			wp_send_json_error('Invalid chapter ID');
		}

		// Check if user can delete the chapter
		if (!current_user_can('delete_post', $chapter_id)) {
			wp_send_json_error('Insufficient permissions');
		}

		$result = wp_delete_post($chapter_id, true);
		if (!$result) {
			wp_send_json_error('Failed to delete chapter');
		}

		wp_send_json_success();
	}

	public function ttbp_ajax_update_chapter_order() {
		check_ajax_referer('ttbp_nonce', 'nonce');

		if (!isset($_POST['order'])) {
			wp_send_json_error('Missing order data');
		}

		$order_input = sanitize_text_field(wp_unslash($_POST['order']));
		$order = json_decode($order_input, true);
		if (!is_array($order)) {
			wp_send_json_error('Invalid order data');
		}

		// Verify user can edit all chapters (check parent book for each)
		foreach ($order as $position => $chapter_id) {
			$chapter = get_post($chapter_id);
			if (!$chapter || $chapter->post_type !== 'ttbp_chapter') {
				wp_send_json_error('Invalid chapter ID');
			}
			
			// Check if user can edit the parent book
			if (!current_user_can('edit_post', $chapter->post_parent)) {
				wp_send_json_error('Insufficient permissions');
			}
		}

		foreach ($order as $position => $chapter_id) {
			wp_update_post(array(
				'ID' => $chapter_id,
				'menu_order' => $position
			));
		}

		wp_send_json_success();
	}

	public function ttbp_ajax_get_authors() {
		check_ajax_referer('ttbp_nonce', 'nonce');

		// Check if user has capability to edit books
		if (!current_user_can('edit_posts')) {
			wp_send_json_error('Insufficient permissions');
		}

		$search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';

		$args = array(
			'taxonomy' => 'ttbp_book_author',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC',
			'number' => 20 // Limit results for performance
		);

		// Add search if provided
		if (!empty($search)) {
			$args['name__like'] = $search;
		}

		$authors = get_terms($args);

		if (is_wp_error($authors)) {
			wp_send_json_error('Failed to fetch authors');
		}

		$author_list = array();
		foreach ($authors as $author) {
			$author_list[] = array(
				'value' => $author->name,
				'title' => $author->name,
				'count' => $author->count
			);
		}

		wp_send_json_success($author_list);
	}

	/**
	 * Get book authors (supports both taxonomy and legacy field)
	 */
	public static function get_book_authors($book_id) {
		// First try to get authors from taxonomy
		$author_terms = wp_get_post_terms($book_id, 'ttbp_book_author', array('fields' => 'names'));
		$author_terms = array_filter($author_terms, 'strlen');
		if (!empty($author_terms)) {
			return $author_terms;
		}
		return array();
	}

	/**
	 * Get book authors as links (for display)
	 */
	public static function get_book_authors_links($book_id) {
		$authors = self::get_book_authors($book_id);
		$authors = array_filter($authors, 'strlen');
		$author_links = array();
		foreach ($authors as $author) {
			// Try to find the author term
			$term = get_term_by('name', $author, 'ttbp_book_author');
			if ($term && !is_wp_error($term)) {
				$author_links[] = sprintf(
					'<a href="%s" class="book-author-link">%s</a>',
					esc_url(get_term_link($term)),
					esc_html($author)
				);
			} else {
				// Fallback to plain text if no term exists
				$author_links[] = esc_html($author);
			}
		}
		return $author_links;
	}

	/**
	 * Migrate legacy author field to taxonomy
	 */
	public static function migrate_legacy_authors() {
		$books = get_posts(array(
			'post_type' => 'ttbp-book',
			'posts_per_page' => -1,
			'post_status' => 'any'
		));
		
		$migrated_count = 0;
		
		foreach ($books as $book) {
			$legacy_author = get_post_meta($book->ID, '_book_author', true);
			
			if (!empty($legacy_author)) {
				// Check if author term already exists
				$existing_terms = wp_get_post_terms($book->ID, 'ttbp_book_author');
				
				if (empty($existing_terms) || is_wp_error($existing_terms)) {
					// Create author term if it doesn't exist
					$term = term_exists($legacy_author, 'ttbp_book_author');
					if (!$term) {
						$term = wp_insert_term($legacy_author, 'ttbp_book_author');
					}
					
					if (!is_wp_error($term)) {
						// Set the author term for this book
						wp_set_object_terms($book->ID, $term['term_id'], 'ttbp_book_author');
						$migrated_count++;
					}
				}
			}
		}
		
		return $migrated_count;
	}
}
$ttbp_book = new TTBP_Book();