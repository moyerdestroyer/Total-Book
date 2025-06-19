<?php
/**
 * Template for displaying the book reader
 */

get_header();
//get the book id
$book_id = get_the_ID();
?>

<div id="book-reader" data-book-id="<?php echo esc_attr($book_id); ?>"></div>

<?php get_footer(); ?>