<?php
/**
 * templates/models-mobile.php
 *
 * Dynamic product cards for the mobile menu.
 * This version reads images from /assets/images/products/ (non-underscore variants).
 *
 * Behaviour:
 * - Scans assets/images/products for files (webp/png/jpg/jpeg).
 * - Ignores files whose filename ends with an underscore (reserved for front-page variants in /assets/images/).
 * - Groups by base name (e.g. e-nova) and renders one card per base.
 * - Emits <source> for webp (if present) and an <img> fallback (png/jpg/jpeg/webp).
 * - Adds optional data-object-position attribute per image when a focal override exists.
 *
 * IMPORTANT: copy this file to /wp-content/themes/your-theme/templates/models-mobile.php
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// Menu must use the non-underscore images located in assets/images/products/
$images_dir_path = get_stylesheet_directory() . '/assets/images/products/';
$images_dir_uri  = get_stylesheet_directory_uri() . '/assets/images/products/';

// User-configurable overrides:
// badge text per base (optional)
$badge_overrides = array(
  // 'e-nova' => 'Cerradura Electronica',
);

// focal point overrides per base (object-position values, e.g. "50% 30%")
$focal_overrides = array(
  // 'e-nova' => '80% 20%',
);

// gather files (only files directly under assets/images/products/)
$files = array();
$glob = @glob( $images_dir_path . '*.{webp,png,jpg,jpeg}', GLOB_BRACE );
if ( $glob && is_array( $glob ) ) {
  foreach ( $glob as $path ) {
    if ( ! is_file( $path ) ) {
      continue;
    }

    $filename = basename( $path );
    $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    $name = pathinfo( $filename, PATHINFO_FILENAME );

    // Skip files whose filename ends with an underscore (reserved variants)
    if ( substr( $name, -1 ) === '_' ) {
      continue;
    }

    $base = $name; // keep hyphens as-is

    if ( ! isset( $files[ $base ] ) ) {
      $files[ $base ] = array(
        'files' => array(),
      );
    }
    $files[ $base ]['files'][ $ext ] = $images_dir_uri . $filename;
  }
}

// Sort by base name to ensure deterministic ordering.
if ( $files ) {
  ksort( $files, SORT_NATURAL | SORT_FLAG_CASE );
}

// Helper: produce a human-readable title from the base name:
// preserves hyphens and capitalizes parts (e.g. e-nova -> e-Nova)
function beslock_title_from_base( $base ) {
  $segments = explode( '-', $base );
  foreach ( $segments as $i => $seg ) {
    if ( $i === 0 && strlen( $seg ) === 1 ) {
      // Keep single-letter prefix lowercase (e.g. 'e' -> e-Nova)
      $segments[ $i ] = strtolower( $seg );
    } else {
      $segments[ $i ] = ucfirst( $seg );
    }
  }
  return implode( '-', $segments );
}
?>
<section class="models__list" aria-hidden="false">
  <?php if ( empty( $files ) ) : ?>
    <!-- No product images found in assets/images/products/ -->
  <?php else : ?>
    <?php foreach ( $files as $base => $data ) :
      $files_map = isset( $data['files'] ) ? $data['files'] : array();

      // prefer webp source if available
      $webp_uri = isset( $files_map['webp'] ) ? $files_map['webp'] : false;

      // fallback order for <img>
      $img_fallback = false;
      foreach ( array( 'png', 'jpg', 'jpeg', 'webp' ) as $ext ) {
        if ( isset( $files_map[ $ext ] ) ) {
          $img_fallback = $files_map[ $ext ];
          break;
        }
      }

      // skip if somehow no fallback available
      if ( ! $img_fallback ) {
        continue;
      }

      $title = beslock_title_from_base( $base );
      $badge = isset( $badge_overrides[ $base ] ) ? $badge_overrides[ $base ] : 'Cerradura Electronica';
      $focal = isset( $focal_overrides[ $base ] ) ? $focal_overrides[ $base ] : '';
      $id_safe = 'models-item-title-' . sanitize_html_class( $base );
    ?>
    <article class="models__item" role="article" aria-labelledby="<?php echo esc_attr( $id_safe ); ?>">
      <div class="models__item-media models__item-media--lazy" aria-hidden="false">
        <picture>
          <?php if ( $webp_uri ) : ?>
            <source srcset="<?php echo esc_url( $webp_uri ); ?>" type="image/webp">
          <?php endif; ?>

          <img
            src="<?php echo esc_url( $img_fallback ); ?>"
            alt="<?php echo esc_attr( $title ); ?>"
            loading="lazy"
            class="models__item-img"
            width="1200"
            height="675"
            <?php if ( ! empty( $focal ) ) : ?>
              data-object-position="<?php echo esc_attr( $focal ); ?>"
            <?php endif; ?>
          />
        </picture>

        <div class="models__item-media__shade" aria-hidden="true"></div>

        <div class="models__item-media__overlay" aria-hidden="false">
          <h3 id="<?php echo esc_attr( $id_safe ); ?>" class="models__item-title" tabindex="-1" aria-hidden="false"><?php echo esc_html( $title ); ?></h3>

          <div class="models__item-badges" aria-hidden="false">
            <span class="models__item-badge"><?php echo esc_html( $badge ); ?></span>
          </div>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>