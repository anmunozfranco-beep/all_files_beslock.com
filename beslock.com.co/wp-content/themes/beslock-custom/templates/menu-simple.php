<?php
// /wp-content/themes/beslock-custom/menu-simple.php
// Partial minimal para drawer móvil — SOLO el drawer (no incluye botón de abrir)
// Asegúrate de que el botón de apertura esté en header.php con id="menuBtn".
?>
<!-- Drawer principal: no incluye botón de apertura (ese está en header.php) -->
<nav id="mobileDrawer" class="mobile-drawer" aria-hidden="true" role="dialog" aria-label="<?php esc_attr_e('Mobile menu', 'beslock'); ?>">
  <div class="mobile-drawer__panel" role="document">
    <!-- Drawer header: close/back button at left and centered logo (same height as main header) -->
    <div class="drawer-header" role="banner">
      <!-- Close/back button placed at the left side of the header -->
      <button id="closeDrawer" class="mobile-drawer__close" aria-label="<?php esc_attr_e('Close menu', 'beslock'); ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span class="screen-reader-text"><?php esc_html_e('Back', 'beslock'); ?></span>
      </button>

      <!-- Centered logo (positioned absolutely to match header behavior) -->
      <a class="drawer__logo" href="<?php echo esc_url( home_url('/') ); ?>" aria-label="<?php esc_attr_e('Home', 'beslock'); ?>">
        <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo-green.png' ); ?>" alt="<?php esc_attr_e('BESLOCK Logo', 'beslock'); ?>" />
      </a>
    </div>

    <ul class="mobile-menu" role="menu">
      <li class="mobile-menu__item" role="none">
        <button class="mobile-menu__link" id="productsToggle" aria-expanded="false" aria-controls="productsPanel" role="menuitem">
          <?php esc_html_e('Products', 'beslock'); ?> <span class="chev" aria-hidden="true">›</span>
        </button>

        <?php
        /**
         * Panel semántico para Products.
         * - Oculto por defecto (aria-hidden="true")
         * - id="productsPanel" enlazado desde aria-controls en el botón
         * - role="region" para describir área del drawer que contiene el contenido de Products
         * - Contenido provisto por template-part: templates/models-mobile.php
         *
         * Nota: la visibilidad se controla con aria-* y clases (.models--hidden / .models--visible).
         */
        ?>
        <div id="productsPanel" class="mobile-products-panel models models--hidden" role="region" aria-hidden="true" aria-labelledby="productsToggle">
          <?php
            // Cargamos el template-part con las tarjetas (models)
            get_template_part( 'templates/models-mobile' );
          ?>
        </div>
      </li>

      <li class="mobile-menu__item" role="none">
        <?php
        // Link to the WooCommerce Shop page if available, otherwise fallback to /tienda
        $shop_id = ( function_exists( 'wc_get_page_id' ) ) ? wc_get_page_id( 'shop' ) : 0;
        $shop_url = $shop_id ? get_permalink( $shop_id ) : home_url( '/tienda' );

        // Determine if the store has published products to show a small flag/badge
        $product_count = 0;
        if ( post_type_exists( 'product' ) ) {
          $counts = wp_count_posts( 'product' );
          $product_count = isset( $counts->publish ) ? (int) $counts->publish : 0;
        }
        ?>
        <a class="mobile-menu__link" href="<?php echo esc_url( $shop_url ); ?>" role="menuitem">
          <i class="bi bi-shop-window" aria-hidden="true"></i>
          <div class="mobile-menu__meta">
            <span class="mobile-menu__title">Tienda de productos
              <?php if ( $product_count > 0 ) : ?>
                <span class="mobile-menu__badge" aria-hidden="true"><?php echo esc_html( $product_count ); ?></span>
              <?php endif; ?>
            </span>
            <span class="mobile-menu__subtitle">Compra fácil y rápida</span>
          </div>
        </a>
      </li>

      <li class="mobile-menu__item" role="none">
        <a class="mobile-menu__link" href="<?php echo esc_url( home_url('/offers') ); ?>" role="menuitem">
          <i class="bi bi-percent" aria-hidden="true"></i>
          <div class="mobile-menu__meta">
            <span class="mobile-menu__title">Catch our Limited Offers</span>
            <span class="mobile-menu__subtitle">Save and boost profits</span>
          </div>
        </a>
      </li>

      <li class="mobile-menu__item" role="none">
        <a class="mobile-menu__link" href="<?php echo esc_url( home_url('/contact') ); ?>" role="menuitem">
          <i class="bi bi-headset" aria-hidden="true"></i>
          <div class="mobile-menu__meta">
            <span class="mobile-menu__title">Contact us Now</span>
            <span class="mobile-menu__subtitle">We are right back with you</span>
          </div>
        </a>
      </li>

      <li class="mobile-menu__item" role="none">
        <a class="mobile-menu__link" href="<?php echo esc_url( home_url('/docs') ); ?>" role="menuitem">
          <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
          <div class="mobile-menu__meta">
            <span class="mobile-menu__title">Technical Documentation</span>
            <span class="mobile-menu__subtitle">Top products on specifications</span>
          </div>
        </a>
      </li>
    </ul>
  </div>

  <!-- Backdrop para cerrar tocando afuera -->
  <div class="mobile-drawer__backdrop" id="drawerBackdrop" tabindex="-1" aria-hidden="true"></div>
</nav>