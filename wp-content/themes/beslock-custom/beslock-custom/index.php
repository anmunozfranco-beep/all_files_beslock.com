<?php
/**
 * Child theme index.php proxy
 * Delegate rendering to parent theme's index.php so the parent (Kadence)
 * remains responsible for template resolution (including WooCommerce).
 * This file intentionally does not output static markup.
 */

// If the parent theme provides an index.php, load it so the parent handles output.
if ( file_exists( get_template_directory() . '/index.php' ) ) {
	load_template( get_template_directory() . '/index.php' );
	return;
}

// Fallback: behave as a minimal index that respects the loop.
get_header();
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		the_content();
	}
}
get_footer();
