<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/debug/debug.php' );

if ( ! function_exists( 'beslock_log_enqueued_styles' ) ) {
  function beslock_log_enqueued_styles() {
    $styles = wp_styles();
    $out = array();
    if ( isset( $styles->registered ) && is_array( $styles->registered ) ) {
      foreach ( $styles->registered as $handle => $obj ) {
        $out[] = sprintf( "%s => %s", $handle, isset( $obj->src ) ? $obj->src : '' );
      }
    }
    $log_dir = WP_CONTENT_DIR . '/debug';
    if ( ! file_exists( $log_dir ) ) {
      wp_mkdir_p( $log_dir );
    }
    file_put_contents( $log_dir . '/enqueued-styles.log', implode( PHP_EOL, $out ) );
  }
  add_action( 'wp_print_styles', 'beslock_log_enqueued_styles' );
}
