<?php
/**
 * import-products-wc.php
 *
 * Small, safe importer to create WooCommerce `product` posts from the
 * static product array used by the theme. Designed for manual execution
 * via WP-CLI: `wp eval-file wp-content/themes/beslock-custom/tools/import-products-wc.php`
 *
 * Behavior:
 * - By default the script runs in DRY-RUN mode and will only report actions.
 * - To actually create products set `$DO_IMPORT = true` below.
 *
 * Notes:
 * - Images are sideloaded from the theme's `assets/images/` folder.
 * - The script skips products that already exist (by post title).
 */

if ( ! defined( 'ABSPATH' ) ) {
  // try to bootstrap WP if the file is executed directly from WP-CLI
  $wp_load = dirname( __FILE__, 6 ) . '/wp-load.php';
  if ( file_exists( $wp_load ) ) {
    require_once $wp_load;
  } else {
    echo "Cannot find wp-load.php; run this with wp-cli's eval-file from the site root.\n";
    exit;
  }
}

if ( ! class_exists( 'WooCommerce' ) ) {
  echo "WooCommerce not active. Activate WooCommerce before running this script.\n";
  exit;
}

$DO_IMPORT = true; // enabled by user confirmation — will perform creation

$theme_images_dir = get_stylesheet_directory() . '/assets/images/';

// Source data (kept in sync with the theme fallback).
$products = [
  [ 'name' => 'e-Nova', 'desc' => "Acceso inteligente sin llaves para tu día a día.\nHuella y control simple para moverte con libertad y tranquilidad.", 'images' => [ 'e-nova_.webp', 'e-nova_s.webp' ] ],
  [ 'name' => 'e-Orbit', 'desc' => "Mira y controla quién llega a tu puerta, estés donde estés.\nSeguridad avanzada con video y acceso inteligente desde tu celular.", 'images' => [ 'e-orbit_.webp' ] ],
  [ 'name' => 'e-Touch', 'desc' => "Acceso fácil para espacios compartidos.\nClave y huella para que todos puedan entrar, sin complicaciones.", 'images' => [ 'e-touch_.webp', 'e-touch_c.webp' ] ],
  [ 'name' => 'e-Flex', 'desc' => "Ideal para recibir y dar acceso sin estar presente.\nCódigos temporales, huella y control remoto para estancias cortas.", 'images' => [ 'e-flex_.webp' ] ],
  [ 'name' => 'e-Shield', 'desc' => "Protección inteligente para tu hogar.\nSeguridad robusta con acceso electrónico y respaldo mecánico.", 'images' => [ 'e-shield_.webp', 'e-shield_e.webp' ] ],
  [ 'name' => 'e-Prime', 'desc' => "Control total para accesos exigentes.\nGestión avanzada de usuarios para espacios de alto flujo.", 'images' => [ 'e-prime_.webp' ] ],
];

$summary = [ 'created' => [], 'skipped' => [], 'failed' => [] ];

foreach ( $products as $p ) {
  $title = $p['name'];
  $exists = get_page_by_title( $title, OBJECT, 'product' );
  if ( $exists ) {
    $summary['skipped'][] = $title;
    echo "SKIP: Product already exists: {$title}\n";
    continue;
  }

  echo "DRY-RUN: Would create product: {$title}\n";

  if ( $DO_IMPORT ) {
    $post_id = wp_insert_post( [
      'post_title'   => $title,
      'post_content' => $p['desc'],
      'post_status'  => 'publish',
      'post_type'    => 'product',
    ] );

    if ( is_wp_error( $post_id ) || ! intval( $post_id ) ) {
      $summary['failed'][] = $title;
      echo "ERROR: Failed to create post for {$title}\n";
      continue;
    }

    // Make sure product object exists
    $prod = wc_get_product( $post_id );
    if ( ! $prod ) $prod = new WC_Product_Simple( $post_id );

    // basic product metadata (no price set by default)
    $prod->set_sku( 'beslock-' . sanitize_title( $title ) );
    $prod->set_status( 'publish' );
    $prod->save();

    // handle images: first image => featured, rest => gallery
    $gallery_ids = [];
    foreach ( $p['images'] as $idx => $imgfile ) {
      $src = $theme_images_dir . $imgfile;
      if ( ! file_exists( $src ) ) {
        echo "WARN: image not found: {$src}\n";
        continue;
      }
      $data = file_get_contents( $src );
      if ( false === $data ) { echo "WARN: could not read {$src}\n"; continue; }
      $upload = wp_upload_bits( basename( $src ), null, $data );
      if ( ! empty( $upload['error'] ) ) { echo "WARN: upload error for {$src}: {$upload['error']}\n"; continue; }

      $wp_filetype = wp_check_filetype( $upload['file'], null );
      $attachment = [
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( basename( $upload['file'] ) ),
        'post_content'   => '',
        'post_status'    => 'inherit'
      ];
      $attach_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
      require_once ABSPATH . 'wp-admin/includes/image.php';
      $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
      wp_update_attachment_metadata( $attach_id, $attach_data );

      if ( $idx === 0 ) {
        set_post_thumbnail( $post_id, $attach_id );
      } else {
        $gallery_ids[] = $attach_id;
      }
    }

    if ( ! empty( $gallery_ids ) ) update_post_meta( $post_id, '_product_image_gallery', implode( ',', $gallery_ids ) );

    $summary['created'][] = $title;
    echo "CREATED: {$title} (post_id={$post_id})\n";
  }
}

echo "\nSummary:\n";
echo "Created: " . count( $summary['created'] ) . "\n";
echo "Skipped: " . count( $summary['skipped'] ) . "\n";
echo "Failed:  " . count( $summary['failed'] ) . "\n";

if ( ! $DO_IMPORT ) {
  echo "\nDRY-RUN only. To perform the import set \$DO_IMPORT = true in this file and re-run with WP-CLI.\n";
}
