<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/woocommerce/cart.php' );

// WooCommerce cart/shop related logic
if ( class_exists( 'WooCommerce' ) && WC() ) {
  // Redirect the WooCommerce Shop page to the front-page products portfolio section.
  add_action( 'template_redirect', function() {
    if ( function_exists( 'is_shop' ) && is_shop() ) {
      wp_safe_redirect( home_url( '/' ) . '#productos', 301 );
      exit;
    }
  }, 5 );

  // Ensure WooCommerce canonical URLs that expect a shop page don't break.
  add_filter( 'woocommerce_get_shop_page_id', function( $page_id ) {
    return 0;
  } );
}
