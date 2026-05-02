<?php
$product = $args['product'] ?? null;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! $product ) return;

$show_description = $args['show_description'] ?? false;
?>

<div class="product-card pc-card">

<?php
// Dynamic badge: per-product toggle via post meta 'beslock_badge'
// Fallback: show badge by default when meta is not set
$show_badge = get_post_meta( $product->get_id(), 'beslock_badge', true );
if ( $show_badge === '' ) {
    $show_badge = 'yes';
}

// Resolve badge image path and URL; only render if file exists
// badge resolution will use theme template directory when rendering inside image container

?>

<div class="product-card__image">
    <?php echo $product->get_image('medium'); ?>

    <?php
    // Render badge image inside the image container so it is absolutely positioned
    $theme_tpl_dir = get_template_directory();
    $badge_file_tpl = $theme_tpl_dir . '/assets/images/instal.png';
    if ( file_exists( $badge_file_tpl ) && $show_badge === 'yes' ) : ?>
        <img
            class="product-card__badge"
            src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/instal.png' ); ?>"
            alt="<?php echo esc_attr_x( 'Instalación incluida', 'badge alt', 'beslock' ); ?>"
            aria-hidden="true"
        />
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
