<?php
/**
 * products-portfolio.php
 *
 * Front-page product grid — must use the underscore variants located in /assets/images/
 * (these are the larger/front-page images reserved for the portfolio).
 */

// Fallback static product data (kept for environments without WooCommerce).
$static_products = [
  [
    'name'  => 'e-Nova',
    'desc'  => "Acceso inteligente sin llaves para tu día a día.\nHuella y control simple para moverte con libertad y tranquilidad.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-nova_.webp',
    // image filenames relative to theme assets (used by the importer)
    'images' => [ 'e-nova_.webp', 'e-nova_s.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Orbit',
    'desc'  => "Mira y controla quién llega a tu puerta, estés donde estés.\nSeguridad avanzada con video y acceso inteligente desde tu celular.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-orbit_.webp',
    'images' => [ 'e-orbit_.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Touch',
    'desc'  => "Acceso fácil para espacios compartidos.\nClave y huella para que todos puedan entrar, sin complicaciones.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-touch_.webp',
    'images' => [ 'e-touch_.webp', 'e-touch_c.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Flex',
    'desc'  => "Ideal para recibir y dar acceso sin estar presente.\nCódigos temporales, huella y control remoto para estancias cortas.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-flex_.webp',
    'images' => [ 'e-flex_.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Shield',
    'desc'  => "Protección inteligente para tu hogar.\nSeguridad robusta con acceso electrónico y respaldo mecánico.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-shield_.webp',
    'images' => [ 'e-shield_.webp', 'e-shield_e.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Prime',
    'desc'  => "Control total para accesos exigentes.\nGestión avanzada de usuarios para espacios de alto flujo.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-prime_.webp',
    'images' => [ 'e-prime_.webp' ],
    'link'  => '#'
  ],
];

// If WooCommerce is active, read from WC products. Otherwise fall back to the static array.
$products = [];
if ( class_exists('WooCommerce') ) {
  $wc_args = [ 'limit' => 6, 'status' => 'publish' ];
  $wc_products = wc_get_products( $wc_args );
  if ( ! empty( $wc_products ) ) {
    foreach ( $wc_products as $wp ) {
      $id = is_object( $wp ) && method_exists( $wp, 'get_id' ) ? $wp->get_id() : intval( $wp );
      $name = is_object( $wp ) && method_exists( $wp, 'get_name' ) ? $wp->get_name() : get_the_title( $id );
      $desc = is_object( $wp ) && method_exists( $wp, 'get_short_description' ) ? $wp->get_short_description() : '';
      if ( empty( $desc ) && is_object( $wp ) && method_exists( $wp, 'get_description' ) ) $desc = $wp->get_description();
      $image_url = get_the_post_thumbnail_url( $id, 'full' );
      $gallery_ids = get_post_meta( $id, '_product_image_gallery', true );
      $images = [];
      if ( $gallery_ids ) {
        foreach ( explode( ',', $gallery_ids ) as $aid ) {
          $src = wp_get_attachment_url( intval( $aid ) );
          if ( $src ) $images[] = $src;
        }
      }
      // Ensure the featured image is the first image from server output
      if ( $image_url ) array_unshift( $images, $image_url );

      $products[] = [
        'name'   => $name,
        'desc'   => $desc,
        'image'  => $image_url ?: $static_products[0]['image'],
        'images' => $images ?: $static_products[0]['images'],
        'link'   => get_permalink( $id ),
      ];
    }
  }
}

// final fallback to static if products is still empty
if ( empty( $products ) ) {
  $products = $static_products;
}

echo '<section class="products-portfolio section reveal"><div class="u-container products-portfolio__grid">';
foreach ($products as $product) {
  set_query_var('product', $product);
  get_template_part('templates/blocks/product-card');
}
echo '</div></section>';
?>