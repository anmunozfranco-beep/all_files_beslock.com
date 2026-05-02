<?php
/**
 * Header Widget Template Part
 * Reusable header used on frontpage and insertable via shortcode or PHP.
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

$home_url = esc_url( home_url( '/' ) );
$logo_src = esc_url( get_stylesheet_directory_uri() . '/assets/images/logo-green.png' );
$site_name = get_bloginfo( 'name' );
?>
<header class="header">
  <div class="u-container header__bar">
    <button id="menuBtn" class="header__icon header__icon--menu" aria-controls="mobileDrawer" aria-expanded="false" aria-label="Open menu">&#9776;</button>

    <a href="<?php echo $home_url; ?>" class="header__logo">
      <img src="<?php echo $logo_src; ?>" alt="<?php echo esc_attr( $site_name ); ?>">
    </a>

    <?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
      <?php $cart_url = esc_url( wc_get_cart_url() ); ?>
      <a class="header__icon header__icon--cart" href="<?php echo $cart_url; ?>" aria-label="Go to cart">
        <i class="bi bi-cart" aria-hidden="true"></i>
        <?php if ( class_exists( 'WooCommerce' ) && WC()->cart && WC()->cart->get_cart_contents_count() > 0 ) : ?>
          <span class="header__cart-count" aria-hidden="true"><?php echo intval( WC()->cart->get_cart_contents_count() ); ?></span>
        <?php endif; ?>
      </a>
    <?php else : ?>
      <a class="header__icon header__icon--cart" href="<?php echo $home_url; ?>" aria-label="Go to cart">
        <i class="bi bi-cart" aria-hidden="true"></i>
      </a>
    <?php endif; ?>
  </div>
</header>
