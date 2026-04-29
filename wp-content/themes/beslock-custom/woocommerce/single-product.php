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
            <?php
              // Stock microcopy / urgency
              if ( $product->is_in_stock() ) {
                if ( $product->managing_stock() ) {
                  $qty = intval( $product->get_stock_quantity() );
                  if ( $qty > 0 && $qty <= 5 ) {
                    printf( '<div class="product-page__stock product-page__stock--low">Solo %d disponibles</div>', $qty );
                  }
                }
              } else {
                echo '<div class="product-page__stock product-page__stock--out">Agotado</div>';
              }
            ?>
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

    <div class="product-tabs" data-module="product-tabs">
      <div class="product-tabs__nav" role="tablist" aria-label="Product tabs">
        <button id="product-tab-specs" class="product-tabs__tab product-tabs__tab--active" role="tab" aria-selected="true" aria-controls="product-panel-specs">Especificaciones</button>
        <button id="product-tab-reviews" class="product-tabs__tab" role="tab" aria-selected="false" aria-controls="product-panel-reviews">Reviews</button>
      </div>

      <div id="product-panel-specs" class="product-tabs__panel" role="tabpanel" aria-labelledby="product-tab-specs">
        <h2 class="visually-hidden">Especificaciones</h2>
        <ul class="product-specs-list">
          <li>Material: 100% algodón orgánico.</li>
          <li>Color: disponible en varias tonalidades.</li>
          <li>Tamaño: S, M, L, XL.</li>
          <li>Cuidado: lavar a máquina a 30°C.</li>
          <li>Garantía: 2 años contra defectos de fabricación.</li>
          <li>Peso aproximado: 450 g.</li>
        </ul>
      </div>

      <div id="product-panel-reviews" class="product-tabs__panel" role="tabpanel" aria-labelledby="product-tab-reviews" hidden>
        <h2 class="visually-hidden">Reviews</h2>
        <div class="product-reviews-placeholder">
          <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
          <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
          <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
        </div>
      </div>
    </div>

    <!-- Removed demo/related/problem-solution sections per design request -->
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
