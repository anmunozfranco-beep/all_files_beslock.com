<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

global $product;

$context = get_query_var('beslock_context', []);
$show_description = $context['show_description'] ?? false;
?>

<div class="product-card pc-card section-reveal">

  <div class="product-card__image">
    <?php echo $product->get_image( 'medium' ); ?>

    <?php
    // Non-blocking badge logic: compute $show_badge only if $product is valid.
    $show_badge = false;
    if ( isset( $product ) && is_a( $product, 'WC_Product' ) ) {
      $product_slug   = $product->get_slug();
      $badge_products = array( 'e-orbit', 'e-flex', 's-shield', 'e-prime' );
      $show_badge     = in_array( $product_slug, $badge_products, true );
    }

    if ( $show_badge ) :
      $badge_path = get_template_directory() . '/assets/images/instal.png';
      if ( file_exists( $badge_path ) ) : ?>
        <img
          class="product-card__badge"
          src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/instal.png' ); ?>"
          alt="<?php echo esc_attr_x( 'Instalación incluida', 'badge alt', 'beslock' ); ?>"
          aria-hidden="true"
        >
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <h3 class="product-card__title"><?php echo esc_html( $product->get_name() ); ?></h3>

  <p class="product-card__price"><?php echo $product->get_price_html(); ?></p>

  <?php if ( $show_description ) : ?>
    <p class="product-card__description">
      <?php echo wp_kses_post( $product->get_short_description() ); ?>
    </p>
  <?php endif; ?>

  <div class="pc-actions">

    <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="pc-btn-main">
      Ver Producto
    </a>

    <a href="?add-to-cart=<?php echo $product->get_id(); ?>" class="pc-btn-cart" aria-label="Add to cart">
      <i class="bi bi-cart"></i>
    </a>

  </div>

</div>
