<?php
/**
 * import-products-from-portfolio.php
 *
 * Create WooCommerce products from the static product array used by the
 * front-page `products-portfolio.php` template.
 *
 * Usage (dry-run default):
 * wp eval-file wp-content/tools/import-products-from-portfolio.php
 * To actually import set $DO_IMPORT = true below.
 */

if ( ! defined( 'ABSPATH' ) ) {
  $wp_load = dirname( __FILE__, 4 ) . '/wp-load.php';
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

$DO_IMPORT = true; // enabled: perform creation (user requested real run)

// Static source copied from templates/blocks/products-portfolio.php
$static_products = [
  [
    'name'  => 'e-Nova',
    'desc'  => "Acceso inteligente sin llaves para tu día a día.\nHuella y control simple para moverte con libertad y tranquilidad.",
    'images' => [ 'e-nova_.webp', 'e-nova_s.webp' ],
  ],
  [
    'name'  => 'e-Orbit',
    'desc'  => "Mira y controla quién llega a tu puerta, estés donde estés.\nSeguridad avanzada con video y acceso inteligente desde tu celular.",
    'images' => [ 'e-orbit_.webp' ],
  ],
  [
    'name'  => 'e-Touch',
    'desc'  => "Acceso fácil para espacios compartidos.\nClave y huella para que todos puedan entrar, sin complicaciones.",
    'images' => [ 'e-touch_.webp', 'e-touch_c.webp' ],
  ],
  [
    'name'  => 'e-Flex',
    'desc'  => "Ideal para recibir y dar acceso sin estar presente.\nCódigos temporales, huella y control remoto para estancias cortas.",
    'images' => [ 'e-flex_.webp' ],
  ],
  [
    'name'  => 'e-Shield',
    'desc'  => "Protección inteligente para tu hogar.\nSeguridad robusta con acceso electrónico y respaldo mecánico.",
    'images' => [ 'e-shield_.webp', 'e-shield_e.webp' ],
  ],
  [
    'name'  => 'e-Prime',
    'desc'  => "Control total para accesos exigentes.\nGestión avanzada de usuarios para espacios de alto flujo.",
    'images' => [ 'e-prime_.webp' ],
  ],
];

$theme_dir = get_stylesheet_directory();
$theme_uri = get_stylesheet_directory_uri();

$summary = [ 'created' => [], 'skipped' => [], 'failed' => [] ];

foreach ( $static_products as $p ) {
  $title = $p['name'];
  // Skip if product with same title exists
  $exists = get_page_by_title( $title, OBJECT, 'product' );
  if ( $exists ) {
    $summary['skipped'][] = $title;
    echo "SKIP: Product already exists: {$title}\n";
    continue;
  }

  echo "DRY-RUN: Would create product: {$title}\n";

  if ( ! $DO_IMPORT ) continue;

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

  $prod = wc_get_product( $post_id );
  if ( ! $prod ) $prod = new WC_Product_Simple( $post_id );
  $sku = 'beslock-' . sanitize_title( $title );
  $prod->set_sku( $sku );
  $prod->set_status( 'publish' );
  $prod->save();

  // Images: sideload from theme assets
  $gallery_ids = [];
  foreach ( $p['images'] as $idx => $imgfile ) {
    $src = $theme_dir . '/assets/images/' . ltrim( $imgfile, '/' );
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

echo "\nSummary:\n";
echo "Created: " . count( $summary['created'] ) . "\n";
echo "Skipped: " . count( $summary['skipped'] ) . "\n";
echo "Failed:  " . count( $summary['failed'] ) . "\n";

if ( ! $DO_IMPORT ) {
  echo "\nDRY-RUN only. To perform the import set \$DO_IMPORT = true in this file and re-run with WP-CLI.\n";
}
