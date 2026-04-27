<?php
/**
 * Single Product template override (minimal, layout scaffold)
 * Placed in theme to provide a controlled layout while preserving WooCommerce functions.
 */
defined( 'ABSPATH' ) || exit;
get_header();

global $product;
if ( ! $product ) {
    while ( have_posts() ) {
        the_post();
        $product = wc_get_product( get_the_ID() );
    }
}

?>
<main id="site-content" class="site-main">
  <div class="u-container product-page">
    <?php do_action( 'woocommerce_before_single_product' ); ?>

    <div class="product-page__hero">
      <div class="product-page__media">
        <?php
        /**
         * Show product images (uses WooCommerce template function)
         */
        if ( function_exists( 'woocommerce_show_product_images' ) ) {
            woocommerce_show_product_images();
        } else {
            echo get_the_post_thumbnail( get_the_ID(), 'large' );
        }
        ?>
      </div>

      <div class="product-page__info">
        <?php
        // Title
        if ( function_exists( 'woocommerce_template_single_title' ) ) {
            woocommerce_template_single_title();
        } else {
            echo '<h1 class="product-title">' . get_the_title() . '</h1>';
        }

        // Price
        if ( function_exists( 'woocommerce_template_single_price' ) ) {
            woocommerce_template_single_price();
        }

        // Short description
        if ( function_exists( 'woocommerce_template_single_excerpt' ) ) {
            woocommerce_template_single_excerpt();
        }

        // Trust / badges area placeholder
        echo '<div class="product-page__trust">';
        do_action( 'beslock_product_trust_badges' );
        echo '</div>';
        ?>
      </div>

      <aside class="product-page__buy">
        <?php
        // Add to cart (preserve WooCommerce functionality)
        if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
            woocommerce_template_single_add_to_cart();
        }

        // Small product meta or stock
        if ( function_exists( 'woocommerce_template_single_meta' ) ) {
            woocommerce_template_single_meta();
        }
        ?>
      </aside>
    </div>

    <!-- Secondary sections scaffold -->
    <section class="product-page__trust-block"> <!-- [ Confianza ] -->
      <?php do_action( 'beslock_product_confianza' ); ?>
    </section>

    <section class="product-page__psb"> <!-- Problema → Solución → Beneficios -->
      <?php do_action( 'beslock_product_psb' ); ?>
    </section>

    <section class="product-page__specs"> <!-- Specs técnicas -->
      <?php do_action( 'beslock_product_specs' ); ?>
    </section>

    <section class="product-page__demo"> <!-- Demo / Uso -->
      <?php do_action( 'beslock_product_demo' ); ?>
    </section>

    <section class="product-page__who"> <!-- Para quién es -->
      <?php do_action( 'beslock_product_who' ); ?>
    </section>

    <section class="product-page__reviews"> <!-- Reviews -->
      <?php
      if ( function_exists( 'comments_template' ) ) {
          comments_template();
      }
      ?>
    </section>

    <section class="product-page__faq"> <!-- FAQ -->
      <?php do_action( 'beslock_product_faq' ); ?>
    </section>

    <section class="product-page__related"> <!-- Productos relacionados -->
      <?php
      if ( function_exists( 'woocommerce_output_related_products' ) ) {
          woocommerce_output_related_products();
      }
      ?>
    </section>

    <section class="product-page__cta"> <!-- CTA final -->
      <?php do_action( 'beslock_product_cta' ); ?>
    </section>

    <?php do_action( 'woocommerce_after_single_product' ); ?>
  </div>
</main>

<?php
get_footer();
