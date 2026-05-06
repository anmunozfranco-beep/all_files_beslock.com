<?php
/**
 * CSV_portfolio_generator.php
 *
 * Admin UI and utilities to generate a CSV of portfolio products and import images
 * - Generates CSV with fields used by product-card from the current WP products
 * - Saves CSV to theme `data/` folder as `products_portfolio.csv`
 * - Provides a button to import images from theme assets (reuses beslock_import_images_from_assets if available)
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

function beslock_csv_portfolio_admin_ui() {
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions', 'beslock' ) );
  }

  echo '<div class="wrap"><h1>' . esc_html__( 'CSV Generator', 'beslock' ) . '</h1>';

  $message = '';
  if ( isset( $_POST['beslock_generate_csv'] ) ) {
    check_admin_referer( 'beslock_csv_generator_nonce' );
    $res = beslock_generate_csv_from_portfolio();
    if ( is_wp_error( $res ) ) {
      echo '<div class="notice notice-error"><p>' . esc_html( $res->get_error_message() ) . '</p></div>';
    } else {
      $url = get_stylesheet_directory_uri() . '/data/' . basename( $res );
      echo '<div class="notice notice-success"><p>' . esc_html__( 'CSV generated:', 'beslock' ) . ' <a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( basename( $res ) ) . '</a></p></div>';
    }
  }

  if ( isset( $_POST['beslock_import_images_assets'] ) ) {
    check_admin_referer( 'beslock_csv_generator_nonce' );
    // try to call existing function if available
    if ( function_exists( 'beslock_import_images_from_assets' ) ) {
      $dry = isset( $_POST['beslock_import_images_dry'] ) && $_POST['beslock_import_images_dry'];
      $r = beslock_import_images_from_assets( $dry );
      if ( is_wp_error( $r ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html( $r->get_error_message() ) . '</p></div>';
      } else {
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Images import result (see log below).', 'beslock' ) . '</p></div>';
        if ( is_array( $r ) && ! empty( $r['log'] ) ) {
          echo '<h2>Log</h2><pre style="white-space:pre-wrap;background:#fff;border:1px solid #ddd;padding:12px;">' . esc_html( implode( "\n", $r['log'] ) ) . '</pre>';
        }
      }
    } else {
      echo '<div class="notice notice-error"><p>' . esc_html__( 'Images importer not available. Ensure carga_portfolio_data.php provides beslock_import_images_from_assets().', 'beslock' ) . '</p></div>';
    }
  }

  echo '<form method="post">' . wp_nonce_field( 'beslock_csv_generator_nonce' );
  echo '<p>' . esc_html__( 'Generate a CSV for WooCommerce importer using the products shown by the portfolio block.', 'beslock' ) . '</p>';
  echo '<p><button type="submit" name="beslock_generate_csv" class="button button-primary">' . esc_html__( 'CSV generator', 'beslock' ) . '</button></p>';
  echo '<h2>' . esc_html__( 'Import images from theme assets', 'beslock' ) . '</h2>';
  echo '<p><label><input type="checkbox" name="beslock_import_images_dry" value="1" checked> ' . esc_html__( 'Dry run (do not modify uploads)', 'beslock' ) . '</label></p>';
  echo '<p><button type="submit" name="beslock_import_images_assets" class="button">' . esc_html__( 'Import images from assets', 'beslock' ) . '</button></p>';
  echo '</form>';

  echo '</div>';
}

function beslock_generate_csv_from_portfolio() {
  $theme_dir = get_stylesheet_directory();
  $data_dir = trailingslashit( $theme_dir ) . 'data';
  if ( ! is_dir( $data_dir ) ) {
    if ( ! @mkdir( $data_dir, 0755, true ) ) {
      return new WP_Error( 'no_write', 'Unable to create data directory: ' . $data_dir );
    }
  }

  $csv_path = trailingslashit( $data_dir ) . 'products_portfolio.csv';
  $fh = @fopen( $csv_path, 'w' );
  if ( ! $fh ) return new WP_Error( 'no_write', 'Unable to open CSV for writing: ' . $csv_path );

  // Header: include fields used by product-card
  $headers = array( 'product_id', 'slug', 'name', 'short_description', 'description', 'price', 'sku', 'badge', 'features', 'images', 'gallery', 'categories', 'tags' );
  fputcsv( $fh, $headers );

  // Query products similar to products-portfolio template
  $args = array( 'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => array( 'publish', 'private', 'draft' ) );
  $loop = new WP_Query( $args );
  if ( $loop->have_posts() ) {
    while ( $loop->have_posts() ) {
      $loop->the_post();
      $pid = get_the_ID();
      $prod = array();
      $prod['product_id'] = $pid;
      $prod['slug'] = get_post_field( 'post_name', $pid );
      $prod['name'] = get_the_title( $pid );
      $prod['short_description'] = get_post_field( 'post_excerpt', $pid );
      $prod['description'] = get_post_field( 'post_content', $pid );
      $prod['price'] = get_post_meta( $pid, '_price', true );
      $prod['sku'] = get_post_meta( $pid, '_sku', true );
      $prod['badge'] = get_post_meta( $pid, 'beslock_badge', true );
      $prod['features'] = get_post_meta( $pid, 'beslock_features', true );

      // images: featured + gallery URLs (theme-local filenames converted to names when possible)
      $images = array();
      $thumb = get_post_thumbnail_id( $pid );
      if ( $thumb ) {
        $url = wp_get_attachment_url( $thumb );
        if ( $url ) $images[] = $url;
      }
      $gallery_meta = get_post_meta( $pid, '_product_image_gallery', true );
      $gallery_ids = $gallery_meta ? array_filter( array_map( 'intval', explode( ',', $gallery_meta ) ) ) : array();
      $gallery_urls = array();
      foreach ( $gallery_ids as $gid ) {
        $u = wp_get_attachment_url( $gid );
        if ( $u ) $gallery_urls[] = $u;
      }

      // categories and tags
      $cats = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'names' ) );
      $tags = wp_get_post_terms( $pid, 'product_tag', array( 'fields' => 'names' ) );

      $row = array(
        $prod['product_id'],
        $prod['slug'],
        $prod['name'],
        $prod['short_description'],
        $prod['description'],
        $prod['price'],
        $prod['sku'],
        $prod['badge'],
        is_array( $prod['features'] ) ? implode( '|', $prod['features'] ) : $prod['features'],
        implode( ',', $images ),
        implode( ',', $gallery_urls ),
        implode( '|', $cats ),
        implode( ', ', $tags ),
      );

      fputcsv( $fh, $row );
    }
    wp_reset_postdata();
  }

  fclose( $fh );
  return $csv_path;
}

?>
