<?php
$product = $args['product'] ?? null;
if ( ! defined( 'ABSPATH' ) ) {
<?php
// Badge system: show badge for a curated list of product slugs.
// This keeps control in one place and avoids duplicates.
?>

// Resolve badge image path and URL; only render if file exists
// badge resolution will use theme template directory when rendering inside image container

?>

<div class="product-card__image">
</div>
    <?php echo $product->get_image('medium'); ?>

    <?php
    // show badge only for specific product slugs
    $product_slug = $product->get_slug();
    $badge_products = array( 'e-orbit', 'e-flex', 's-shield', 'e-prime' );
    if ( in_array( $product_slug, $badge_products, true ) ) :
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

<div class="pc-actions">

    <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="pc-btn-main">
        Ver Producto
    </a>

    <a href="?add-to-cart=<?php echo $product->get_id(); ?>" class="pc-btn-cart" aria-label="Add to cart">
        <i class="bi bi-cart"></i>
    </a>

</div>

</div>
<?php
