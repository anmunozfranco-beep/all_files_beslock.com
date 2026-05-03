<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

/* Keep the required hook only. */
do_action( 'woocommerce_cart_is_empty' );

?>

<div class="beslock-cart beslock-cart--empty beslock-cart--offset">
    <div class="beslock-cart__container">

        <h2 class="beslock-cart__empty-title">
            Tu carrito está vacío
        </h2>

        <p class="beslock-cart__empty-subtitle">
            Descubre nuestros productos y equipa tu espacio con Beslock
        </p>

                <div class="beslock-cart__recommendations">
                    <?php
                    $args = [
                        'post_type' => 'product',
                        'posts_per_page' => 4,
                    ];

                    $loop = new WP_Query( $args );

                    if ( $loop->have_posts() ) : ?>

                        <div class="products-portfolio__grid">

                            <?php while ( $loop->have_posts() ) : $loop->the_post(); ?>

                                <?php wc_get_template_part( 'content', 'product' ); ?>

                            <?php endwhile; ?>

                        </div>

                    <?php endif;

                    wp_reset_postdata();
                    ?>
                </div>

    </div>
</div>

<?php
