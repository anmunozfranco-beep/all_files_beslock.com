<?php
defined('ABSPATH') || exit;
get_header();
?>

<main class="product-page">

  <?php while ( have_posts() ) : the_post(); ?>

    <div class="product-page__hero">

      <div class="product-page__media">
        <?php if ( function_exists( 'woocommerce_show_product_images' ) ) { woocommerce_show_product_images(); } ?>
      </div>

      <div class="product-page__info">
        <?php if ( function_exists( 'woocommerce_template_single_title' ) ) { woocommerce_template_single_title(); } ?>
        <?php if ( function_exists( 'woocommerce_template_single_price' ) ) { woocommerce_template_single_price(); } ?>
        <?php if ( function_exists( 'woocommerce_template_single_excerpt' ) ) { woocommerce_template_single_excerpt(); } ?>
        <?php if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) { woocommerce_template_single_add_to_cart(); } ?>
      </div>

    </div>

    <div class="product-page__tabs">
      <?php if ( function_exists( 'woocommerce_output_product_data_tabs' ) ) { woocommerce_output_product_data_tabs(); } ?>
    </div>

  <?php endwhile; ?>

</main>

<?php get_footer();
