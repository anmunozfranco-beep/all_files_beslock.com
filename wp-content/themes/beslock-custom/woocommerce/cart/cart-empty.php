<?php
/**
 * Theme override: Cart empty template with product-card style buttons
 */

defined( 'ABSPATH' ) || exit;

$shop_url = wc_get_page_permalink( 'shop' );
?>
<div class="wc-empty-cart-message beslock-empty-cart-theme">
  <p class="cart-empty woocommerce-info"><?php echo esc_html__( 'Tu carrito está vacío.', 'beslock' ); ?></p>

  <div class="beslock-empty-actions product-card product-card--empty">
    <div class="product-card__content">
      <div class="product-card__actions product-card__actions--inline">
        <a href="<?php echo esc_url( $shop_url ); ?>" class="btn product-card__btn product-card__btn--link product-card__btn--full"><?php echo esc_html__( 'Ver Producto', 'beslock' ); ?></a>
        <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="product-card__add-to-cart" aria-label="<?php echo esc_attr__( 'Ir al carrito', 'beslock' ); ?>" rel="nofollow">
          <i class="bi bi-cart" aria-hidden="true"></i>
        </a>
      </div>
    </div>
  </div>
</div>
