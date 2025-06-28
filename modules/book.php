<?php
/*
 * Book Submodule
 */

Class TB_Book {
	public function __construct() {
		add_action('init', array($this, 'register_post_type'));
		add_action('wp_ajax_add_chapter', array($this, 'ajax_add_chapter'));
		add_action('wp_ajax_delete_chapter', array($this, 'ajax_delete_chapter'));
		add_action('wp_ajax_update_chapter_order', array($this, 'ajax_update_chapter_order'));
		add_action('admin_notices', array($this, 'display_admin_notices'));
	}

	public function register_post_type() {
		$labels = array(
			'name'               => _x('Books', 'post type general name', 'total-book'),
			'singular_name'      => _x('Book', 'post type singular name', 'total-book'),
			'menu_name'          => _x('Books', 'admin menu', 'total-book'),
			'name_admin_bar'     => _x('Book', 'add new on admin bar', 'total-book'),
			'add_new'            => _x('Add New', 'book', 'total-book'),
			'add_new_item'       => __('Add New Book', 'total-book'),
			'new_item'           => __('New Book', 'total-book'),
			'edit_item'          => __('Edit Book', 'total-book'),
			'view_item'          => __('View Book', 'total-book'),
			'all_items'          => __('All Books', 'total-book'),
			'search_items'       => __('Search Books', 'total-book'),
			'not_found'          => __('No books found.', 'total-book'),
			'not_found_in_trash' => __('No books found in Trash.', 'total-book')
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

		register_post_type('book', $args);
		
		// Register Book Category Taxonomy
		$category_labels = array(
			'name'              => _x('Book Categories', 'taxonomy general name', 'total-book'),
			'singular_name'     => _x('Book Category', 'taxonomy singular name', 'total-book'),
			'search_items'      => __('Search Book Categories', 'total-book'),
			'all_items'         => __('All Book Categories', 'total-book'),
			'parent_item'       => __('Parent Book Category', 'total-book'),
			'parent_item_colon' => __('Parent Book Category:', 'total-book'),
			'edit_item'         => __('Edit Book Category', 'total-book'),
			'update_item'       => __('Update Book Category', 'total-book'),
			'add_new_item'      => __('Add New Book Category', 'total-book'),
			'new_item_name'     => __('New Book Category Name', 'total-book'),
			'menu_name'         => __('Categories', 'total-book'),
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

		register_taxonomy('book_category', array('book'), $category_args);
		
		// Register Book Author Taxonomy
		$author_labels = array(
			'name'              => _x('Book Authors', 'taxonomy general name', 'total-book'),
			'singular_name'     => _x('Book Author', 'taxonomy singular name', 'total-book'),
			'search_items'      => __('Search Book Authors', 'total-book'),
			'all_items'         => __('All Book Authors', 'total-book'),
			'edit_item'         => __('Edit Book Author', 'total-book'),
			'update_item'       => __('Update Book Author', 'total-book'),
			'add_new_item'      => __('Add New Book Author', 'total-book'),
			'new_item_name'     => __('New Book Author Name', 'total-book'),
			'menu_name'         => __('Authors', 'total-book'),
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

		register_taxonomy('book_author', array('book'), $author_args);
		
		// Add meta box for book details
		add_action('add_meta_boxes', array($this, 'add_book_meta_boxes'));
		add_action('save_post', array($this, 'save_book_meta'));
	}

	public function add_book_meta_boxes() {
		add_meta_box(
			'book_details',
			__('Book Details', 'total-book'),
			array($this, 'render_book_meta_box'),
			'book',
			'normal',
			'high'
		);

		add_meta_box(
			'book_chapters',
			__('Book Chapters', 'total-book'),
			array($this, 'render_chapters_meta_box'),
			'book',
			'normal',
			'high'
		);

		// Remove the default taxonomy metabox for book_author
		remove_meta_box('tagsdiv-book_author', 'book', 'side');
	}

	public function render_book_meta_box($post) {
		// Add nonce for security
		wp_nonce_field('book_meta_box', 'book_meta_box_nonce');

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
			'taxonomy' => 'book_author',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC'
		));
		// Get current author terms
		$current_authors = wp_get_post_terms($post->ID, 'book_author', array('fields' => 'names'));
		$current_author_names = implode(',', $current_authors);
		?>
		<div class="book-meta-fields">
			<p>
				<label for="book_subtitle"><?php _e('Subtitle', 'total-book'); ?></label>
				<input type="text" id="book_subtitle" name="book_subtitle" value="<?php echo esc_attr($subtitle); ?>" class="widefat">
			</p>
			<p>
				<label for="book_authors_tagify"><?php _e('Authors', 'total-book'); ?> <span style="color: red;">*</span></label>
				<input id="book_authors_tagify" class="widefat" value="<?php echo esc_attr($current_author_names); ?>">
				<p class="description">
					<?php _e('Type an author name and press Enter or comma. Add as many as you like. Names can include spaces and punctuation.', 'total-book'); ?>
				</p>
			</p>
			<p>
				<label for="book_isbn"><?php _e('ISBN', 'total-book'); ?></label>
				<input type="text" id="book_isbn" name="book_isbn" value="<?php echo esc_attr($isbn); ?>" class="widefat">
			</p>
			<p>
				<label for="book_publication_date"><?php _e('Publication Date', 'total-book'); ?></label>
				<input type="date" id="book_publication_date" name="book_publication_date" value="<?php echo esc_attr($publication_date); ?>" class="widefat">
			</p>
			<p>
				<label for="book_publisher"><?php _e('Publisher', 'total-book'); ?></label>
				<input type="text" id="book_publisher" name="book_publisher" value="<?php echo esc_attr($publisher); ?>" class="widefat">
			</p>
			<p>
				<label for="book_description"><?php _e('Description', 'total-book'); ?></label>
				<textarea id="book_description" name="book_description" class="widefat" rows="5"><?php echo esc_textarea($description); ?></textarea>
			</p>
			<p>
				<label for="book_dedication"><?php _e('Dedication', 'total-book'); ?></label>
				<textarea id="book_dedication" name="book_dedication" class="widefat" rows="3"><?php echo esc_textarea($dedication); ?></textarea>
			</p>
			<p>
				<label for="book_acknowledgments"><?php _e('Acknowledgments', 'total-book'); ?></label>
				<textarea id="book_acknowledgments" name="book_acknowledgments" class="widefat" rows="5"><?php echo esc_textarea($acknowledgments); ?></textarea>
			</p>
			<p>
				<label for="book_about_author"><?php _e('About The Author', 'total-book'); ?></label>
				<textarea id="book_about_author" name="book_about_author" class="widefat" rows="5"><?php echo esc_textarea($about_author); ?></textarea>
			</p>
		</div>
		<link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
		<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var input = document.getElementById('book_authors_tagify');
			var tagify = new Tagify(input, {
				whitelist: [], // Could be populated with $all_authors if desired
				maxTags: 10,
				trim: true,
				duplicates: false,
				placeholder: '<?php echo esc_js(__('Type author name and press Enter', 'total-book')); ?>',
			});

			$('#post').on('submit', function(e) {
				var tagData = tagify.value;
				if (!tagData || tagData.length === 0) {
					e.preventDefault();
					alert('<?php echo esc_js(__('At least one author is required. Please add an author.', 'total-book')); ?>');
					input.focus();
					return false;
				}
				// Before submit, set a hidden field with the author names (comma separated)
				if ($('#book_authors_hidden').length === 0) {
					$('<input>').attr({type: 'hidden', id: 'book_authors_hidden', name: 'book_authors_tagify_hidden'}).appendTo($(this));
				}
				$('#book_authors_hidden').val(tagData.map(function(tag){return tag.value;}).join(','));
				// Remove name attribute from the visible input so only the hidden field is submitted
				$('#book_authors_tagify').removeAttr('name');
			});
		});
		</script>
		<?php
	}

	public function render_chapters_meta_box($post) {
		wp_nonce_field('book_chapters_meta_box', 'book_chapters_meta_box_nonce');
		
		// Get all chapters for this book
		$chapters = get_posts(array(
			'post_type' => 'chapter',
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
								<a href="<?php echo get_edit_post_link($chapter->ID); ?>"><?php echo esc_html($chapter->post_title); ?></a>
								<span class="chapter-actions">
									<a href="<?php echo get_edit_post_link($chapter->ID); ?>" class="button button-small"><?php _e('Edit', 'total-book'); ?></a>
									<a href="#" class="button button-small delete-chapter" data-id="<?php echo esc_attr($chapter->ID); ?>"><?php _e('Delete', 'total-book'); ?></a>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="no-chapters"><?php _e('No chapters added yet.', 'total-book'); ?></p>
				<?php endif; ?>
			</div>
			
			<div class="add-chapter-form">
				<h4><?php _e('Add New Chapter', 'total-book'); ?></h4>
				<p>
					<input type="text" id="new_chapter_title" class="widefat" placeholder="<?php esc_attr_e('Chapter Title', 'total-book'); ?>">
				</p>
				<p>
					<button type="button" class="button button-primary" id="add_chapter"><?php _e('Add Chapter', 'total-book'); ?></button>
				</p>
			</div>
		</div>
		<?php
	}

	public function save_book_meta($post_id) {
		// Check if our nonce is set
		if (!isset($_POST['book_meta_box_nonce'])) {
			return;
		}

		// Verify that the nonce is valid
		if (!wp_verify_nonce($_POST['book_meta_box_nonce'], 'book_meta_box')) {
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
			$author_names = array_map('sanitize_text_field', explode(',', $_POST['book_authors_tagify_hidden']));
			$author_names = array_filter($author_names, 'strlen');
			foreach ($author_names as $author_name) {
				$term = term_exists($author_name, 'book_author');
				if (!$term) {
					wp_insert_term($author_name, 'book_author');
				}
			}
			if (!empty($author_names)) {
				wp_set_object_terms($post_id, $author_names, 'book_author');
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
				$value = call_user_func($sanitize_callback, $_POST[$field]);
				update_post_meta($post_id, '_' . $field, $value);
			}
		}

		// Save chapter order if it exists
		if (isset($_POST['chapter_order'])) {
			$chapter_order = json_decode(stripslashes($_POST['chapter_order']), true);
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
		
		if (!$post || $post->post_type !== 'book') {
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
	public function ajax_add_chapter() {
		check_ajax_referer('total_book_nonce', 'nonce');

		$book_id = intval($_POST['book_id']);
		$title = sanitize_text_field($_POST['title']);

		if (!$book_id || !$title) {
			wp_send_json_error('Invalid data');
		}

		$chapter_id = wp_insert_post(array(
			'post_title' => $title,
			'post_type' => 'chapter',
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

	public function ajax_delete_chapter() {
		check_ajax_referer('total_book_nonce', 'nonce');

		$chapter_id = intval($_POST['chapter_id']);
		if (!$chapter_id) {
			wp_send_json_error('Invalid chapter ID');
		}

		$result = wp_delete_post($chapter_id, true);
		if (!$result) {
			wp_send_json_error('Failed to delete chapter');
		}

		wp_send_json_success();
	}

	public function ajax_update_chapter_order() {
		check_ajax_referer('total_book_nonce', 'nonce');

		$order = json_decode(stripslashes($_POST['order']), true);
		if (!is_array($order)) {
			wp_send_json_error('Invalid order data');
		}

		foreach ($order as $position => $chapter_id) {
			wp_update_post(array(
				'ID' => $chapter_id,
				'menu_order' => $position
			));
		}

		wp_send_json_success();
	}

	/**
	 * Get book authors (supports both taxonomy and legacy field)
	 */
	public static function get_book_authors($book_id) {
		// First try to get authors from taxonomy
		$author_terms = wp_get_post_terms($book_id, 'book_author', array('fields' => 'names'));
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
			$term = get_term_by('name', $author, 'book_author');
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
			'post_type' => 'book',
			'posts_per_page' => -1,
			'post_status' => 'any'
		));
		
		$migrated_count = 0;
		
		foreach ($books as $book) {
			$legacy_author = get_post_meta($book->ID, '_book_author', true);
			
			if (!empty($legacy_author)) {
				// Check if author term already exists
				$existing_terms = wp_get_post_terms($book->ID, 'book_author');
				
				if (empty($existing_terms) || is_wp_error($existing_terms)) {
					// Create author term if it doesn't exist
					$term = term_exists($legacy_author, 'book_author');
					if (!$term) {
						$term = wp_insert_term($legacy_author, 'book_author');
					}
					
					if (!is_wp_error($term)) {
						// Set the author term for this book
						wp_set_object_terms($book->ID, $term['term_id'], 'book_author');
						$migrated_count++;
					}
				}
			}
		}
		
		return $migrated_count;
	}
}
$tb_book = new TB_Book();