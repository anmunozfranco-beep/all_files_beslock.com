<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/features/product-features.php' );

// Product features hooks: CTA, badges, free shipping bar
if ( ! function_exists( 'beslock_product_cta' ) ) {
  function beslock_product_cta() {
    echo '<div class="beslock-product-cta"><a href="' . esc_url( wc_get_cart_url() ) . '" class="button">' . esc_html__( 'Ir al carrito', 'beslock' ) . '</a></div>';
  }
  add_action( 'beslock_after_product', 'beslock_product_cta' );
}

if ( ! function_exists( 'beslock_free_shipping_bar' ) ) {
  function beslock_free_shipping_bar() {
    $threshold = 50000;
    echo '<div class="beslock-free-shipping">' . sprintf( esc_html__( 'Envío gratis en compras superiores a %s', 'beslock' ), wc_price( $threshold ) ) . '</div>';
  }
  add_action( 'wp_footer', 'beslock_free_shipping_bar' );
}
