<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/admin/importer.php' );

// Importer helpers and product-image assignment functions
if ( ! function_exists( 'beslock_import_portfolio_images' ) ) {
  function beslock_import_portfolio_images() {
    $dir = get_stylesheet_directory() . '/assets/images';
    if ( ! is_dir( $dir ) ) {
      return new WP_Error( 'no_dir', sprintf( __( 'Directory not found: %s', 'beslock' ), $dir ) );
    }

    $patterns = array( '/*.webp', '/*.png', '/*.jpg', '/*.jpeg', '/*.gif' );
    $files = array();
    foreach ( $patterns as $p ) {
      $found = glob( $dir . $p );
      if ( $found ) $files = array_merge( $files, $found );
    }

    if ( empty( $files ) ) {
      return array( 'count' => 0, 'ids' => array() );
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $upload_dir = wp_upload_dir();
    $imported = array();

    foreach ( $files as $file ) {
      $filename = wp_basename( $file );

      $existing = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'meta_query' => array(
          array( 'key' => '_wp_attached_file', 'value' => $filename, 'compare' => 'LIKE' ),
        ),
      ) );
      if ( ! empty( $existing ) ) {
        $imported[] = $existing[0]->ID;
        continue;
      }

      $unique = wp_unique_filename( $upload_dir['path'], $filename );
      $new_path = trailingslashit( $upload_dir['path'] ) . $unique;
      if ( ! copy( $file, $new_path ) ) {
        continue;
      }

      $filetype = wp_check_filetype( $unique );
      $attachment = array(
        'post_mime_type' => $filetype['type'] ?: 'image/jpeg',
        'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
        'post_content'   => '',
        'post_status'    => 'inherit',
      );

      $attach_id = wp_insert_attachment( $attachment, $new_path );
      if ( is_wp_error( $attach_id ) ) continue;

      $attach_data = wp_generate_attachment_metadata( $attach_id, $new_path );
      wp_update_attachment_metadata( $attach_id, $attach_data );

      $imported[] = $attach_id;
    }

    return array( 'count' => count( $imported ), 'ids' => $imported );
  }
}

if ( ! function_exists( 'beslock_import_and_assign_portfolio_images' ) ) {
  function beslock_import_and_assign_portfolio_images() {
    $imp = beslock_import_portfolio_images();
    if ( is_wp_error( $imp ) ) {
      return $imp;
    }
    $assign = beslock_assign_images_to_products();
    if ( is_wp_error( $assign ) ) {
      return $assign;
    }
    return array(
      'import_count' => isset( $imp['count'] ) ? $imp['count'] : 0,
      'import_ids'   => isset( $imp['ids'] ) ? $imp['ids'] : array(),
      'assigned'     => isset( $assign['assigned'] ) ? $assign['assigned'] : 0,
      'skipped'      => isset( $assign['skipped'] ) ? $assign['skipped'] : array(),
    );
  }
}

if ( ! function_exists( 'beslock_set_all_products_price' ) ) {
  function beslock_set_all_products_price( $amount ) {
    if ( ! $amount || ! is_scalar( $amount ) ) {
      return new WP_Error( 'invalid_amount', __( 'Invalid amount', 'beslock' ) );
    }
    $amount_float = floatval( str_replace( ',', '.', (string) $amount ) );
    $amount = number_format( $amount_float, 2, '.', '' );

    $products = get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'post_status' => array( 'publish', 'private', 'draft' ) ) );
    if ( empty( $products ) ) {
      return new WP_Error( 'no_products', __( 'No products found', 'beslock' ) );
    }

    $updated = 0;
    foreach ( $products as $p ) {
      $pid = $p->ID;
      if ( function_exists( 'wc_get_product' ) ) {
        $wc = wc_get_product( $pid );
        if ( $wc ) {
          if ( $wc->is_type( 'variable' ) ) {
            $child_ids = $wc->get_children();
            foreach ( $child_ids as $cid ) {
              $vc = wc_get_product( $cid );
              if ( $vc ) {
                $vc->set_regular_price( $amount );
                $vc->set_sale_price( '' );
                $vc->save();
                update_post_meta( $cid, '_regular_price', $amount );
                update_post_meta( $cid, '_price', $amount );
                delete_post_meta( $cid, '_sale_price' );
              }
            }
            $wc->set_regular_price( $amount );
            $wc->save();
            update_post_meta( $pid, '_regular_price', $amount );
            update_post_meta( $pid, '_price', $amount );
            delete_post_meta( $pid, '_sale_price' );
          } else {
            $wc->set_regular_price( $amount );
            $wc->set_sale_price( '' );
            $wc->save();
            update_post_meta( $pid, '_regular_price', $amount );
            update_post_meta( $pid, '_price', $amount );
            delete_post_meta( $pid, '_sale_price' );
          }
          $updated++;
          continue;
        }
      }
      update_post_meta( $pid, '_regular_price', $amount );
      update_post_meta( $pid, '_price', $amount );
      delete_post_meta( $pid, '_sale_price' );
      $updated++;
    }

    return array( 'updated' => $updated );
  }
}

