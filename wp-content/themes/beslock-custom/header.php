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
      <span class="logo-wrapper">
        <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo-green.png' ); ?>" alt="<?php esc_attr_e('BESLOCK Logo', 'beslock'); ?>" />
        <span class="logo__tm" aria-hidden="true">®</span>
      </span>
    </a>

    <script>
    (function(){
      function setLogoVar(){
        var wrapper = document.querySelector('.logo-wrapper');
        if(!wrapper) return;
        var img = wrapper.querySelector('img');
        if(!img) return;
        var rect = img.getBoundingClientRect();
        var h = rect && rect.height ? rect.height : (img.naturalHeight || 0);
        if(h) wrapper.style.setProperty('--logo-h', h + 'px');
          if(h) {
            wrapper.setAttribute('data-logo-h', Math.round(h));
            try { console.log('logo measured height:', Math.round(h)); } catch(e){}
          }
      }
      document.addEventListener('DOMContentLoaded', setLogoVar);
      window.addEventListener('resize', function(){ requestAnimationFrame(setLogoVar); });
      var imgEl = document.querySelector('.logo-wrapper img');
      if(imgEl){
        if(!imgEl.complete){ imgEl.addEventListener('load', setLogoVar); }
        else { setLogoVar(); }
        // expose measured height for debugging/inspection
        imgEl.addEventListener('load', function(){ var w=document.querySelector('.logo-wrapper'); if(w && imgEl.getBoundingClientRect) w.setAttribute('data-logo-h', Math.round(imgEl.getBoundingClientRect().height)); });
      }
    })();
    </script>

    <?php
    // Use WooCommerce cart URL when available; fallback to /cart
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart' );
    $cart_count = 0;
    if ( class_exists( 'WooCommerce' ) && WC()->cart ) {
      $cart_count = (int) WC()->cart->get_cart_contents_count();
    }
    ?>
    <a href="<?php echo esc_url( $cart_url ); ?>" class="header__icon header__icon--cart" aria-label="<?php esc_attr_e('Go to cart', 'beslock'); ?>">
      <i class="bi bi-cart" aria-hidden="true"></i>
      <?php if ( $cart_count > 0 ) : ?>
        <span class="header__cart-count" aria-hidden="true"><?php echo esc_html( $cart_count ); ?></span>
      <?php endif; ?>
    </a>
  </div>
</header>
 

<?php
// incluir el partial del drawer si existe (safe)
if ( file_exists( get_stylesheet_directory() . '/templates/menu-simple.php' ) ) {
  get_template_part( 'templates/menu-simple' );
}
?>
