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
            // Support three formats for images in the 'images' array:
            // 1) theme asset filename (e.g. 'e-nova_.webp') — keep legacy behavior
            // 2) absolute URL (http(s)://...) — use directly (WC attachments)
            // 3) absolute path starting with '/' — convert to site URL
            $src = '';
            $img_name_raw = $img_name;
            if ( is_string( $img_name ) && preg_match( '#^https?://#i', $img_name ) ) {
              $src = $img_name;
            } elseif ( is_string( $img_name ) && strpos( $img_name, 'wp-content' ) !== false ) {
              // relative uploads path like 'wp-content/uploads/2026/04/foo.webp'
              $src = ( strpos( $img_name, '/' ) === 0 ) ? home_url( $img_name ) : home_url( '/' . ltrim( $img_name, '/' ) );
            } else {
              // treat as theme asset filename (legacy)
              $img_name = ltrim( $img_name, "/" );
              $abs_img = $theme_dir . '/assets/images/' . $img_name;
              $src = get_stylesheet_directory_uri() . '/assets/images/' . $img_name;
              if ( file_exists( $abs_img ) ) {
                $src .= '?v=' . filemtime( $abs_img );
              }
            }

            if ( empty( $src ) ) continue;

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