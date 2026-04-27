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
              <?php
              /**
               * Product card — always prefer WooCommerce product data when mapped.
               * Renders an image rotator using product gallery (featured + gallery) if present,
               * otherwise falls back to the theme-provided `images` or `image` entries.
               */

              $product = $product ?? array();
              $theme_dir = get_stylesheet_directory();

              // Build image list
              $images = array();
              if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
                $pid = intval( $product['product_id'] );
                // featured
                $thumb_id = get_post_thumbnail_id( $pid );
                if ( $thumb_id ) {
                  $url = wp_get_attachment_image_url( $thumb_id, 'large' );
                  if ( $url ) $images[] = $url;
                }
                // gallery
                $gallery_meta = get_post_meta( $pid, '_product_image_gallery', true );
                if ( $gallery_meta ) {
                  $gids = array_filter( array_map( 'intval', explode( ',', $gallery_meta ) ) );
                  foreach ( $gids as $gid ) {
                    $url = wp_get_attachment_image_url( $gid, 'large' );
                    if ( $url ) $images[] = $url;
                  }
                }
              }

              // Fallback to template-provided images
              if ( empty( $images ) ) {
                if ( isset( $product['images'] ) && is_array( $product['images'] ) ) {
                  $images = $product['images'];
                } elseif ( ! empty( $product['image'] ) ) {
                  $images = array( $product['image'] );
                }
              }

              // Normalize images to absolute URLs where possible
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
                // treat as theme-local filename
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
                        <img class="<?php echo esc_attr( $class ); ?>" src="<?php echo esc_url( $src ); ?>" alt="" />
                      <?php endforeach; ?>
                    </div>
                  <?php else : ?>
                    <div style="width:100%;height:0;padding-bottom:100%;background:#f3f3f3;border-radius:12px;"></div>
                  <?php endif; ?>

                  <?php
                    // Price overlay: prefer WC price HTML, fallback to _regular_price
                    $price_html = '';
                    if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
                      $wc_tmp = wc_get_product( intval( $product['product_id'] ) );
                      if ( $wc_tmp ) {
                        $price_html = $wc_tmp->get_price_html();
                      }
                    }
                    if ( empty( $price_html ) && ! empty( $product['product_id'] ) ) {
                      $reg = get_post_meta( intval( $product['product_id'] ), '_regular_price', true );
                      if ( $reg ) {
                        $price_html = function_exists( 'wc_price' ) ? wc_price( $reg ) : esc_html( $reg );
                      }
                    }
                  ?>
                  <?php if ( $price_html ) : ?>
                    <div class="product-card__price-overlay"><?php echo wp_kses_post( $price_html ); ?></div>
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
                  <a href="<?php echo esc_url( $btn_href ); ?>" class="<?php echo esc_attr( $btn_classes ); ?>" tabindex="0" rel="nofollow"><?php echo esc_html( $btn_text ); ?></a>
                </div>
              </div>