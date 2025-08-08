<?php
/*
 * Chapter Submodule
 */

Class TB_Chapter {
	public function __construct() {
		add_action('init', array($this, 'register_post_type'));
		add_action('manage_chapter_posts_columns', array($this, 'add_custom_columns'));
		add_action('manage_chapter_posts_custom_column', array($this, 'populate_custom_columns'), 10, 2);
		add_action('manage_edit-chapter_sortable_columns', array($this, 'make_columns_sortable'));
		add_action('pre_get_posts', array($this, 'modify_chapter_query'));
		add_action('restrict_manage_posts', array($this, 'add_book_filter'));
		add_filter('parse_query', array($this, 'filter_chapters_by_book'));
		add_action('wp_ajax_assign_chapter_to_book', array($this, 'ajax_assign_chapter_to_book'));
	}

	public function register_post_type() {
		$labels = array(
			'name'               => _x('Chapters', 'post type general name', 'total-book'),
			'singular_name'      => _x('Chapter', 'post type singular name', 'total-book'),
			'menu_name'          => _x('Chapters', 'admin menu', 'total-book'),
			'name_admin_bar'     => _x('Chapter', 'add new on admin bar', 'total-book'),
			'add_new'            => _x('Add New', 'chapter', 'total-book'),
			'add_new_item'       => __('Add New Chapter', 'total-book'),
			'new_item'           => __('New Chapter', 'total-book'),
			'edit_item'          => __('Edit Chapter', 'total-book'),
			'view_item'          => __('View Chapter', 'total-book'),
			'all_items'          => __('All Chapters', 'total-book'),
			'search_items'       => __('Search Chapters', 'total-book'),
			'not_found'          => __('No chapters found.', 'total-book'),
			'not_found_in_trash' => __('No chapters found in Trash.', 'total-book')
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=book',
			'query_var'          => true,
			'rewrite'            => array('slug' => 'chapter'),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
			'show_in_rest'       => true,
		);

		register_post_type('chapter', $args);
	}

	/**
	 * Add custom columns to the chapter list table
	 */
	public function add_custom_columns($columns) {
		// Insert the new columns after the title column
		$new_columns = array();
		
		foreach ($columns as $key => $value) {
			$new_columns[$key] = $value;
			if ($key === 'title') {
				$new_columns['parent_book'] = __('Parent Book', 'total-book');
				$new_columns['chapter_position'] = __('Position', 'total-book');
			}
		}
		
		return $new_columns;
	}

	/**
	 * Populate the custom columns with data
	 */
	public function populate_custom_columns($column, $post_id) {
		switch ($column) {
			case 'parent_book':
				$parent_id = get_post_field('post_parent', $post_id);
				if ($parent_id) {
					$parent_title = get_the_title($parent_id);
					$edit_link = get_edit_post_link($parent_id);
					echo '<a href="' . esc_url($edit_link) . '">' . esc_html($parent_title) . '</a>';
				} else {
					// Show assignment dropdown for orphaned chapters
					echo '<div class="assign-chapter-container">';
					echo '<button type="button" class="button button-small assign-chapter-btn" data-chapter-id="' . esc_attr($post_id) . '">' . esc_html__('Assign to Book', 'total-book') . '</button>';
					echo '<div class="assign-chapter-dropdown" style="display: none;">';
					echo '<select class="book-select" data-chapter-id="' . esc_attr($post_id) . '">';
					echo '<option value="">' . esc_html__('Select a book...', 'total-book') . '</option>';
					
					// Get all books
					$books = get_posts(array(
						'post_type' => 'book',
						'posts_per_page' => -1,
						'orderby' => 'title',
						'order' => 'ASC'
					));
					
					foreach ($books as $book) {
						echo '<option value="' . esc_attr($book->ID) . '">' . esc_html($book->post_title) . '</option>';
					}
					
					echo '</select>';
					echo '<button type="button" class="button button-primary assign-btn" data-chapter-id="' . esc_attr($post_id) . '">' . esc_html__('Assign', 'total-book') . '</button>';
					echo '<button type="button" class="button cancel-btn">' . esc_html__('Cancel', 'total-book') . '</button>';
					echo '</div>';
					echo '</div>';
				}
				break;
				
			case 'chapter_position':
				$parent_id = get_post_field('post_parent', $post_id);
				if ($parent_id) {
					// Get all chapters for this book ordered by menu_order
					$chapters = get_posts(array(
						'post_type' => 'chapter',
						'post_parent' => $parent_id,
						'posts_per_page' => -1,
						'orderby' => 'menu_order',
						'order' => 'ASC',
						'fields' => 'ids'
					));
					
					// Find the position of current chapter
					$position = array_search($post_id, $chapters);
					if ($position !== false) {
						echo '<span style="font-weight: bold; color: #2271b1;">' . esc_html($position + 1) . '</span>';
					} else {
						echo '<span style="color: #999;">-</span>';
					}
				} else {
					echo '<span style="color: #999;">-</span>';
				}
				break;
		}
	}

	/**
	 * Make columns sortable
	 */
	public function make_columns_sortable($columns) {
		$columns['parent_book'] = 'parent';
		$columns['chapter_position'] = 'menu_order';
		return $columns;
	}

	/**
	 * Modify the chapter query to group by parent book
	 */
	public function modify_chapter_query($query) {
		// Only modify queries on the chapter admin page
		if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'chapter') {
			return;
		}

		// Set default ordering to group by parent book, then by menu_order
		if (!$query->get('orderby')) {
			$query->set('orderby', array('post_parent' => 'ASC', 'menu_order' => 'ASC'));
		}
	}

	/**
	 * Add book filter dropdown
	 */
	public function add_book_filter() {
		global $typenow;
		
		if ($typenow !== 'chapter') {
			return;
		}

		// Get all books
		$books = get_posts(array(
			'post_type' => 'book',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC'
		));

		$selected = '';
		// Verify nonce before processing form data
		if (isset($_REQUEST['total_book_filter_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['total_book_filter_nonce'])), 'total_book_filter_nonce')) {
			$parent_book = sanitize_text_field(wp_unslash($_REQUEST['parent_book'] ?? ''));
			if (!empty($parent_book)) {
				$selected = $parent_book;
			}
		}
		?>
		<select name="parent_book">
			<option value=""><?php esc_html_e('All Books', 'total-book'); ?></option>
			<?php foreach ($books as $book) : ?>
				<option value="<?php echo esc_attr($book->ID); ?>" <?php selected($selected, $book->ID); ?>>
					<?php echo esc_html($book->post_title); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php wp_nonce_field('total_book_filter_nonce', 'total_book_filter_nonce'); ?>
		<?php
	}

	/**
	 * Filter chapters by selected book
	 */
	public function filter_chapters_by_book($query) {
		global $pagenow, $typenow;

		if ($pagenow === 'edit.php' && $typenow === 'chapter') {
			// Verify nonce for security
			if (isset($_REQUEST['total_book_filter_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['total_book_filter_nonce'])), 'total_book_filter_nonce')) {
				$parent_book = sanitize_text_field(wp_unslash($_REQUEST['parent_book'] ?? ''));
				if (!empty($parent_book)) {
					$query->set('post_parent', intval($parent_book));
				}
			}
		}
	}

	/**
	 * AJAX handler for assigning chapter to book
	 */
	public function ajax_assign_chapter_to_book() {
		check_ajax_referer('total_book_nonce', 'nonce');

		if (!isset($_POST['chapter_id']) || !isset($_POST['book_id'])) {
			wp_send_json_error('Missing required data');
		}

		$chapter_id = intval(wp_unslash($_POST['chapter_id']));
		$book_id = intval(wp_unslash($_POST['book_id']));

		if (!$chapter_id || !$book_id) {
			wp_send_json_error('Invalid data');
		}

		// Verify the book exists
		$book = get_post($book_id);
		if (!$book || $book->post_type !== 'book') {
			wp_send_json_error('Invalid book');
		}

		// Update the chapter's parent
		$result = wp_update_post(array(
			'ID' => $chapter_id,
			'post_parent' => $book_id
		));

		if (is_wp_error($result)) {
			wp_send_json_error($result->get_error_message());
		}

		// Get the updated chapter data
		$chapter = get_post($chapter_id);
		$book_title = get_the_title($book_id);
		$edit_link = get_edit_post_link($book_id);

		wp_send_json_success(array(
			// translators: %s is the book title
			'message' => sprintf(__('Chapter assigned to "%s"', 'total-book'), $book_title),
			'book_title' => $book_title,
			'edit_link' => $edit_link,
			'chapter_id' => $chapter_id
		));
	}
}

$chapter = new TB_Chapter();