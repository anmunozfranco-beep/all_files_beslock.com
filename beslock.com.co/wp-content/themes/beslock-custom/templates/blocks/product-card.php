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
      // Render images from explicit 'images' array supplied by the portfolio template.
      // Requirements: render exactly the images provided, first image gets class 'is-active'.
      $images = isset( $product['images'] ) && is_array( $product['images'] ) ? $product['images'] : array();
      if ( empty( $images ) && $image_src ) {
        // Backwards-compatible single image fallback using existing 'image' key
        $images = array( basename( $image_src ) );
      }

      if ( ! empty( $images ) ) :
    ?>
        <div class="product-card__image-rotator product-image-rotator">
          <?php foreach ( $images as $i => $img_name ) :
            $img_name = ltrim( $img_name, "/" );
            $src = get_stylesheet_directory_uri() . '/assets/images/' . $img_name;
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
  </div>

  <div class="product-card__content">
    <h3 class="product-card__title"><?php echo esc_html( $product['name'] ?? '' ); ?></h3>
    <p class="product-card__desc"><?php echo esc_html( $product['desc'] ?? '' ); ?></p>
    <a href="<?php echo esc_url( $product['link'] ?? '#' ); ?>" class="btn product-card__btn" tabindex="0">Ver producto</a>
  </div>
</div>