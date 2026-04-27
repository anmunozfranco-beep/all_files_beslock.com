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
      <div class="product-page__hero-grid">
        <!-- LEFT: Media + thumbnails + social proof -->
        <div class="product-page__media"> 
          <?php
            if ( function_exists( 'woocommerce_show_product_images' ) ) {
                woocommerce_show_product_images();
            } else {
                do_action( 'woocommerce_before_single_product_summary' );
            }
          ?>

          <div class="product-page__thumbnails">
            <?php
              // Render thumbnails row (if available)
              if ( function_exists( 'woocommerce_show_product_thumbnails' ) ) {
                woocommerce_show_product_thumbnails();
              }
            ?>
          </div>

          <div class="product-page__social-proof">
            <span class="product-page__views">50 people viewed this product today</span>
          </div>
        </div>

        <!-- CENTER: Title, rating, price, short description -->
        <div class="product-page__info"> 
          <div class="product-page__meta-top">
            <?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>
            <div class="product-page__wishlist">
              <a href="#" class="beslock-wishlist">❤ Wishlist</a>
            </div>
          </div>

          <div class="product-page__title-block">
            <?php
              if ( function_exists( 'woocommerce_template_single_title' ) ) {
                woocommerce_template_single_title();
              } else {
                the_title( '<h1 class="product-page__title">', '</h1>' );
              }

              if ( function_exists( 'woocommerce_template_single_rating' ) ) {
                woocommerce_template_single_rating();
              }

              // Sale flash + price inline
              if ( function_exists( 'woocommerce_show_product_sale_flash' ) ) {
                woocommerce_show_product_sale_flash();
              }
              if ( function_exists( 'woocommerce_template_single_price' ) ) {
                woocommerce_template_single_price();
              }
            ?>
          </div>

          <div class="product-page__excerpt">
            <?php if ( function_exists( 'woocommerce_template_single_excerpt' ) ) { woocommerce_template_single_excerpt(); } ?>
          </div>

          <div class="product-page__meta-bottom">
            <a class="product-page__size-guide" href="#">Size guide</a>
            <div class="product-page__color-chooser">Choose color: <span class="color-swatch" style="background:#222"></span><span class="color-swatch" style="background:#666"></span><span class="color-swatch" style="background:#ccc"></span></div>
          </div>

          <div class="product-page__trust-inline">
            <?php do_action( 'beslock_product_trust_badges' ); ?>
          </div>
        </div>

        <!-- RIGHT: Buy box (price, stock, options, add-to-cart) -->
        <aside class="product-page__buy"> 
          <?php
            global $product;
            if ( $product && is_object( $product ) ) {
              // Stock hint
              if ( $product->managing_stock() ) {
                $qty = $product->get_stock_quantity();
                if ( $qty !== null && $qty <= 5 ) {
                  echo '<div class="product-page__stock">Only ' . intval( $qty ) . ' left!</div>';
                }
              } elseif ( $product->is_on_backorder() ) {
                echo '<div class="product-page__stock product-page__stock--backorder">Available on backorder</div>';
              }

              // Price (redundant, already shown but keep in buy box)
              if ( function_exists( 'woocommerce_template_single_price' ) ) {
                woocommerce_template_single_price();
              }

              // Add-to-cart (this will render variation form for variable products)
              echo '<div class="product-page__buy-box">';
              if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
                woocommerce_template_single_add_to_cart();
              }
              echo '</div>';

              // Checkout logos
              echo '<div class="product-page__checkout-icons">';
              echo '<img src="' . esc_url( get_stylesheet_directory_uri() . '/assets/images/payments.png' ) . '" alt="pagos"/>';
              echo '</div>';
            }
          ?>
        </aside>
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
