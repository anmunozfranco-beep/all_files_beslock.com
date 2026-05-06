<?php
/**
 * carga_portfolio_data.php
 *
 * New isolated importer: carga_portfolio_data
 * Reads /data/products.json and creates/updates WooCommerce products.
 * Idempotent: matches by slug, reuses existing attachments when filenames match,
 * imports theme images if needed, and assigns featured + gallery images.
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! function_exists( 'beslock_carga_portfolio_process' ) ) {
  function beslock_carga_portfolio_process( $dry_run = false ) {
    $log = array();
    $is_dry = (bool) $dry_run;

    $data_file = get_stylesheet_directory() . '/data/products.json';
    if ( ! file_exists( $data_file ) ) {
      return new WP_Error( 'no_file', sprintf( __( 'products.json not found: %s', 'beslock' ), $data_file ) );
    }

    $json = file_get_contents( $data_file );
    if ( $json === false ) {
      return new WP_Error( 'read_error', __( 'Unable to read products.json', 'beslock' ) );
    }

    $data = json_decode( $json, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
      return new WP_Error( 'json_error', json_last_error_msg() );
    }

    if ( ! is_array( $data ) ) {
      return new WP_Error( 'invalid_format', __( 'products.json must be an array of product objects', 'beslock' ) );
    }

    // helper: find attachment ID by filename (basename match)
    $find_attachment_by_filename = function( $filename ) {
      global $wpdb;
      $filename = wp_basename( $filename );
      $like = '%' . $wpdb->esc_like( $filename ) . '%';
      $sql = $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type='attachment' AND pm.meta_key='_wp_attached_file' AND pm.meta_value LIKE %s LIMIT 1", $like );
      $res = $wpdb->get_var( $sql );
      return $res ? intval( $res ) : 0;
    };

    // helper: find theme file path across known image dirs
    $find_theme_file = function( $filename ) {
      $search_dirs = array(
        get_stylesheet_directory() . '/assets/images/products/',
        get_stylesheet_directory() . '/assets/images/',
      );

      $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
      $base = pathinfo( $filename, PATHINFO_FILENAME );
      $candidates = array();

      // If already webp, look for it directly and its _s variant
      if ( $ext === 'webp' ) {
        $candidates[] = $base . '.webp';
        $candidates[] = $base . '_s.webp';
      } else {
        // Map any incoming filename to webp candidates only
        $candidates[] = $base . '.webp';
        $candidates[] = $base . '_s.webp';
        // If filename has common suffixes like _hero or _d, also try stripped base
        if ( preg_match('/(_hero|_d(_\d+)?)$/i', $base ) ) {
          $stripped = preg_replace('/(_hero|_d(_\d+)?)$/i', '', $base );
          if ( $stripped ) {
            $candidates[] = $stripped . '.webp';
            $candidates[] = $stripped . '_s.webp';
          }
        }
      }

      foreach ( $search_dirs as $d ) {
        foreach ( $candidates as $cand ) {
          $p = trailingslashit( $d ) . $cand;
          if ( file_exists( $p ) ) return $p;
        }
      }

      return '';
    };

    // helper: import theme image if present and not already in uploads
    $import_theme_image = function( $filename, &$log ) use ( $find_attachment_by_filename, $find_theme_file, &$is_dry ) {
      $theme_path = $find_theme_file( $filename );
      if ( ! $theme_path ) {
        return 0;
      }

      // check existing attachment first (use the discovered theme basename)
      $theme_basename = wp_basename( $theme_path );
      $existing = $find_attachment_by_filename( $theme_basename );
      if ( $existing ) {
        $log[] = "Reused existing attachment for {$theme_basename}: {$existing}";
        return $existing;
      }

      if ( $is_dry ) {
        $log[] = "Would import theme image (dry-run): {$theme_basename}";
        return 0;
      }

      require_once ABSPATH . 'wp-admin/includes/image.php';
      require_once ABSPATH . 'wp-admin/includes/file.php';
      require_once ABSPATH . 'wp-admin/includes/media.php';

      $upload_dir = wp_upload_dir();
      $unique = wp_unique_filename( $upload_dir['path'], wp_basename( $theme_path ) );
      $new_path = trailingslashit( $upload_dir['path'] ) . $unique;
      if ( ! copy( $theme_path, $new_path ) ) {
        $log[] = "Failed to copy theme image {$theme_path} to uploads";
        return 0;
      }

      $filetype = wp_check_filetype( $unique );
      $attachment = array(
        'post_mime_type' => $filetype['type'] ?: 'image/jpeg',
        'post_title' => sanitize_file_name( pathinfo( $unique, PATHINFO_FILENAME ) ),
        'post_content' => '',
        'post_status' => 'inherit',
      );

      $attach_id = wp_insert_attachment( $attachment, $new_path );
      if ( is_wp_error( $attach_id ) || ! $attach_id ) {
        $log[] = "Failed to insert attachment for {$filename}";
        return 0;
      }

      $attach_data = wp_generate_attachment_metadata( $attach_id, $new_path );
      wp_update_attachment_metadata( $attach_id, $attach_data );
      $log[] = "Imported theme image {$filename} as attachment {$attach_id}";
      return $attach_id;
    };

    // helper: discover images for a product slug under assets/images
    $discover_images_for_slug = function( $slug ) {
      $dirs = array(
        get_stylesheet_directory() . '/assets/images/products/',
        get_stylesheet_directory() . '/assets/images/',
      );
      $found = array( 'primary' => array(), 'secondary' => array() );
      foreach ( $dirs as $d ) {
        if ( ! is_dir( $d ) ) continue;
        // only consider webp files
        $pattern = trailingslashit( $d ) . $slug . '*.webp';
        foreach ( glob( $pattern ) as $path ) {
          $base = wp_basename( $path );
          if ( preg_match('/_s\.webp$/i', $base) ) {
            $found['secondary'][] = $base;
          } else {
            $found['primary'][] = $base;
          }
        }
      }
      return $found;
    };

    $created = 0;
    $updated = 0;
    $skipped = array();
    $missing_images = array();
    $duplicated_slugs = array();

    $seen_slugs = array();

    foreach ( $data as $prod ) {
      if ( ! isset( $prod['slug'] ) || empty( $prod['slug'] ) ) {
        $log[] = 'Skipping product with missing slug';
        continue;
      }

      $slug = sanitize_title( $prod['slug'] );
      if ( isset( $seen_slugs[ $slug ] ) ) {
        $duplicated_slugs[] = $slug;
        $log[] = "Duplicate slug detected and skipped: {$slug}";
        continue;
      }
      $seen_slugs[ $slug ] = true;

      // find existing product by slug
      $existing = get_page_by_path( $slug, OBJECT, 'product' );

      if ( $existing ) {
        $pid = $existing->ID;
        $is_new = false;
      } else {
        if ( $is_dry ) {
          $pid = 0;
          $created++;
          $is_new = true;
          $log[] = "(dry-run) Would create product {$slug}";
        } else {
          // create product post
          $postarr = array(
            'post_title' => isset( $prod['title'] ) ? $prod['title'] : $slug,
            'post_name' => $slug,
            'post_excerpt' => isset( $prod['short_description'] ) ? $prod['short_description'] : '',
            'post_status' => 'publish',
            'post_type' => 'product',
          );
          $pid = wp_insert_post( $postarr );
          if ( is_wp_error( $pid ) || ! $pid ) {
            $log[] = "Failed to create product for slug: {$slug}";
            $skipped[] = $slug;
            continue;
          }
          $created++;
          $is_new = true;
          $log[] = "Created product {$slug} (ID: {$pid})";
        }
      }

      // update title/short description if needed
      $update_post = array( 'ID' => $pid );
      $changed = false;
      if ( isset( $prod['title'] ) && $prod['title'] !== get_the_title( $pid ) ) {
        $update_post['post_title'] = $prod['title'];
        $changed = true;
      }
      if ( isset( $prod['short_description'] ) ) {
        $excerpt = $prod['short_description'];
        if ( $excerpt !== get_post_field( 'post_excerpt', $pid ) ) {
          $update_post['post_excerpt'] = $excerpt;
          $changed = true;
        }
      }
      if ( $changed ) {
        if ( ! $is_dry ) {
          wp_update_post( $update_post );
          $log[] = "Updated basic fields for {$slug}";
        } else {
          $log[] = "(dry-run) Would update basic fields for {$slug}";
        }
      }

      // set price
      $price = isset( $prod['price'] ) ? trim( (string) $prod['price'] ) : '';
      if ( $price !== '' ) {
        if ( $is_dry ) {
          $log[] = "(dry-run) Would set price {$price} for {$slug}";
        } else {
          if ( function_exists( 'wc_get_product' ) ) {
            $wc = wc_get_product( $pid );
            if ( $wc ) {
              $wc->set_regular_price( $price );
              $wc->save();
              update_post_meta( $pid, '_regular_price', $price );
              update_post_meta( $pid, '_price', $price );
            }
          } else {
            update_post_meta( $pid, '_regular_price', $price );
            update_post_meta( $pid, '_price', $price );
          }
        }
      }

      // handle featured image + gallery
      $gallery_ids = array();

      // If images specified in products.json, prefer them
      if ( ! empty( $prod['images'] ) && is_array( $prod['images'] ) ) {
        $first_image = wp_basename( $prod['images'][0] );
        $att_id = $find_attachment_by_filename( $first_image );
        if ( ! $att_id ) {
          $att_id = $import_theme_image( $first_image, $log );
        }
        if ( $att_id ) {
          if ( ! $is_dry && $pid ) set_post_thumbnail( $pid, $att_id );
        } else {
          $missing_images[] = $first_image;
          $log[] = "Missing featured image for {$slug}: {$first_image}";
        }
      } else {
        // attempt to discover theme images by slug
        $discovered = $discover_images_for_slug( $slug );
        if ( ! empty( $discovered['primary'] ) ) {
          $first = $discovered['primary'][0];
          $att = $find_attachment_by_filename( $first );
          if ( ! $att ) $att = $import_theme_image( $first, $log );
          if ( $att ) {
            if ( ! $is_dry && $pid ) set_post_thumbnail( $pid, $att );
            $log[] = ( $is_dry ? "(dry-run) Would auto-assign featured image for {$slug}: {$first}" : "Auto-assigned featured image for {$slug}: {$first}" );
          } else {
            $missing_images[] = $first;
            $log[] = "Missing auto-discovered featured image for {$slug}: {$first}";
          }
        }
        // add secondary images as gallery
        if ( ! empty( $discovered['secondary'] ) ) {
          foreach ( $discovered['secondary'] as $sfile ) {
            $att = $find_attachment_by_filename( $sfile );
            if ( ! $att ) $att = $import_theme_image( $sfile, $log );
            if ( $att ) {
              $gallery_ids[] = $att;
            } else {
              $missing_images[] = $sfile;
              $log[] = "Missing auto-discovered gallery image for {$slug}: {$sfile}";
            }
          }
        }
        // no additional non-webp fallbacks; only primary and secondary webp images are considered
      }

      // If explicit gallery entries present, append them (after discovery)
      if ( ! empty( $prod['gallery'] ) && is_array( $prod['gallery'] ) ) {
        foreach ( $prod['gallery'] as $gfile ) {
          $gbase = wp_basename( $gfile );
          $gid = $find_attachment_by_filename( $gbase );
          if ( ! $gid ) {
            $gid = $import_theme_image( $gbase, $log );
          }
          if ( $gid ) {
            $gallery_ids[] = $gid;
          } else {
            $missing_images[] = $gbase;
            $log[] = "Missing gallery image for {$slug}: {$gbase}";
          }
        }
      }

      if ( ! empty( $gallery_ids ) ) {
        if ( ! $is_dry && $pid ) {
          update_post_meta( $pid, '_product_image_gallery', implode( ',', $gallery_ids ) );
        } else {
          $log[] = "(dry-run) Would set product gallery for {$slug}: " . implode( ',', $gallery_ids );
        }
      }

      // features and badge metadata
      if ( isset( $prod['badge'] ) ) {
        if ( ! $is_dry && $pid ) {
          update_post_meta( $pid, 'beslock_badge', sanitize_text_field( $prod['badge'] ) );
        } else {
          $log[] = "(dry-run) Would set badge for {$slug}: " . sanitize_text_field( $prod['badge'] );
        }
      }
      if ( isset( $prod['features'] ) && is_array( $prod['features'] ) ) {
        if ( ! $is_dry && $pid ) {
          update_post_meta( $pid, 'beslock_features', array_map( 'sanitize_text_field', $prod['features'] ) );
        } else {
          $log[] = "(dry-run) Would set features for {$slug}: " . implode( ',', array_map( 'sanitize_text_field', $prod['features'] ) );
        }
      }

      $updated++;
    }

    // write a persisted log file when not a dry-run
    if ( ! $is_dry ) {
      $summary_lines = array(
        'Created: ' . $created,
        'Updated: ' . $updated,
        'Skipped: ' . count( $skipped ),
        'Missing images: ' . count( array_values( array_unique( $missing_images ) ) ),
        'Duplicated slugs: ' . count( $duplicated_slugs ),
      );
      $log_dir = get_stylesheet_directory() . '/import_logs';
      if ( ! is_dir( $log_dir ) ) {
        @mkdir( $log_dir, 0755, true );
      }
      $log_file = trailingslashit( $log_dir ) . 'carga_portfolio_' . date( 'Ymd_His' ) . '.log';
      $content = "Summary:\n" . implode( "\n", $summary_lines ) . "\n\nLog:\n" . implode( "\n", $log );
      $written = @file_put_contents( $log_file, $content );
      if ( $written === false ) {
        $log[] = "Failed to write import log to {$log_file}";
      } else {
        $log[] = "Wrote import log to {$log_file}";
      }
    }

    return array(
      'created' => $created,
      'updated' => $updated,
      'skipped' => $skipped,
      'missing_images' => array_values( array_unique( $missing_images ) ),
      'duplicated_slugs' => $duplicated_slugs,
      'log' => $log,
    );
  }
}

// Admin helper: render a small summary (used when included from an admin page)
if ( ! function_exists( 'beslock_carga_portfolio_admin_ui' ) ) {
  function beslock_carga_portfolio_admin_ui() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( __( 'Insufficient permissions', 'beslock' ) );
    }

    echo '<div class="wrap"><h1>' . esc_html__( 'Cargar Portfolio Data', 'beslock' ) . '</h1>';

    if ( isset( $_POST['beslock_carga_run'] ) ) {
      check_admin_referer( 'beslock_carga_portfolio_nonce' );
      $dry_run_flag = isset( $_POST['beslock_carga_dryrun'] ) && $_POST['beslock_carga_dryrun'] ? true : false;
      try {
        $res = beslock_carga_portfolio_process( $dry_run_flag );
        if ( is_wp_error( $res ) ) {
          echo '<div class="notice notice-error"><p>' . esc_html( $res->get_error_message() ) . '</p></div>';
        } else {
          echo '<div class="notice notice-success"><p>' . esc_html__( 'Import finished. See log below.', 'beslock' ) . '</p></div>';
          echo '<h2>Summary</h2>';
          echo '<ul>';
          echo '<li>Created: ' . intval( $res['created'] ) . '</li>';
          echo '<li>Updated: ' . intval( $res['updated'] ) . '</li>';
          echo '<li>Skipped: ' . count( $res['skipped'] ) . '</li>';
          echo '<li>Missing images: ' . count( $res['missing_images'] ) . '</li>';
          echo '<li>Duplicated slugs: ' . count( $res['duplicated_slugs'] ) . '</li>';
          echo '</ul>';
          echo '<h2>Log</h2>';
          echo '<pre style="white-space:pre-wrap; background:#fff; border:1px solid #ddd; padding:12px;">' . esc_html( implode( "\n", $res['log'] ) ) . '</pre>';
        }
      } catch ( Throwable $e ) {
        $err = sprintf( 'Import failed with exception: %s on line %d', $e->getMessage(), $e->getLine() );
        echo '<div class="notice notice-error"><p>' . esc_html( $err ) . '</p></div>';
        // try to persist exception to log dir
        $log_dir = get_stylesheet_directory() . '/import_logs';
        if ( ! is_dir( $log_dir ) ) {@mkdir( $log_dir, 0755, true );}
        $log_file = trailingslashit( $log_dir ) . 'carga_portfolio_error_' . date( 'Ymd_His' ) . '.log';
        @file_put_contents( $log_file, $e->getMessage() . "\n" . $e->getTraceAsString() );
      }
    }

    echo '<form method="post">' . wp_nonce_field( 'beslock_carga_portfolio_nonce' );
    echo '<p>' . esc_html__( 'This will read data/products.json and create/update WooCommerce products accordingly.', 'beslock' ) . '</p>';
    echo '<p><label><input type="checkbox" name="beslock_carga_dryrun" value="1" checked> ' . esc_html__( 'Dry run (no changes, just report)', 'beslock' ) . '</label></p>';
    echo '<p><button type="submit" name="beslock_carga_run" class="button button-primary">' . esc_html__( 'Regenerar catálogo', 'beslock' ) . '</button></p>';
    echo '</form></div>';
  }
}

?>
