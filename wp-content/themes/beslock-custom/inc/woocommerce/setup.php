<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/woocommerce/setup.php' );

// WooCommerce theme support and gallery tweaks
if ( class_exists( 'WooCommerce' ) && WC() ) {
  add_action( 'after_setup_theme', function() {
    if ( ! current_theme_supports( 'woocommerce' ) ) {
      add_theme_support( 'woocommerce' );
    }
  }, 11 );

  add_action( 'after_setup_theme', function() {
    if ( function_exists( 'remove_theme_support' ) ) {
      remove_theme_support( 'wc-product-gallery-zoom' );
      remove_theme_support( 'wc-product-gallery-lightbox' );
      remove_theme_support( 'wc-product-gallery-slider' );
    }
  }, 20 );

  // Remove default WooCommerce empty cart message to avoid duplication
  add_action( 'init', function() {
    if ( function_exists( 'remove_action' ) ) {
      // wc_empty_cart_message is added by WooCommerce; remove it so theme template controls output
      remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
    }
  } );
}
