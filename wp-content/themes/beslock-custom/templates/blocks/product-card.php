<?php
/**
 * Product card template — outputs a markup compatible with the theme rotator JS
 * Expected markup:
 * - wrapper: .product-card__image-rotator or .product-image-rotator
 * - image frames: img.product-card__frame and img.product-frame
 * - first frame must include active modifiers (.product-card__frame--active and .is-active)
 */

$product = isset( $product ) && is_array( $product ) ? $product : array();
$theme_dir = get_stylesheet_directory();

// Build canonical image list: prefer WooCommerce product attachments when mapped
$images = array();
if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
  $pid = intval( $product['product_id'] );
  $thumb_id = get_post_thumbnail_id( $pid );
  if ( $thumb_id ) {
    $url = wp_get_attachment_image_url( $thumb_id, 'large' );
    if ( $url ) $images[] = $url;
  }
  $gallery_meta = get_post_meta( $pid, '_product_image_gallery', true );
  if ( $gallery_meta ) {
    $gids = array_filter( array_map( 'intval', explode( ',', $gallery_meta ) ) );
    foreach ( $gids as $gid ) {
      $url = wp_get_attachment_image_url( $gid, 'large' );
      if ( $url ) $images[] = $url;
    }
  }
}

// Fallback to provided template images
if ( empty( $images ) ) {
  if ( ! empty( $product['images'] ) && is_array( $product['images'] ) ) {
    $images = $product['images'];
  } elseif ( ! empty( $product['image'] ) ) {
    $images = array( $product['image'] );
  }
}

// Normalize to absolute URLs where possible
$normalized = array();
foreach ( $images as $img ) {
  if ( is_numeric( $img ) ) {
    $url = wp_get_attachment_image_url( intval( $img ), 'large' );
    if ( $url ) $normalized[] = $url;
    continue;
  }
  if ( is_string( $img ) && ( strpos( $img, 'http://' ) === 0 || strpos( $img, 'https://' ) === 0 ) ) {
    $normalized[] = $img;
    continue;
  }
  // treat as theme-local filename under assets/images/
  $name = ltrim( (string) $img, "/" );
  $abs = $theme_dir . '/assets/images/' . $name;
  if ( file_exists( $abs ) ) {
    $normalized[] = get_stylesheet_directory_uri() . '/assets/images/' . $name . '?v=' . filemtime( $abs );
  }
}
$images = array_values( array_unique( $normalized ) );
?>

<div class="product-card reveal">
  <div class="product-card__image" aria-hidden="false">
    <?php if ( ! empty( $images ) ) : ?>
      <div class="product-card__image-rotator product-image-rotator">
        <?php foreach ( $images as $i => $src ) :
          $bem = 'product-card__frame' . ( $i === 0 ? ' product-card__frame--active' : '' );
          $legacy = 'product-frame' . ( $i === 0 ? ' is-active' : '' );
          $class = trim( $bem . ' ' . $legacy );
        ?>
          <img class="<?php echo esc_attr( $class ); ?>" src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $product['name'] ?? '' ); ?>" />
        <?php endforeach; ?>
      </div>
    <?php else : ?>
      <div style="width:100%;height:0;padding-bottom:100%;background:#f3f3f3;border-radius:12px;"></div>
    <?php endif; ?>

    <?php
      // Price overlay: prefer WC price HTML, fallback to _regular_price meta
      $price_html = '';
      if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
        $wc_tmp = wc_get_product( intval( $product['product_id'] ) );
        if ( $wc_tmp ) $price_html = $wc_tmp->get_price_html();
      }
      if ( empty( $price_html ) && ! empty( $product['product_id'] ) ) {
        $reg = get_post_meta( intval( $product['product_id'] ), '_regular_price', true );
        if ( $reg ) $price_html = function_exists( 'wc_price' ) ? wc_price( $reg ) : esc_html( $reg );
      }
    ?>
    <?php if ( $price_html ) : ?>
      <div class="product-card__price-overlay"><?php echo wp_kses_post( $price_html ); ?></div>
    <?php endif; ?>
    <?php
      // Badges for specific products (overlay in corner)
      $badge_names = array( 'e-Orbit', 'e-Flex', 'e-Prime', 'e-Shield' );
      $prod_name = isset( $product['name'] ) ? (string) $product['name'] : '';
      if ( $prod_name && in_array( strtolower( $prod_name ), array_map( 'strtolower', $badge_names ), true ) ) :
        $badge_src = get_stylesheet_directory_uri() . '/assets/images/instal.jpg';
    ?>
      <img class="product-card__badge" src="<?php echo esc_url( $badge_src ); ?>" alt="<?php esc_attr_e( 'Badge', 'beslock' ); ?>" aria-hidden="true" />
    <?php endif; ?>
  </div>

  <div class="product-card__content">
    <?php
      // Title: prefer WC product name when mapped
      if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
        $wc_tmp2 = wc_get_product( intval( $product['product_id'] ) );
        if ( $wc_tmp2 && method_exists( $wc_tmp2, 'get_name' ) ) {
          $product['name'] = $wc_tmp2->get_name() ?: ( $product['name'] ?? '' );
        }
      }
    ?>
    <h3 class="product-card__title"><?php echo esc_html( $product['name'] ?? '' ); ?></h3>

    <?php
      // Description: prefer WC short description or full description
      $desc_text = isset( $product['desc'] ) ? $product['desc'] : '';
      if ( ! empty( $product['product_id'] ) ) {
        if ( function_exists( 'wc_get_product' ) ) {
          $wc = wc_get_product( intval( $product['product_id'] ) );
          if ( $wc ) {
            $short = $wc->get_short_description();
            if ( $short ) {
              $desc_text = wp_strip_all_tags( $short );
            } else {
              $full = $wc->get_description();
              if ( $full ) $desc_text = wp_strip_all_tags( $full );
            }
          }
        } else {
          $excerpt = get_post_field( 'post_excerpt', intval( $product['product_id'] ) );
          if ( $excerpt ) $desc_text = wp_strip_all_tags( $excerpt );
        }
      }
    ?>
    <p class="product-card__desc"><?php echo esc_html( $desc_text ); ?></p>

    <?php
      $btn_text = __( 'Ver Producto', 'beslock' );
      $btn_href = isset( $product['link'] ) ? $product['link'] : '#';
      $btn_classes = 'btn product-card__btn';
      if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
        $pid = intval( $product['product_id'] );
        $wc = wc_get_product( $pid );
        if ( $wc ) {
          $btn_href = get_permalink( $pid );
          $btn_text = __( 'Ver Producto', 'beslock' );
        }
      }
    ?>
    <div class="product-card__actions">
      <a href="<?php echo esc_url( $btn_href ); ?>" class="<?php echo esc_attr( $btn_classes ); ?> product-card__btn--link" tabindex="0" rel="nofollow"><?php echo esc_html( $btn_text ); ?></a>

      <?php if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) :
        $add_to_cart_url = esc_url( add_query_arg( 'add-to-cart', intval( $product['product_id'] ), home_url( '/' ) ) );
      ?>
        <a href="<?php echo $add_to_cart_url; ?>" class="product-card__add-to-cart" aria-label="<?php esc_attr_e( 'Añadir al carrito', 'beslock' ); ?>" rel="nofollow">
          <i class="bi bi-cart" aria-hidden="true"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
/* end file */
