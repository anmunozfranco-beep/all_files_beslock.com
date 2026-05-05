<?php
defined('ABSPATH') || exit;
get_header();
?>

<main class="product-page">

  <?php while ( have_posts() ) : the_post(); ?>

    <div class="product-page__hero">

      <div class="product-page__media">
        <div class="product-page__gallery">
          <div class="product-page__gallery-wrapper" aria-hidden="false">
            <?php if ( function_exists( 'woocommerce_show_product_images' ) ) { woocommerce_show_product_images(); } ?>
          </div>
        </div>
      </div>

      <div class="product-page__info">
        <?php if ( function_exists( 'woocommerce_template_single_title' ) ) { woocommerce_template_single_title(); } ?>
        <?php if ( function_exists( 'woocommerce_template_single_price' ) ) { woocommerce_template_single_price(); } ?>
        <?php if ( function_exists( 'woocommerce_template_single_excerpt' ) ) { woocommerce_template_single_excerpt(); } ?>
        <?php if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) { woocommerce_template_single_add_to_cart(); } ?>
      </div>

    </div>

    <div class="product-page__details">
      <div class="product-tabs">

        <div class="product-tabs__nav">
          <button class="product-tabs__tab is-active">Features</button>
          <button class="product-tabs__tab">Reviews</button>
        </div>

        <div class="product-tabs__content">

          <div class="product-tabs__panel is-active">
            <ul>
              <li>Lorem ipsum dolor sit amet</li>
              <li>Consectetur adipiscing elit</li>
              <li>Sed do eiusmod tempor</li>
              <li>Incididunt ut labore</li>
              <li>Et dolore magna aliqua</li>
              <li>Ut enim ad minim veniam</li>
              <li>Quis nostrud exercitation</li>
            </ul>
          </div>

          <div class="product-tabs__panel">
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.</p>
          </div>

        </div>

      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
      const tabs = Array.from(document.querySelectorAll('.product-tabs__tab'));
      const panels = Array.from(document.querySelectorAll('.product-tabs__panel'));
      if(!tabs.length || !panels.length) return;
      tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
          tabs.forEach(t => t.classList.remove('is-active'));
          panels.forEach(p => p.classList.remove('is-active'));
          tab.classList.add('is-active');
          if(panels[index]) panels[index].classList.add('is-active');
        });
      });
    });
    </script>

  <?php endwhile; ?>

</main>

<?php get_footer();
