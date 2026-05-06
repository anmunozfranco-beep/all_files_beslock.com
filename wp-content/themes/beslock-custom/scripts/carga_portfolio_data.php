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

if ( ! function_exists( 'beslock_import_images_from_assets' ) ) {
  /**
   * Import images from theme assets into uploads and assign featured/gallery to products.
   * Returns array(summary) or WP_Error.
   */
  function beslock_import_images_from_assets( $dry_run = true ) {
    $log = array();
    $is_dry = (bool) $dry_run;
    $theme_dir = get_stylesheet_directory();
    $data_file = $theme_dir . '/data/products.json';
    if ( ! file_exists( $data_file ) ) return new WP_Error( 'no_file', 'products.json not found' );
    $json = file_get_contents( $data_file );
    $data = json_decode( $json, true );
    if ( ! is_array( $data ) ) return new WP_Error( 'invalid_json', 'products.json invalid' );

    global $wpdb;
    $imported = 0;
    $assigned = 0;
    $missing = array();

    $search_dirs = array(
      $theme_dir . '/assets/images/products/',
      $theme_dir . '/assets/images/',
    );

    foreach ( $data as $prod ) {
      if ( empty( $prod['slug'] ) ) continue;
      $slug = sanitize_title( $prod['slug'] );
      $found_files = array();
      // primary candidate slug_.webp and slug.webp
      foreach ( $search_dirs as $d ) {
        if ( ! is_dir( $d ) ) continue;
        $p1 = trailingslashit( $d ) . $slug . '_.webp';
        $p2 = trailingslashit( $d ) . $slug . '.webp';
        if ( file_exists( $p1 ) ) $found_files[] = $p1;
        if ( file_exists( $p2 ) ) $found_files[] = $p2;
        // glob secondaries
        foreach ( glob( trailingslashit( $d ) . $slug . '_*.webp' ) as $g ) {
          if ( ! in_array( $g, $found_files ) ) $found_files[] = $g;
        }
      }

      if ( empty( $found_files ) ) {
        $log[] = "No assets found for {$slug}";
        $missing[] = $slug;
        continue;
      }

      // find product by slug or title
      $existing = get_page_by_path( $slug, OBJECT, 'product' );
      if ( ! $existing && ! empty( $prod['title'] ) ) {
        $existing = get_page_by_title( $prod['title'], OBJECT, 'product' );
      }
      $pid = $existing ? $existing->ID : 0;

      $gallery_ids = array();
      foreach ( $found_files as $file_path ) {
        $basename = wp_basename( $file_path );
        // check existing attachment by filename (without ext)
        $name_no_ext = pathinfo( $basename, PATHINFO_FILENAME );
        $like = '%' . $wpdb->esc_like( $name_no_ext ) . '%';
        $sql = $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type='attachment' AND pm.meta_key='_wp_attached_file' AND pm.meta_value LIKE %s LIMIT 1", $like );
        $att_id = $wpdb->get_var( $sql );
        if ( $att_id ) {
          $log[] = "Reused attachment {$att_id} for {$basename}";
        } else {
          if ( $is_dry ) {
            $log[] = "(dry-run) Would import {$basename} for product {$slug}";
            $att_id = 0;
          } else {
            // copy to uploads and create attachment
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $upload_dir = wp_upload_dir();
            $unique = wp_unique_filename( $upload_dir['path'], $basename );
            $new_path = trailingslashit( $upload_dir['path'] ) . $unique;
            if ( ! copy( $file_path, $new_path ) ) {
              $log[] = "Failed to copy {$file_path} to uploads";
              continue;
            }
            $filetype = wp_check_filetype( $unique );
            $attachment = array(
              'post_mime_type' => $filetype['type'] ?: 'image/webp',
              'post_title' => sanitize_file_name( pathinfo( $unique, PATHINFO_FILENAME ) ),
              'post_content' => '',
              'post_status' => 'inherit',
            );
            $att_id = wp_insert_attachment( $attachment, $new_path );
            if ( is_wp_error( $att_id ) || ! $att_id ) {
              $log[] = "Failed to insert attachment for {$basename}";
              continue;
            }
            $meta = wp_generate_attachment_metadata( $att_id, $new_path );
            wp_update_attachment_metadata( $att_id, $meta );
            $imported++;
            $log[] = "Imported asset {$basename} as attachment {$att_id}";
          }
        }

        if ( $att_id ) $gallery_ids[] = intval( $att_id );
      }

      // assign featured image (first) and gallery
      if ( ! empty( $gallery_ids ) && $pid ) {
        if ( ! $is_dry ) {
          set_post_thumbnail( $pid, $gallery_ids[0] );
          if ( count( $gallery_ids ) > 1 ) update_post_meta( $pid, '_product_image_gallery', implode( ',', array_slice( $gallery_ids, 1 ) ) );
          $assigned++;
          $log[] = "Assigned images to product {$slug} (ID {$pid})";
        } else {
          $log[] = "(dry-run) Would assign images to product {$slug}: " . implode( ',', $gallery_ids );
        }
      } elseif ( ! empty( $gallery_ids ) ) {
        $log[] = "Images imported for {$slug} but product not found (skipped assignment)";
      }
    }

    // persist log to option
    try { update_option( 'beslock_last_import_log', implode( "\n", $log ) ); } catch ( Exception $e ) {}

    return array( 'imported' => $imported, 'assigned' => $assigned, 'missing' => $missing, 'log' => $log );
  }
}

