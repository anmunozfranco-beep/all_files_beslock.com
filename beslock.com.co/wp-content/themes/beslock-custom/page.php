<?php
/**
 * Child theme page.php proxy
 * Delegate rendering to parent theme's page.php so the parent (Kadence)
 * remains responsible for layout and template resolution for pages.
 * This file intentionally does not output custom markup.
 */

/**
 * Minimal child `page.php` — do NOT load parent templates manually.
 * Use the standard WP loop so parent (Kadence) and plugins (WooCommerce)
 * can fully control layout and content rendering.
 */
get_header();
if ( have_posts() ) {
  while ( have_posts() ) {
    the_post();
    the_content();
  }
}
get_footer();
