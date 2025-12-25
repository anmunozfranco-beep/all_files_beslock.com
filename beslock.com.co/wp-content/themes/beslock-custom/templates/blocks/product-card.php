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
    <?php if ( $image_src ) : ?>
      <?php
        // For three specific products, render a rotator with two images.
        // IMPORTANT: current filenames in /assets/images end with an underscore for the base image.
        // Example: e-nova_.webp (base) + e-nova_s.webp (alt). Keep this map in sync with actual files.
        $rotator_map = [
          'e-Nova'   => [ '/assets/images/e-nova_.webp',   '/assets/images/e-nova_s.webp'   ],
          'e-Touch'  => [ '/assets/images/e-touch_.webp',  '/assets/images/e-touch_c.webp'  ],
          'e-Shield' => [ '/assets/images/e-shield_.webp', '/assets/images/e-shield_e.webp' ],
        ];

        $product_name = $product['name'] ?? '';
        $use_rotator = array_key_exists( $product_name, $rotator_map );

        // Helper: build srcset string for a given image path (URI form)
        $build_srcset_for = function( $img_uri ) use ( $theme_dir ) {
          $srcset_parts = array();
          $image_path_rel = str_replace( get_stylesheet_directory_uri(), '', $img_uri );
          $filename = basename( $img_uri );
          $dirname  = dirname( $image_path_rel );
          $possible_sizes = array(
            '300' => $dirname . '/m/300x0/' . $filename,
            '800' => $dirname . '/m/800x0/' . $filename,
          );
          foreach ( $possible_sizes as $w => $rel ) {
            $abs = $theme_dir . $rel;
            if ( file_exists( $abs ) ) {
              $srcset_parts[] = esc_url( get_stylesheet_directory_uri() . $rel ) . ' ' . $w . 'w';
            }
          }
          if ( empty( $srcset_parts ) && $img_uri ) {
            $srcset_parts[] = esc_url( $img_uri ) . ' 800w';
          }
          return ! empty( $srcset_parts ) ? implode( ', ', $srcset_parts ) : '';
        };

        if ( $use_rotator ) :
          // Map contains relative paths; convert to full URIs. Render exactly two images
          // inside the rotator container for the specified products (no file_exists checks).
          $pair = $rotator_map[ $product_name ];
          $img1_rel = $pair[0];
          $img2_rel = $pair[1];
          $img1_uri = esc_url( get_stylesheet_directory_uri() . $img1_rel );
          $img2_uri = esc_url( get_stylesheet_directory_uri() . $img2_rel );
          $srcset1 = $build_srcset_for( $img1_uri );
          $srcset2 = $build_srcset_for( $img2_uri );
      ?>
          <div class="product-image-rotator">
            <img class="product-frame visible" src="<?php echo $img1_uri; ?>" <?php if ( $srcset1 ) : ?>srcset="<?php echo esc_attr( $srcset1 ); ?>"<?php endif; ?> sizes="(max-width:600px) 90vw, 300px" alt="<?php echo esc_attr( $product_name ); ?>" loading="lazy" decoding="async" />
            <img class="product-frame" src="<?php echo $img2_uri; ?>" <?php if ( $srcset2 ) : ?>srcset="<?php echo esc_attr( $srcset2 ); ?>"<?php endif; ?> sizes="(max-width:600px) 90vw, 300px" alt="<?php echo esc_attr( $product_name ); ?> alternate" loading="lazy" decoding="async" />
          </div>
      <?php
        else :
          // Default single-image flow for products not in the rotator map
      ?>
          <div class="product-image-rotator">
            <img class="product-frame visible" src="<?php echo esc_url( $image_src ); ?>" <?php if ( $srcset_attr ) : ?>srcset="<?php echo esc_attr( $srcset_attr ); ?>"<?php endif; ?> sizes="(max-width:600px) 90vw, 300px" alt="<?php echo esc_attr( $product['name'] ?? '' ); ?>" loading="lazy" decoding="async" />
          </div>
      <?php
        endif; // use_rotator
      ?>
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