<?php
/*
 * Chapter Submodule
 */

Class TB_Chapter {
	public function __construct() {
		add_action('init', array($this, 'register_post_type'));
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
}
$chapter = new TB_Chapter();