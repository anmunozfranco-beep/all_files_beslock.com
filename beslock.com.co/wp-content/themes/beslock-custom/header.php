<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Performance: preconnect a CDN / fonts -->
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Preload hero poster image on front page to improve LCP (si existe) -->
  <?php if ( function_exists( 'is_front_page' ) && is_front_page() ) : 
    $hero_poster = get_stylesheet_directory_uri() . '/assets/images/hero-poster.webp';
    // Sólo imprimir preload si el archivo probablemente exista (no hacer comprobación server-side aquí)
  ?>
    <link rel="preload" as="image" href="<?php echo esc_url( $hero_poster ); ?>">
  <?php endif; ?>

  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="header">
  <div class="u-container header__bar">
    <button id="menuBtn" class="header__icon header__icon--menu" aria-controls="mobileDrawer" aria-expanded="false" aria-label="<?php esc_attr_e('Open menu', 'beslock'); ?>">&#9776;</button>

    <a href="<?php echo esc_url( home_url('/') ); ?>" class="header__logo">
      <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo-green.png' ); ?>" alt="<?php esc_attr_e('BESLOCK Logo', 'beslock'); ?>" />
    </a>

    <?php
    // Use WooCommerce cart URL when available; fallback to /cart
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart' );
    $cart_count = 0;
    if ( function_exists( 'WC' ) && WC()->cart ) {
      $cart_count = (int) WC()->cart->get_cart_contents_count();
    }
    ?>
    <a href="<?php echo esc_url( $cart_url ); ?>" class="header__icon header__icon--cart" aria-label="<?php esc_attr_e('Go to cart', 'beslock'); ?>">
      <i class="bi bi-cart" aria-hidden="true"></i>
      <?php if ( $cart_count > 0 ) : ?>
        <span class="header__cart-count" aria-hidden="true"><?php echo esc_html( $cart_count ); ?></span>
      <?php endif; ?>
      <span class="screen-reader-text"><?php esc_html_e('Go to cart', 'beslock'); ?></span>
    </a>
  </div>
</header>
 

<?php
// incluir el partial del drawer si existe (safe)
if ( file_exists( get_stylesheet_directory() . '/templates/menu-simple.php' ) ) {
  get_template_part( 'templates/menu-simple' );
}
?>
