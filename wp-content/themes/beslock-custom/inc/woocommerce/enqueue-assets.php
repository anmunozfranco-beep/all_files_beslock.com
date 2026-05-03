<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/woocommerce/enqueue-assets.php' );

/**
 * Minimal WooCommerce-related asset registration. Keeps handles available
 * for WooCommerce templates and product-card component.
 */
add_action( 'wp_enqueue_scripts', function() {
  $theme_dir = get_stylesheet_directory_uri();
  $theme_path = get_stylesheet_directory();

  // Ensure product-card CSS is available (style.css already contains globals)
  $pc_css = $theme_path . '/assets/css/product-card.css';
  if ( file_exists( $pc_css ) ) {
    wp_register_style( 'beslock-product-card', $theme_dir . '/assets/css/product-card.css', array( 'beslock-main-style' ), filemtime( $pc_css ) );
    wp_enqueue_style( 'beslock-product-card' );
  }
}, 20 );
