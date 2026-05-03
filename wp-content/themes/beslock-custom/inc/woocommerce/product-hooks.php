<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/woocommerce/product-hooks.php' );

// Product-specific WooCommerce filters and hooks
if ( class_exists( 'WooCommerce' ) && WC() ) {
  // Change single product add-to-cart button text to Spanish
  if ( ! has_filter( 'woocommerce_product_single_add_to_cart_text' ) ) {
    add_filter( 'woocommerce_product_single_add_to_cart_text', function( $text ) {
      return 'Agregar al carrito';
    } );
  }
}
