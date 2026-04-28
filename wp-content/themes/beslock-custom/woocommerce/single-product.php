<?php
/**
 * Beslock theme override: single product (mobile-first, BEM)
 * Minimal, accessible scaffold that uses WooCommerce functions where appropriate.
 */
defined( 'ABSPATH' ) || exit;
get_header();

if ( have_posts() ) :
  while ( have_posts() ) : the_post();
    $product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
    if ( ! $product ) {
      continue;
    }
?>

<main class="product-page product-page--single" id="main" role="main">
  <div class="product-page__hero">
    <div class="product-page__media">
      <?php
        /**
         * Use core WooCommerce gallery rendering (keeps Photoswipe/zoom behavior)
         * This outputs the .woocommerce-product-gallery markup.
         */
        if ( function_exists( 'woocommerce_show_product_images' ) ) {
          woocommerce_show_product_images();
        }
      ?>
    </div>

    <div class="product-page__info">
      <header class="product-page__header">
        <h1 class="product-page__title"><?php the_title(); ?></h1>
        <div class="product-page__meta">
          <div class="product-page__price"><?php echo $product->get_price_html(); ?></div>
        </div>
      </header>

      <div class="product-page__excerpt">
        <?php the_excerpt(); ?>
      </div>

      <div class="product-page__buy">
        <?php
          /** Show the add to cart area (handles simple/variable) */
          if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
            woocommerce_template_single_add_to_cart();
          }
        ?>
      </div>

      <div class="product-page__trust">
        <?php do_action( 'beslock_product_trust_badges' ); ?>
      </div>
    </div>
  </div>

  <div class="product-page__content">
    <section class="product-page__problem-solution" aria-labelledby="ps-heading">
      <h2 id="ps-heading" class="product-page__section-title">Problema → Solución → Beneficios</h2>
      <?php do_action( 'beslock_product_problem_solution' ); ?>
    </section>

    <section class="product-page__specs" aria-labelledby="specs-heading">
      <h2 id="specs-heading" class="product-page__section-title">Especificaciones</h2>
      <?php do_action( 'beslock_product_specs' ); ?>
    </section>

    <section class="product-page__extra" aria-labelledby="extra-heading">
      <h2 id="extra-heading" class="product-page__section-title">Demo · ¿Para quién?</h2>
      <?php do_action( 'beslock_product_demo' ); ?>
    </section>

    <section class="product-page__reviews" aria-labelledby="reviews-heading">
      <h2 id="reviews-heading" class="product-page__section-title">Opiniones</h2>
      <?php comments_template(); ?>
    </section>

    <aside class="product-page__related" aria-labelledby="related-heading">
      <h2 id="related-heading" class="product-page__section-title">Productos relacionados</h2>
      <?php do_action( 'beslock_product_related' ); ?>
    </aside>
  </div>
</main>

<?php
  endwhile;
endif;

get_footer();