if ( ! function_exists( 'beslock_carga_portfolio_process' ) ) {
  function beslock_carga_portfolio_process( $dry_run = false ) {
    $log = array();
    $is_dry = (bool) $dry_run;

    $data_file = get_stylesheet_directory() . '/data/products.json';
    if ( ! file_exists( $data_file ) ) {
      $err = new WP_Error( 'no_file', sprintf( __( 'products.json not found: %s', 'beslock' ), $data_file ) );
      try { update_option( 'beslock_last_import_log', $err->get_error_message() ); } catch ( Exception $e ) { }
      return $err;
    }

    $json = file_get_contents( $data_file );
    if ( $json === false ) {
      $err = new WP_Error( 'read_error', __( 'Unable to read products.json', 'beslock' ) );
      try { update_option( 'beslock_last_import_log', $err->get_error_message() ); } catch ( Exception $e ) { }
      return $err;
    }

    $data = json_decode( $json, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
      $msg = json_last_error_msg();
      $err = new WP_Error( 'json_error', $msg );
      try { update_option( 'beslock_last_import_log', $msg ); } catch ( Exception $e ) { }
      return $err;
    }

    if ( ! is_array( $data ) ) {
      $err = new WP_Error( 'invalid_format', __( 'products.json must be an array of product objects', 'beslock' ) );
      try { update_option( 'beslock_last_import_log', $err->get_error_message() ); } catch ( Exception $e ) { }
      return $err;
    }

    // helper: persist current log to WP option so admin UI can show progress even on crash
    $persist_log = function() use ( &$log ) {
      try { update_option( 'beslock_last_import_log', implode( "\n", $log ) ); } catch ( Exception $e ) { }
    };

    // helper: find attachment ID by filename (basename match)
    $find_attachment_by_filename = function( $filename ) {
      global $wpdb;
      // match by basename without extension so webp attachments are found
      $basename = wp_basename( $filename );
      $name_no_ext = pathinfo( $basename, PATHINFO_FILENAME );
      $like = '%' . $wpdb->esc_like( $name_no_ext ) . '%';
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

      $basename = wp_basename( $filename );
      $name_no_ext = pathinfo( $basename, PATHINFO_FILENAME );

      // Prefer primary pattern: slug + '_' (e.g. e-orbit_.webp)
      $primary_candidate = $name_no_ext . '_.webp';
      // Also accept plain webp fallback (slug.webp)
      $fallback_candidate = $name_no_ext . '.webp';

      foreach ( $search_dirs as $d ) {
        $p = trailingslashit( $d ) . $primary_candidate;
        if ( file_exists( $p ) ) return $p;
        $p2 = trailingslashit( $d ) . $fallback_candidate;
        if ( file_exists( $p2 ) ) return $p2;
        // also accept explicit filename passed (with extension)
        $explicit = trailingslashit( $d ) . $basename;
        if ( file_exists( $explicit ) ) return $explicit;
      }

      return '';
    };

    // helper: import theme image if present and not already in uploads
    $import_theme_image = function( $filename, &$log ) use ( $find_attachment_by_filename, $find_theme_file, &$is_dry ) {
      // accept either basename with extension or name without extension
      $theme_path = $find_theme_file( $filename );
      if ( ! $theme_path ) {
        // try basename without extension
        $theme_path = $find_theme_file( pathinfo( wp_basename( $filename ), PATHINFO_FILENAME ) );
      }
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
    // Primary convention: slug_.webp (e.g. e-orbit_.webp)
    // Secondary convention: slug_*.webp (anything with an extra part after underscore)
    $discover_images_for_slug = function( $slug ) {
      $dirs = array(
        get_stylesheet_directory() . '/assets/images/products/',
        get_stylesheet_directory() . '/assets/images/',
      );
      $found = array( 'primary' => array(), 'secondary' => array() );
      foreach ( $dirs as $d ) {
        if ( ! is_dir( $d ) ) continue;
        $pattern = trailingslashit( $d ) . $slug . '_*.webp';
        foreach ( glob( $pattern ) as $path ) {
          $base = wp_basename( $path );
          // primary exact match slug_.webp
          if ( strcasecmp( $base, $slug . '_.webp' ) === 0 ) {
            $found['primary'][] = $base;
          } else {
            $found['secondary'][] = $base;
          }
        }
        // also accept plain slug.webp as fallback
        $plain = trailingslashit( $d ) . $slug . '.webp';
        if ( file_exists( $plain ) ) {
          $found['primary'][] = wp_basename( $plain );
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

    $data_modified = false;
    foreach ( $data as $idx => $prod ) {
      if ( ! isset( $prod['slug'] ) || empty( $prod['slug'] ) ) {
        $log[] = 'Skipping product with missing slug';
        $persist_log();
        continue;
      }

      $slug = sanitize_title( $prod['slug'] );
      if ( isset( $seen_slugs[ $slug ] ) ) {
        $duplicated_slugs[] = $slug;
        $log[] = "Duplicate slug detected and skipped: {$slug}";
        continue;
      }
      $seen_slugs[ $slug ] = true;

      // find existing product by slug; fall back to matching by title (useful when a dummy product exists)
      $existing = get_page_by_path( $slug, OBJECT, 'product' );
      if ( ! $existing && ! empty( $prod['title'] ) ) {
        // try to find by exact title match
        $by_title = get_page_by_title( $prod['title'], OBJECT, 'product' );
        if ( $by_title ) {
          $existing = $by_title;
          $log[] = "Found existing product by title for {$slug}: ID {$existing->ID}";
        }
      }

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
          // ensure minimal WooCommerce product metadata so product exists in empty store
          if ( ! $is_dry && $pid ) {
            // stock / visibility defaults
            update_post_meta( $pid, '_stock_status', 'instock' );
            update_post_meta( $pid, '_manage_stock', 'no' );
            update_post_meta( $pid, '_stock', '' );
            update_post_meta( $pid, '_virtual', 'no' );
            update_post_meta( $pid, '_downloadable', 'no' );
            // visibility (older WP/WC versions)
            update_post_meta( $pid, '_visibility', 'visible' );
             // set product type to simple to avoid theme hooks assuming variations
             if ( function_exists( 'wp_set_object_terms' ) ) {
              wp_set_object_terms( $pid, 'simple', 'product_type' );
              $log[] = "Set product type to simple for {$slug}";
            }
          } else {
            $log[] = "(dry-run) Would set minimal product metadata for {$slug}";
          }
        }
      }

      // If JSON lacks a short_description, try to use existing product excerpt
      if ( empty( $prod['short_description'] ) && $pid ) {
        $existing_excerpt = get_post_field( 'post_excerpt', $pid );
        if ( ! empty( $existing_excerpt ) ) {
          $data[ $idx ]['short_description'] = $existing_excerpt;
          $prod['short_description'] = $existing_excerpt;
          $data_modified = true;
          $log[] = "Filled missing short_description for {$slug} from existing post_excerpt";
          $persist_log();
        }
      }

      // update title/short description if needed
      $update_post = array( 'ID' => $pid );
      $changed = false;
      // ensure the post_name (slug) reflects the canonical slug from products.json
      if ( $pid && ! empty( $slug ) && $slug !== get_post_field( 'post_name', $pid ) ) {
        $update_post['post_name'] = $slug;
        $changed = true;
        $log[] = "Will update post_name (slug) for product ID {$pid} to {$slug}";
      }
      // map long description -> post_content
      if ( isset( $prod['description'] ) && $prod['description'] !== get_post_field( 'post_content', $pid ) ) {
        $update_post['post_content'] = $prod['description'];
        $changed = true;
      }
      // SKU mapping
      if ( isset( $prod['sku'] ) ) {
        $sku = sanitize_text_field( $prod['sku'] );
        if ( $sku !== get_post_meta( $pid, '_sku', true ) ) {
          if ( ! $is_dry ) {
            update_post_meta( $pid, '_sku', $sku );
          } else {
            $log[] = "(dry-run) Would set _sku for {$slug}: {$sku}";
          }
        }
      }
      // stock and manage_stock
      if ( isset( $prod['manage_stock'] ) ) {
        $manage = $prod['manage_stock'] ? 'yes' : 'no';
        if ( ! $is_dry ) {
          update_post_meta( $pid, '_manage_stock', $manage );
        } else {
          $log[] = "(dry-run) Would set _manage_stock for {$slug}: {$manage}";
        }
      }
      if ( isset( $prod['stock'] ) ) {
        if ( ! $is_dry ) {
          update_post_meta( $pid, '_stock', sanitize_text_field( $prod['stock'] ) );
        } else {
          $log[] = "(dry-run) Would set _stock for {$slug}: " . sanitize_text_field( $prod['stock'] );
        }
      }
      if ( isset( $prod['stock_status'] ) ) {
        if ( ! $is_dry ) {
          update_post_meta( $pid, '_stock_status', sanitize_text_field( $prod['stock_status'] ) );
        } else {
          $log[] = "(dry-run) Would set _stock_status for {$slug}: " . sanitize_text_field( $prod['stock_status'] );
        }
      }
      // weight and dimensions
      if ( isset( $prod['weight'] ) ) {
        if ( ! $is_dry ) {
          update_post_meta( $pid, '_weight', sanitize_text_field( $prod['weight'] ) );
        } else {
          $log[] = "(dry-run) Would set _weight for {$slug}: " . sanitize_text_field( $prod['weight'] );
        }
      }
      if ( isset( $prod['length'] ) || isset( $prod['width'] ) || isset( $prod['height'] ) ) {
        if ( ! $is_dry ) {
          if ( isset( $prod['length'] ) ) update_post_meta( $pid, '_length', sanitize_text_field( $prod['length'] ) );
          if ( isset( $prod['width'] ) ) update_post_meta( $pid, '_width', sanitize_text_field( $prod['width'] ) );
          if ( isset( $prod['height'] ) ) update_post_meta( $pid, '_height', sanitize_text_field( $prod['height'] ) );
        } else {
          $log[] = "(dry-run) Would set dimensions for {$slug}";
        }
      }
      // categories (by slug or name)
      if ( ! empty( $prod['categories'] ) && is_array( $prod['categories'] ) ) {
        $cats = array_map( 'sanitize_text_field', $prod['categories'] );
        if ( ! $is_dry ) {
          wp_set_object_terms( $pid, $cats, 'product_cat' );
        } else {
          $log[] = "(dry-run) Would set categories for {$slug}: " . implode( ',', $cats );
        }
      }
      // tags
      if ( ! empty( $prod['tags'] ) && is_array( $prod['tags'] ) ) {
        $tags = array_map( 'sanitize_text_field', $prod['tags'] );
        if ( ! $is_dry ) {
          wp_set_post_terms( $pid, $tags, 'product_tag' );
        } else {
          $log[] = "(dry-run) Would set tags for {$slug}: " . implode( ',', $tags );
        }
      }
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
        $persist_log();
      }

      // set price
      $price = isset( $prod['price'] ) ? trim( (string) $prod['price'] ) : '';
      if ( $price !== '' ) {
        if ( $is_dry ) {
          $log[] = "(dry-run) Would set price {$price} for {$slug}";
          $persist_log();
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
        // Only consider webp variants: use basename without extension so find/import will map to .webp
        $first_image = wp_basename( $prod['images'][0] );
        $first_base = pathinfo( $first_image, PATHINFO_FILENAME );
        $att_id = $find_attachment_by_filename( $first_base );
        if ( ! $att_id ) {
          $att_id = $import_theme_image( $first_base, $log );
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
            $persist_log();
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
            $persist_log();
          }
        }
        // no additional non-webp fallbacks; only primary and secondary webp images are considered
      }

      // If explicit gallery entries present, append them (after discovery)
      if ( ! empty( $prod['gallery'] ) && is_array( $prod['gallery'] ) ) {
        foreach ( $prod['gallery'] as $gfile ) {
          // use basename without extension so we only match/import .webp candidates
          $gbase = wp_basename( $gfile );
          $gbase_no_ext = pathinfo( $gbase, PATHINFO_FILENAME );
          $gid = $find_attachment_by_filename( $gbase_no_ext );
          if ( ! $gid ) {
            $gid = $import_theme_image( $gbase_no_ext, $log );
          }
          if ( $gid ) {
            $gallery_ids[] = $gid;
          } else {
            $missing_images[] = $gbase;
            $log[] = "Missing gallery image for {$slug}: {$gbase}";
          }
          $persist_log();
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
        // fallback to uploads if theme dir is not writable, otherwise use sys temp dir
        $use_dir = $log_dir;
        if ( ! is_dir( $use_dir ) || ! is_writable( $use_dir ) ) {
          $upload_dir = wp_upload_dir();
          $use_dir = trailingslashit( $upload_dir['basedir'] ) . 'beslock_import_logs';
          if ( ! is_dir( $use_dir ) ) {
            @mkdir( $use_dir, 0755, true );
          }
        }
        if ( ! is_dir( $use_dir ) || ! is_writable( $use_dir ) ) {
          $tmp = sys_get_temp_dir();
          $use_dir = trailingslashit( $tmp ) . 'beslock_import_logs';
          if ( ! is_dir( $use_dir ) ) {
            @mkdir( $use_dir, 0755, true );
          }
        }
      $log_file = trailingslashit( $use_dir ) . 'carga_portfolio_' . date( 'Ymd_His' ) . '.log';
      $content = "Summary:\n" . implode( "\n", $summary_lines ) . "\n\nLog:\n" . implode( "\n", $log );
      $written = @file_put_contents( $log_file, $content );
      if ( $written === false ) {
        $log[] = "Failed to write import log to {$log_file}";
      } else {
        $log[] = "Wrote import log to {$log_file}";
        // persist last log into options for admin inspection
        try { update_option( 'beslock_last_import_log', $content ); } catch ( Exception $e ) { }
      }
    }

    // always persist last log (including dry-run) so admin UI can show it immediately
    $summary_lines_all = array(
      'Created: ' . $created,
      'Updated: ' . $updated,
      'Skipped: ' . count( $skipped ),
      'Missing images: ' . count( array_values( array_unique( $missing_images ) ) ),
      'Duplicated slugs: ' . count( $duplicated_slugs ),
    );
    $full_content = "Summary:\n" . implode( "\n", $summary_lines_all ) . "\n\nLog:\n" . implode( "\n", $log );
    try { update_option( 'beslock_last_import_log', $full_content ); } catch ( Exception $e ) { }

    // If we filled missing short_descriptions from existing posts, write an updated data file for review
    if ( $data_modified ) {
      $updated_file = dirname( $data_file ) . '/products.updated.json';
      @file_put_contents( $updated_file, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
      $log[] = "Wrote updated data file with filled short_descriptions: {$updated_file}";
      try { update_option( 'beslock_last_import_log', implode( "\n", $log ) ); } catch ( Exception $e ) { }
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

if ( ! function_exists( 'beslock_generate_products_csv' ) ) {
  /**
   * Generate a WooCommerce-importable CSV from data/products.json.
   * Attempts to fetch rendered product pages (DOM) for richer fields, falls back to JSON.
   * Returns path to CSV or WP_Error.
   */
  function beslock_generate_products_csv( $use_dom = true ) {
    $theme_dir = get_stylesheet_directory();
    $data_file = $theme_dir . '/data/products.json';
    if ( ! file_exists( $data_file ) ) return new WP_Error( 'no_file', 'products.json not found for CSV generation' );
    $json = file_get_contents( $data_file );
    $data = json_decode( $json, true );
    if ( ! is_array( $data ) ) return new WP_Error( 'invalid_json', 'products.json invalid' );

    $csv_path = $theme_dir . '/data/products.csv';
    $fh = @fopen( $csv_path, 'w' );
    if ( ! $fh ) return new WP_Error( 'no_write', 'Unable to open CSV for writing: ' . $csv_path );

    // Header matching common WooCommerce importer columns (minimal set)
    $headers = array( 'Name','Slug','Description','Short description','Regular price','SKU','Categories','Tags','Images' );
    fputcsv( $fh, $headers );

    foreach ( $data as $prod ) {
      $row = array_fill( 0, count( $headers ), '' );
      $name = isset( $prod['title'] ) ? $prod['title'] : ( isset( $prod['slug'] ) ? $prod['slug'] : '' );
      $slug = isset( $prod['slug'] ) ? $prod['slug'] : sanitize_title( $name );
      $desc = isset( $prod['description'] ) ? $prod['description'] : '';
      $short = isset( $prod['short_description'] ) ? $prod['short_description'] : '';
      $price = isset( $prod['price'] ) ? $prod['price'] : '';
      $sku = isset( $prod['sku'] ) ? $prod['sku'] : '';
      $cats = isset( $prod['categories'] ) && is_array( $prod['categories'] ) ? implode( '|', $prod['categories'] ) : '';
      $tags = isset( $prod['tags'] ) && is_array( $prod['tags'] ) ? implode( ', ', $prod['tags'] ) : '';
      $images_csv = '';

      if ( $use_dom ) {
        // attempt to find an existing product and fetch its permalink
        $existing = get_page_by_path( $slug, OBJECT, 'product' );
        if ( ! $existing && ! empty( $prod['title'] ) ) {
          $existing = get_page_by_title( $prod['title'], OBJECT, 'product' );
        }
        if ( $existing ) {
          $permalink = get_permalink( $existing->ID );
          if ( $permalink ) {
            $res = wp_remote_get( $permalink );
            if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
              $html = wp_remote_retrieve_body( $res );
              if ( ! empty( $html ) ) {
                libxml_use_internal_errors( true );
                $doc = new DOMDocument();
                $doc->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
                $xpath = new DOMXPath( $doc );
                // title
                $nodes = $xpath->query("//h1[contains(@class,'product_title')] | //h1[contains(@class,'entry-title')] | //h1");
                if ( $nodes->length ) $name = trim( $nodes->item(0)->textContent );
                // short description
                $nodes = $xpath->query("//*[contains(@class,'short-description')] | //div[contains(@class,'woocommerce-product-details__short-description')] | //div[contains(@class,'entry-summary')]/p");
                if ( $nodes->length ) $short = trim( $nodes->item(0)->textContent );
                // description
                $nodes = $xpath->query("//div[contains(@class,'woocommerce-Tabs-panel') or contains(@class,'description')] | //div[contains(@class,'entry-content')]");
                if ( $nodes->length ) $desc = trim( preg_replace('/\s+/', ' ', $nodes->item(0)->textContent) );
                // price
                $nodes = $xpath->query("//*[contains(@class,'price')]//span[contains(@class,'amount')] | //p[contains(@class,'price')]//span");
                if ( $nodes->length ) {
                  $price_text = trim( $nodes->item(0)->textContent );
                  $price_digits = preg_replace('/[^0-9\.,]/', '', $price_text );
                  $price = str_replace( array( ',', ' ' ), array( '.', '' ), $price_digits );
                }
                // images: gather product gallery images
                $img_nodes = $xpath->query("//figure[contains(@class,'woocommerce-product-gallery__image')]//img | //div[contains(@class,'product-gallery')]//img | //img[contains(@class,'wp-post-image')]");
                $imgs = array();
                foreach ( $img_nodes as $n ) {
                  $src = $n->getAttribute('src');
                  if ( $src ) $imgs[] = $src;
                }
                if ( ! empty( $imgs ) ) {
                  // CSV expects comma separated list of image URLs
                  $images_csv = implode( ',', array_unique( $imgs ) );
                }
              }
            }
          }
        }
      }

      // fallback to JSON images if none found from DOM
      if ( empty( $images_csv ) && ! empty( $prod['images'] ) ) {
        $images_csv = implode( ',', $prod['images'] );
      }

      $row = array( $name, $slug, $desc, $short, $price, $sku, $cats, $tags, $images_csv );
      fputcsv( $fh, $row );
    }

    fclose( $fh );
    return $csv_path;
  }
}

// Admin helper: render a small summary (used when included from an admin page)
if ( ! function_exists( 'beslock_carga_portfolio_admin_ui' ) ) {
  function beslock_carga_portfolio_admin_ui() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( __( 'Insufficient permissions', 'beslock' ) );
    }

    echo '<div class="wrap"><h1>' . esc_html__( 'Cargar Portfolio Data', 'beslock' ) . '</h1>';

    // show last persisted log from options (if any)
    $last_log = get_option( 'beslock_last_import_log', '' );
    if ( ! empty( $last_log ) ) {
      echo '<h2>' . esc_html__( 'Último log persistido', 'beslock' ) . '</h2>';
      echo '<pre style="white-space:pre-wrap; background:#fff; border:1px solid #ddd; padding:12px;">' . esc_html( $last_log ) . '</pre>';
    }

    if ( isset( $_POST['beslock_carga_run'] ) ) {
      check_admin_referer( 'beslock_carga_portfolio_nonce' );
      $dry_run_flag = isset( $_POST['beslock_carga_dryrun'] ) && $_POST['beslock_carga_dryrun'] ? true : false;
      $images_only = isset( $_POST['beslock_import_images'] ) && $_POST['beslock_import_images'];

      // convert PHP errors to exceptions so they can be caught
      set_error_handler( function( $severity, $message, $file, $line ) {
        throw new \ErrorException( $message, 0, $severity, $file, $line );
      } );

      // shutdown handler to catch fatal errors that bypass try/catch
      register_shutdown_function( function() use ( $dry_run_flag ) {
        $err = error_get_last();
        if ( ! $err ) {
          return;
        }
        $msg = sprintf( "Shutdown error: [%s] %s in %s on line %d", $err['type'], $err['message'], $err['file'], $err['line'] );
        // determine log dir (theme first, then uploads)
        $log_dir = get_stylesheet_directory() . '/import_logs';
        if ( ! is_dir( $log_dir ) ) {
          @mkdir( $log_dir, 0755, true );
        }
        $use_dir = $log_dir;
        if ( ! is_dir( $use_dir ) || ! is_writable( $use_dir ) ) {
          $upload_dir = wp_upload_dir();
          $use_dir = trailingslashit( $upload_dir['basedir'] ) . 'beslock_import_logs';
          if ( ! is_dir( $use_dir ) ) {
            @mkdir( $use_dir, 0755, true );
          }
        }
        $log_file = trailingslashit( $use_dir ) . 'carga_portfolio_error_shutdown_' . date( 'Ymd_His' ) . '.log';
        $content = $msg . "\n\n" . var_export( $err, true ) . "\n\n" . implode( "\n", array_map( function( $k, $v ) { return "$k: $v"; }, array_keys( $_SERVER ), array_values( $_SERVER ) ) );
        @file_put_contents( $log_file, $content );
        try { update_option( 'beslock_last_import_log', $content ); } catch ( Exception $e ) { }
      } );

      try {
        // If user requested CSV generation, run that first
        if ( isset( $_POST['beslock_generate_csv'] ) && $_POST['beslock_generate_csv'] ) {
          $csv_res = beslock_generate_products_csv( true );
          if ( is_wp_error( $csv_res ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $csv_res->get_error_message() ) . '</p></div>';
          } else {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'CSV generado:', 'beslock' ) . ' ' . esc_html( $csv_res ) . '</p></div>';
          }
        }

        if ( $images_only ) {
          $res = beslock_import_images_from_assets( $dry_run_flag );
        } else {
          $res = beslock_carga_portfolio_process( $dry_run_flag );
        }
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
        try { update_option( 'beslock_last_import_log', $e->getMessage() . "\n" . $e->getTraceAsString() ); } catch ( Exception $ex ) { }
      }
    }
    // show CSV generator checkbox
    echo '<p><label><input type="checkbox" name="beslock_generate_csv" value="1"> ' . esc_html__( 'Generate CSV from products (try DOM render then JSON fallback)', 'beslock' ) . '</label></p>';

    echo '<form method="post">' . wp_nonce_field( 'beslock_carga_portfolio_nonce' );
    echo '<p>' . esc_html__( 'This will read data/products.json and create/update WooCommerce products accordingly.', 'beslock' ) . '</p>';
    echo '<p><label><input type="checkbox" name="beslock_carga_dryrun" value="1" checked> ' . esc_html__( 'Dry run (no changes, just report)', 'beslock' ) . '</label></p>';
    echo '<p><label><input type="checkbox" name="beslock_import_images" value="1"> ' . esc_html__( 'Only import images from theme assets and assign to products', 'beslock' ) . '</label></p>';
    echo '<p><button type="submit" name="beslock_carga_run" class="button button-primary">' . esc_html__( 'Regenerar catálogo', 'beslock' ) . '</button></p>';
    echo '</form></div>';
  }
}

?>
