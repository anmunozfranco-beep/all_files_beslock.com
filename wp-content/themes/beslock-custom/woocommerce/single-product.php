<?php
/**
 * Single Product template override (minimal, layout scaffold)
 * Placed in theme to provide a controlled layout while preserving WooCommerce functions.
 */
defined( 'ABSPATH' ) || exit;
get_header();

global $product, $post;
// Ensure global $post and $product are properly set for WooCommerce templates/hooks.
if ( have_posts() ) {
  the_post();
  $post = get_post();
  setup_postdata( $post );
  if ( function_exists( 'wc_get_product' ) ) {
    $wc_product = wc_get_product( $post->ID );
    if ( $wc_product && is_object( $wc_product ) ) {
      $product = $wc_product;
    } else {
      $product = null;
    }
  }
} else {
  $product = null;
}

?>
<main id="site-content" class="site-main">
  <div class="u-container product-page">
    <?php do_action( 'woocommerce_before_single_product' ); ?>

    <div class="product-page__hero">
      <div class="product-page__media">
        <?php
        /**
         * Use WooCommerce hook to render product gallery/images.
         * This ensures functions are available only when WooCommerce is active
         * and avoids calling template helper functions directly.
         */
        do_action( 'woocommerce_before_single_product_summary' );
        ?>
      </div>

      <div class="product-page__info">
        <?php
        /**
         * Render the product summary (title, rating, price, excerpt, add-to-cart)
         * via the standard WooCommerce hook. Theme CSS can reposition the
         * resulting markup into columns as needed.
         */
        do_action( 'woocommerce_single_product_summary' );

        // Trust / badges area placeholder
        echo '<div class="product-page__trust">';
        do_action( 'beslock_product_trust_badges' );
        echo '</div>';
        ?>
      </div>
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
