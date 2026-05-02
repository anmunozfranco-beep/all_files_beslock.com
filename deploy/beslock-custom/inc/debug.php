<?php
/**
 * Debug helpers (style queue dump).
 */

add_action( 'wp_print_styles', function() {
  if ( is_admin() ) {
    return;
  }
  global $wp_styles;
  if ( empty( $wp_styles ) ) {
    return;
  }
  $out = [];
  $out[] = "=== QUEUE ===";
  if ( ! empty( $wp_styles->queue ) ) {
    foreach ( $wp_styles->queue as $h ) {
      $r = isset( $wp_styles->registered[ $h ] ) ? $wp_styles->registered[ $h ] : null;
      $src = $r && ! empty( $r->src ) ? $r->src : 'inline/unknown';
      $out[] = "$h => $src";
    }
  }
  $out[] = "\n=== REGISTERED (selected) ===";
  foreach ( $wp_styles->registered as $handle => $obj ) {
    $src = isset( $obj->src ) && $obj->src ? $obj->src : 'inline/unknown';
    $out[] = "$handle => $src";
  }

  $dir = get_stylesheet_directory() . '/debug';
  if ( ! file_exists( $dir ) ) {
    wp_mkdir_p( $dir );
  }
  $file = $dir . '/enqueued-styles.log';
  @file_put_contents( $file, implode( "\n", $out ) );
}, 100 );