if ( ! function_exists( 'beslock_normalize_string' ) ) {
  function beslock_normalize_string( $s ) {
    $s = (string) $s;
    $s = mb_strtolower( $s, 'UTF-8' );
    if ( function_exists( 'iconv' ) ) {
      $norm = @iconv( 'UTF-8', 'ASCII//TRANSLIT', $s );
      if ( $norm !== false ) {
        $s = $norm;
      }
    }
    $s = preg_replace( '/[^a-z0-9]+/', '-', $s );
    $s = trim( $s, '-' );
    return $s;
  }
}

if ( ! function_exists( 'beslock_assign_images_to_products' ) ) {
  function beslock_assign_images_to_products() {
    $assigned = 0;
    $skipped = array();

    $products = get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'post_status' => array( 'publish', 'private', 'draft' ) ) );
    if ( empty( $products ) ) {
      return new WP_Error( 'no_products', __( 'No products found', 'beslock' ) );
    }

    global $wpdb;

    foreach ( $products as $p ) {
      $slug = $p->post_name ?: '';
      $title = $p->post_title ?: '';
      $match_key = $slug ? $slug : $title;
      $norm_key = beslock_normalize_string( $match_key );
      if ( ! $norm_key ) {
        $skipped[] = $p->post_title . '(no-slug)';
        continue;
      }

      $like_slug = '%' . $wpdb->esc_like( $slug ) . '%';
      $like_title = '%' . $wpdb->esc_like( $title ) . '%';
      $query = $wpdb->prepare(
        "SELECT ID, pm.meta_value as file FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type='attachment' AND pm.meta_key='_wp_attached_file' AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)",
        $like_slug,
        $like_title
      );
      $rows = $wpdb->get_results( $query );

      $matches = array();
      if ( ! empty( $rows ) ) {
        foreach ( $rows as $r ) {
          $filename = pathinfo( $r->file, PATHINFO_FILENAME );
          $norm_filename = beslock_normalize_string( $filename );
          if ( $norm_filename === $norm_key || strpos( $norm_filename, $norm_key ) !== false || strpos( $norm_key, $norm_filename ) !== false ) {
            $matches[] = $r->ID;
          }
        }
      }

      if ( empty( $matches ) ) {
        $all_query = "SELECT ID, pm.meta_value as file FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type='attachment' AND pm.meta_key='_wp_attached_file' LIMIT 1000";
        $rows2 = $wpdb->get_results( $all_query );
        if ( ! empty( $rows2 ) ) {
          foreach ( $rows2 as $r ) {
            $filename = pathinfo( $r->file, PATHINFO_FILENAME );
            $norm_filename = beslock_normalize_string( $filename );
            if ( $norm_filename === $norm_key || strpos( $norm_filename, $norm_key ) !== false || strpos( $norm_key, $norm_filename ) !== false ) {
              $matches[] = $r->ID;
            }
          }
        }
      }

      if ( empty( $matches ) ) {
        $skipped[] = $p->post_title;
        continue;
      }

      $first = array_shift( $matches );
      set_post_thumbnail( $p->ID, $first );
      if ( ! empty( $matches ) ) {
        $gallery_val = implode( ',', $matches );
        update_post_meta( $p->ID, '_product_image_gallery', $gallery_val );
      }

      $assigned++;
    }

    return array( 'assigned' => $assigned, 'skipped' => $skipped );
  }
}
