<?php
/**
 * WooCommerce integration and tweaks.
 */

// Declare WooCommerce support for the child theme if not already present.
add_action( 'after_setup_theme', function() {
  if ( ! current_theme_supports( 'woocommerce' ) ) {
    add_theme_support( 'woocommerce' );
  }
}, 11 );

// Change single product add-to-cart button text to Spanish
add_filter( 'woocommerce_product_single_add_to_cart_text', function( $text ) {
  return 'Agregar al carrito';
} );

// If the parent theme or plugins enable WooCommerce gallery lightbox/zoom
add_action( 'after_setup_theme', function() {
  if ( function_exists( 'remove_theme_support' ) ) {
    remove_theme_support( 'wc-product-gallery-zoom' );
    remove_theme_support( 'wc-product-gallery-lightbox' );
    remove_theme_support( 'wc-product-gallery-slider' );
  }
}, 20 );

/**
 * Redirect the WooCommerce Shop page to the front-page products portfolio section.
 */
add_action( 'template_redirect', function() {
  if ( function_exists( 'is_shop' ) && is_shop() ) {
    wp_safe_redirect( home_url( '/' ) . '#productos', 301 );
    exit;
  }
}, 5 );

/**
 * Ensure WooCommerce canonical URLs that expect a shop page don't break.
 */
add_filter( 'woocommerce_get_shop_page_id', function( $page_id ) {
  return 0;
} );
