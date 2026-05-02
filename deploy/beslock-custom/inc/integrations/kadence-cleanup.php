<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/integrations/kadence-cleanup.php' );

// Remove or override Kadence-specific theme behaviors that conflict with Beslock layout
if ( ! function_exists( 'beslock_strip_kadence_css' ) ) {
  function beslock_strip_kadence_css() {
    wp_dequeue_style( 'kadence-blocks' );
    wp_dequeue_style( 'kadence' );
  }
  add_action( 'wp_enqueue_scripts', 'beslock_strip_kadence_css', 100 );
}

if ( ! function_exists( 'beslock_kadence_remove_header_hook' ) ) {
  function beslock_kadence_remove_header_hook() {
    remove_action( 'kadence_header', 'kadence_render_header' );
  }
}
