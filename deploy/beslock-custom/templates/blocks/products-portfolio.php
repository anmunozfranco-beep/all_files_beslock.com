<?php
/**
 * products-portfolio.php
 *
 * Front-page product grid — must use the underscore variants located in /assets/images/
 * (these are the larger/front-page images reserved for the portfolio).
 */

$products = [
  [
    'name'  => 'e-Nova',
    'desc'  => "Acceso inteligente sin llaves para tu día a día.\nHuella y control simple para moverte con libertad y tranquilidad.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-nova_.webp',
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

echo '<section id="productos" class="products-portfolio section reveal"><div class="u-container products-portfolio__grid">';
  foreach ($products as $product) {
  // Try to map this portfolio entry to a WooCommerce product by slug/title.
  $map_slug = sanitize_title( $product['name'] );
  // Prefer mapping stored by the import plugin for reliability on live
  $mapping = get_option( 'beslock_portfolio_mapping', array() );
  $found = array();
  if ( ! empty( $mapping ) && isset( $mapping[ $map_slug ] ) ) {
    $pid = intval( $mapping[ $map_slug ] );
    $pobj = get_post( $pid );
    if ( $pobj && $pobj->post_type === 'product' ) {
      $found = array( $pobj );
    }
  }
  if ( empty( $found ) ) {
    $found = get_posts( array( 'post_type' => 'product', 'name' => $map_slug, 'posts_per_page' => 1 ) );
  }
  if ( empty( $found ) ) {
    // Fallback: try search by product name (WP search)
    $found = get_posts( array( 'post_type' => 'product', 's' => $product['name'], 'posts_per_page' => 1 ) );
  }

  if ( ! empty( $found ) ) {
    $pobj = $found[0];
    $product['name'] = $pobj->post_title;
    $product['link'] = get_permalink( $pobj->ID );
    $product['product_id'] = $pobj->ID;
    // If rendering within the cart context, provide a formatted price HTML
    // and hide the description so the product-card shows the price instead
    // of the description (cart-specific behaviour).
    if ( function_exists( 'is_cart' ) && is_cart() ) {
      if ( function_exists( 'wc_get_product' ) && ! empty( $product['product_id'] ) ) {
        $wc_tmp = wc_get_product( intval( $product['product_id'] ) );
        $product['price'] = $wc_tmp ? $wc_tmp->get_price_html() : '';
      }
      $product['show_desc'] = false;
    }
  }

  set_query_var('product', $product);
  get_template_part('templates/blocks/product-card');
}
echo '</div></section>';
?>