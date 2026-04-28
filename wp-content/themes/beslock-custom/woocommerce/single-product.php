<?php
/**
 * Beslock theme override: single product (mobile-first, BEM)
 * Minimal, accessible scaffold that uses WooCommerce functions where appropriate.
 */
defined( 'ABSPATH' ) || exit;
// Use the site's normal header so single-product pages render the original
// header markup from `header.php`.
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
        // Server-rendered custom reel: gather featured + gallery images from the product
        $images = array();
        $feat_id = get_post_thumbnail_id( $product->get_id() );
        if ( $feat_id ) $images[] = intval( $feat_id );
        $gallery_meta = get_post_meta( $product->get_id(), '_product_image_gallery', true );
        if ( $gallery_meta ) {
          $gids = array_filter( array_map( 'intval', explode( ',', $gallery_meta ) ) );
          foreach ( $gids as $gid ) {
            if ( $gid && $gid !== $feat_id ) $images[] = intval( $gid );
          }
        }

        if ( ! empty( $images ) ) {
          echo '<div class="beslock-gallery-reel" role="list">';
          foreach ( $images as $aid ) {
            $src = wp_get_attachment_image_url( $aid, 'large' );
            $srcset = wp_get_attachment_image_srcset( $aid, 'large' );
            $sizes = wp_get_attachment_image_sizes( $aid, 'large' );
            $alt = get_post_meta( $aid, '_wp_attachment_image_alt', true );
            if ( ! $alt ) $alt = get_the_title( $aid );
            printf( '<div class="beslock-gallery-slide" role="listitem">' );
            printf( '<img src="%s" srcset="%s" sizes="%s" alt="%s" loading="lazy">', esc_url( $src ), esc_attr( $srcset ), esc_attr( $sizes ), esc_attr( $alt ) );
            echo '</div>';
          }
          echo '</div>';
        } else {
          // intentionally leave empty if no images
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

// Client-side fix: replace placeholder/magnifier image with product image if detected
if ( isset( $product ) && $product ) {
  $pid = intval( $product->get_id() );
  $correct_url = wp_get_attachment_image_url( get_post_thumbnail_id( $pid ), 'large' );
  if ( empty( $correct_url ) ) {
    $gallery = get_post_meta( $pid, '_product_image_gallery', true );
    if ( $gallery ) {
      $gids = array_filter( array_map( 'intval', explode( ',', $gallery ) ) );
      if ( ! empty( $gids ) ) {
        $correct_url = wp_get_attachment_image_url( $gids[0], 'large' );
      }
    }
  }
  if ( empty( $correct_url ) ) {
    $slug = isset( $post ) ? $post->post_name : '';
    if ( $slug ) {
      $correct_url = get_stylesheet_directory_uri() . '/assets/images/products/' . $slug . '.webp';
    }
  }

  if ( ! empty( $correct_url ) ) {
    ?>
    <script>(function(){
      function replaceIfPlaceholder(){
        try{
          var imgs = document.querySelectorAll('.woocommerce-product-gallery__image img.wp-post-image, .woocommerce-product-gallery__image img');
          if (!imgs || imgs.length === 0) return;
          var img = imgs[0];
          var src = img.getAttribute('src') || '';
          var isPlaceholder = /lupa|magnif|magnifier|magnifying|search|lens|placeholder/i.test(src);
          function checkAndReplace(){
            var w = img.naturalWidth || img.width || 0;
            var h = img.naturalHeight || img.height || 0;
            if ((w && h && (w/h > 5 || h/w > 5)) || isPlaceholder) {
              img.src = '<?php echo esc_js( $correct_url ); ?>';
              img.removeAttribute('srcset');
              img.removeAttribute('sizes');
            }
          }
          if (img.complete) {
            checkAndReplace();
          } else {
            img.addEventListener('load', checkAndReplace);
            setTimeout(checkAndReplace, 800);
          }
        }catch(e){console && console.error && console.error('replaceIfPlaceholder', e);}      
      }
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', replaceIfPlaceholder); else replaceIfPlaceholder();
    })();</script>
    <?php
  }
}

get_footer();
