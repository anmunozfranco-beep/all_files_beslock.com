<?php
// Robust product-card: only outputs srcset entries that exist; DOES NOT set width/height attributes
$product = $product ?? [];

$image_src = isset($product['image']) ? $product['image'] : '';
$image_path_rel = str_replace( get_stylesheet_directory_uri(), '', $image_src );
$theme_dir = get_stylesheet_directory();

$filename = basename( $image_src );
$dirname  = dirname( $image_path_rel );

$possible_sizes = array(
  '300' => $dirname . '/m/300x0/' . $filename,
  '800' => $dirname . '/m/800x0/' . $filename,
);

$srcset_parts = array();
foreach ( $possible_sizes as $w => $rel ) {
  $abs = $theme_dir . $rel;
  if ( file_exists( $abs ) ) {
    $srcset_parts[] = esc_url( get_stylesheet_directory_uri() . $rel ) . ' ' . $w . 'w';
  }
}

if ( empty( $srcset_parts ) && $image_src ) {
  $srcset_parts[] = esc_url( $image_src ) . ' 800w';
}

$srcset_attr = ! empty( $srcset_parts ) ? implode( ', ', $srcset_parts ) : '';
?>
<div class="product-card reveal">
  <div class="product-card__image" aria-hidden="false">
    <?php
      // Build images list. Priority:
      // 1) If this card was mapped to a WC product (`product_id`), use product attachments (featured + gallery)
      // 2) Else use explicit 'images' array from the portfolio template
      $images = array();
      if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
        $pid = intval( $product['product_id'] );
        // featured image
        $thumb_id = get_post_thumbnail_id( $pid );
        if ( $thumb_id ) {
          $url = wp_get_attachment_image_url( $thumb_id, 'large' );
          if ( $url ) $images[] = $url;
        }
        // gallery images
        $gallery_meta = get_post_meta( $pid, '_product_image_gallery', true );
        if ( $gallery_meta ) {
          $gids = array_filter( array_map( 'intval', explode( ',', $gallery_meta ) ) );
          foreach ( $gids as $gid ) {
            $url = wp_get_attachment_image_url( $gid, 'large' );
            if ( $url ) $images[] = $url;
          }
        }
      }

      if ( empty( $images ) ) {
        $images = isset( $product['images'] ) && is_array( $product['images'] ) ? $product['images'] : array();
        if ( empty( $images ) && $image_src ) {
          // Backwards-compatible single image fallback using existing 'image' key
          $images = array( basename( $image_src ) );
        }
      }

      if ( ! empty( $images ) ) :
    ?>
        <div class="product-card__image-rotator product-image-rotator">
          <?php foreach ( $images as $i => $img_item ) :
            $src = '';
            // If item is a full URL (attachment URL returned by wp_get_attachment_image_url)
            if ( is_string( $img_item ) && ( strpos( $img_item, 'http://' ) === 0 || strpos( $img_item, 'https://' ) === 0 ) ) {
              $src = $img_item;
            } elseif ( is_numeric( $img_item ) ) {
              $src = wp_get_attachment_image_url( intval( $img_item ), 'large' );
            } else {
              // treat as theme-local filename
              $img_name = ltrim( $img_item, "/" );
              $abs_img = $theme_dir . '/assets/images/' . $img_name;
              $src = get_stylesheet_directory_uri() . '/assets/images/' . $img_name;
              if ( file_exists( $abs_img ) ) {
                $src .= '?v=' . filemtime( $abs_img );
              }
            }
            if ( ! $src ) continue;
            $bem = 'product-card__frame' . ( $i === 0 ? ' product-card__frame--active' : '' );
            $legacy = 'product-frame' . ( $i === 0 ? ' is-active' : '' );
            $class = trim( $bem . ' ' . $legacy );
          ?>
            <img class="<?php echo esc_attr( $class ); ?>" src="<?php echo esc_url( $src ); ?>" alt="" />
          <?php endforeach; ?>
        </div>
    <?php else : ?>
      <div style="width:100%;height:0;padding-bottom:100%;background:#f3f3f3;border-radius:12px;"></div>
    <?php endif; ?>
    <?php
      // Price overlay: show WC price HTML when product mapped
      $price_html = '';
      if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
        $wc_tmp = wc_get_product( intval( $product['product_id'] ) );
        if ( $wc_tmp ) {
          $price_html = $wc_tmp->get_price_html();
        }
      }
    ?>
    <?php if ( $price_html ) : ?>
      <div class="product-card__price-overlay"><?php echo wp_kses_post( $price_html ); ?></div>
    <?php endif; ?>
  </div>

  <div class="product-card__content">
    <h3 class="product-card__title"><?php echo esc_html( $product['name'] ?? '' ); ?></h3>
    <?php
      // Description: prefer WooCommerce product short description (or excerpt/content) when product_id is present
      $desc_text = isset( $product['desc'] ) ? $product['desc'] : '';
      if ( ! empty( $product['product_id'] ) ) {
        $pid = intval( $product['product_id'] );
        if ( function_exists( 'wc_get_product' ) ) {
          $wc = wc_get_product( $pid );
          if ( $wc ) {
            $short = $wc->get_short_description();
            if ( $short ) {
              $desc_text = wp_strip_all_tags( $short );
            } else {
              $full = $wc->get_description();
              if ( $full ) {
                $desc_text = wp_strip_all_tags( $full );
              }
            }
          }
        } else {
          $excerpt = get_post_field( 'post_excerpt', $pid );
          if ( $excerpt ) {
            $desc_text = wp_strip_all_tags( $excerpt );
          }
        }
      }
    ?>
    <p class="product-card__desc"><?php echo esc_html( $desc_text ); ?></p>
    <?php
      // Existing button: if mapped to WC product, make it an Add to Cart where appropriate.
      $btn_text = __( 'Ver Producto', 'beslock' );
      $btn_href = isset( $product['link'] ) ? $product['link'] : '#';
      $btn_classes = 'btn product-card__btn';

      if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
        $pid = intval( $product['product_id'] );
        $wc = wc_get_product( $pid );
        if ( $wc ) {
          // Always link to the single product page from portfolio cards.
          $btn_href = get_permalink( $pid );
          $btn_text = __( 'Ver Producto', 'beslock' );
        }
      }
    ?>
    <a href="<?php echo esc_url( $btn_href ); ?>" class="<?php echo esc_attr( $btn_classes ); ?>" tabindex="0" rel="nofollow"><?php echo esc_html( $btn_text ); ?></a>
  </div>
</div>