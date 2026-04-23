<?php
/**
 * remove-imported-products.php
 *
 * WP-CLI friendly script to remove products created by the Beslock importer.
 * Identification method: SKU prefix `beslock-` (the importer sets SKU to that value).
 *
 * Usage (dry-run default):
 * wp eval-file wp-content/themes/beslock-custom/tools/remove-imported-products.php
 * To actually delete set $DO_REMOVE = true below, or run via WP-CLI with env var.
 */

if ( ! defined( 'ABSPATH' ) ) {
  $wp_load = dirname( __FILE__, 6 ) . '/wp-load.php';
  if ( file_exists( $wp_load ) ) {
    require_once $wp_load;
  } else {
    echo "Cannot find wp-load.php; run this with wp-cli's eval-file from the site root.\n";
    exit;
  }
}

if ( ! class_exists( 'WooCommerce' ) ) {
  echo "WooCommerce not active. Nothing to do.\n";
  exit;
}

$DO_REMOVE = true; // USER CONFIRMED: perform deletions

$args = [
  'status' => 'any',
  'limit'  => -1,
  'type'   => 'simple',
];

$products = wc_get_products( $args );

$found = [];
foreach ( $products as $p ) {
  $sku = $p->get_sku();
  if ( $sku && strpos( $sku, 'beslock-' ) === 0 ) {
    $found[] = [ 'id' => $p->get_id(), 'title' => $p->get_name(), 'sku' => $sku ];
  }
}

if ( empty( $found ) ) {
  echo "No imported products (sku prefix beslock-) found.\n";
  exit;
}

echo "Found " . count( $found ) . " imported products:\n";
foreach ( $found as $f ) {
  echo "- {$f['id']} | {$f['title']} | {$f['sku']}\n";
}

if ( ! $DO_REMOVE ) {
  echo "\nDRY-RUN: no changes made. To delete set \$DO_REMOVE = true in this file or run with WP-CLI after editing.\n";
  exit;
}

// Proceed to delete products
foreach ( $found as $f ) {
  wp_delete_post( $f['id'], true );
  echo "DELETED: {$f['id']} | {$f['title']}\n";
}

echo "Done.\n";
