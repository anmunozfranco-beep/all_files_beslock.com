<?php
$product = $args['product'] ?? null;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! $product ) return;

$show_description = $args['show_description'] ?? false;
?>

<div class="product-card">

<div class="product-card__image">
    <?php echo $product->get_image('medium'); ?>
</div>

<h3 class="product-card__title">
    <?php echo esc_html($product->get_name()); ?>
</h3>

<p class="product-card__price">
    <?php echo $product->get_price_html(); ?>
</p>

<?php if ( ! empty( $show_description ) ) : ?>

    <?php $desc = $product->get_short_description(); ?>

    <?php if ( ! empty( $desc ) ) : ?>
        <p class="product-card__description">
            <?php echo wp_kses_post( $desc ); ?>
        </p>
    <?php else : ?>
        <p class="product-card__description">
            <?php echo esc_html__( 'Descripción no disponible', 'beslock' ); ?>
        </p>
    <?php endif; ?>

<?php endif; ?>

<div class="product-card__actions">

    <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="btn-primary">
        Ver Producto
    </a>

    <a href="?add-to-cart=<?php echo $product->get_id(); ?>" class="btn-cart" aria-label="Add to cart">
        <i class="bi bi-cart"></i>
    </a>

</div>

</div>
<?php
