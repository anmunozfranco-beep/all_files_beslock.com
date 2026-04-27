<?php
/*
Plugin Name: Beslock Import Tools
Description: Exposes "Import Portfolio Images" tools (imports images from wp-content/themes/beslock-custom/assets/images and assigns to products). Falls back to internal implementation if theme functions are missing.
Version: 1.0
Author: Beslock
*/

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/* ---------- Admin menu ---------- */
add_action( 'admin_menu', function() {
  add_management_page(
    __( 'Import Portfolio Images', 'beslock' ),
    __( 'Import Portfolio Images', 'beslock' ),
    'manage_options',
    'beslock-import-portfolio',
    'beslock_import_portfolio_page_plugin'
  );
} );

/* ---------- Page renderer ---------- */
function beslock_import_portfolio_page_plugin() {
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions', 'beslock' ) );
  }

  echo '<div class="wrap"><h1>' . esc_html__( 'Import Portfolio Images', 'beslock' ) . '</h1>';

  // Handle single import action: Importar productos
  if ( isset( $_POST['beslock_import_products'] ) ) {
    check_admin_referer( 'beslock_import_images_nonce' );
    $res = beslock_plugin_import_products();
    if ( is_wp_error( $res ) ) {
      echo '<div class="notice notice-error"><p>' . esc_html( $res->get_error_message() ) . '</p></div>';
    } else {
      // show summary
      echo '<div class="notice notice-success"><p>' . esc_html__( 'Import completed.', 'beslock' ) . '</p></div>';
      if ( isset( $res['created'] ) ) {
        echo '<p>' . sprintf( esc_html__( 'Products created: %d', 'beslock' ), intval( $res['created'] ) ) . '</p>';
      }
      if ( isset( $res['updated'] ) ) {
        echo '<p>' . sprintf( esc_html__( 'Products updated: %d', 'beslock' ), intval( $res['updated'] ) ) . '</p>';
      }
      if ( isset( $res['imported_images'] ) ) {
        echo '<p>' . sprintf( esc_html__( 'Images imported: %d', 'beslock' ), intval( $res['imported_images'] ) ) . '</p>';
      }
      // show mapping if available (helpful for debugging)
      if ( isset( $res['mapping'] ) && is_array( $res['mapping'] ) && ! empty( $res['mapping'] ) ) {
        echo '<h2>' . esc_html__( 'Mapping (slug => product ID)', 'beslock' ) . '</h2>';
        echo '<ul>';
        foreach ( $res['mapping'] as $s => $id ) {
          echo '<li>' . esc_html( $s ) . ' => ' . intval( $id ) . '</li>';
        }
        echo '</ul>';
      }
    }
  }

  echo '<form method="post">' . wp_nonce_field( 'beslock_import_images_nonce' );
  echo '<p>' . esc_html__( 'Esta acción importará los productos definidos en el tema, creará los productos en WooCommerce, importará las imágenes y establecerá el precio regular a 500000.', 'beslock' ) . '</p>';
  echo '<p><button type="submit" name="beslock_import_products" class="button button-primary">' . esc_html__( 'Importar productos', 'beslock' ) . '</button></p>';
  echo '</form></div>';
}

/* ---------- Fallback wrappers: prefer theme functions if available ---------- */
function beslock_plugin_import_portfolio_images() {
  if ( function_exists( 'beslock_import_portfolio_images' ) ) {
    return beslock_import_portfolio_images();
  }
  return beslock_plugin_do_import();
}

function beslock_plugin_assign_images_to_products() {
  if ( function_exists( 'beslock_assign_images_to_products' ) ) {
    return beslock_assign_images_to_products();
  }
  return beslock_plugin_do_assign();
}

function beslock_plugin_import_and_assign_portfolio_images() {
  if ( function_exists( 'beslock_import_and_assign_portfolio_images' ) ) {
    return beslock_import_and_assign_portfolio_images();
  }
  $imp = beslock_plugin_do_import();
  if ( is_wp_error( $imp ) ) {
    return $imp;
  }
  $assign = beslock_plugin_do_assign();
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

/* ---------- Internal implementation (copiado y simplificado del tema) ---------- */
function beslock_plugin_do_import() {
  $dir = get_stylesheet_directory() . '/assets/images';
  $fallback = WP_CONTENT_DIR . '/themes/beslock-custom/assets/images';
  if ( ! is_dir( $dir ) && is_dir( $fallback ) ) {
    $dir = $fallback;
  }

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
    if ( is_wp_error( $attach_id ) || ! $attach_id ) continue;

    $attach_data = wp_generate_attachment_metadata( $attach_id, $new_path );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    $imported[] = $attach_id;
  }

  return array( 'count' => count( $imported ), 'ids' => $imported );
}

function beslock_plugin_do_assign() {
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
          $matches[] = (object) array( 'ID' => $r->ID, 'file' => $r->file );
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
            $matches[] = (object) array( 'ID' => $r->ID, 'file' => $r->file );
          }
        }
      }
    }

    if ( empty( $matches ) ) {
      $skipped[] = $p->post_title;
      continue;
    }

    // choose featured (ends with '_') and gallery (ends with '_s')
    $choice = beslock_plugin_choose_featured_and_gallery( $matches );
    if ( $choice['featured'] ) {
      set_post_thumbnail( $p->ID, intval( $choice['featured'] ) );
    }
    if ( ! empty( $choice['gallery'] ) ) {
      $gallery_val = implode( ',', $choice['gallery'] );
      update_post_meta( $p->ID, '_product_image_gallery', $gallery_val );
    }

    $assigned++;
  }

  return array( 'assigned' => $assigned, 'skipped' => $skipped );
}

/**
 * Read $products array from the theme front-page products-portfolio template.
 * Returns array of products or WP_Error on failure.
 */
function beslock_plugin_read_portfolio_products() {
  $tpl = get_stylesheet_directory() . '/templates/blocks/products-portfolio.php';
  if ( ! file_exists( $tpl ) ) {
    return new WP_Error( 'missing_template', 'products-portfolio template not found: ' . $tpl );
  }
  $src = file_get_contents( $tpl );
  // Find the $products = [ ... ]; assignment
  $pos = strpos( $src, '$products' );
  if ( $pos === false ) {
    return new WP_Error( 'no_products', 'No $products array found in template' );
  }
  $start = strpos( $src, '=', $pos );
  if ( $start === false ) {
    return new WP_Error( 'no_assignment', 'Could not locate $products assignment' );
  }
  $start = strpos( $src, '[', $start );
  if ( $start === false ) {
    return new WP_Error( 'no_array', 'Could not locate array start for $products' );
  }
  // find matching bracket
  $i = $start;
  $len = strlen( $src );
  $depth = 0;
  for ( ; $i < $len; $i++ ) {
    if ( $src[ $i ] === '[' ) $depth++;
    if ( $src[ $i ] === ']' ) {
      $depth--;
      if ( $depth === 0 ) {
        $end = $i;
        break;
      }
    }
  }
  if ( ! isset( $end ) ) {
    return new WP_Error( 'no_end', 'Could not find end of $products array' );
  }
  $array_str = substr( $src, $start, $end - $start + 1 );
  // Evaluate the array in a safe-ish way by returning it
  $to_eval = "<?php return " . $array_str . ";";
  try {
    $tmpfile = wp_tempnam( 'beslock_products_' );
    if ( ! $tmpfile ) {
      return new WP_Error( 'tmpfail', 'Could not create temp file for parsing' );
    }
    file_put_contents( $tmpfile, $to_eval );
    $products = include $tmpfile;
    @unlink( $tmpfile );
    if ( ! is_array( $products ) ) {
      return new WP_Error( 'not_array', 'Parsed products is not an array' );
    }
    return $products;
  } catch ( Exception $e ) {
    return new WP_Error( 'eval_error', $e->getMessage() );
  }
}

/**
 * Given an array of rows (objects with ID and file), choose featured and gallery attachments.
 * Featured: filename base ends with '_' (underscore). Gallery: filename base ends with '_s'.
 * Fallback: if no featured candidate, use the first item as featured; others become gallery.
 * Returns array('featured' => int|null, 'gallery' => array)
 */
function beslock_plugin_choose_featured_and_gallery( $rows ) {
  $featured = null;
  $gallery = array();
  $others = array();
  foreach ( $rows as $r ) {
    $file = isset( $r->file ) ? $r->file : ( is_array( $r ) && isset( $r['file'] ) ? $r['file'] : '' );
    $id = isset( $r->ID ) ? intval( $r->ID ) : ( is_array( $r ) && isset( $r['ID'] ) ? intval( $r['ID'] ) : 0 );
    $base = pathinfo( $file, PATHINFO_FILENAME );
    if ( '' === $base ) {
      $others[] = $id;
      continue;
    }
    if ( substr( $base, -2 ) === '_s' || substr( $base, -2 ) === '-s' ) {
      $gallery[] = $id;
      continue;
    }
    if ( substr( $base, -1 ) === '_' || substr( $base, -1 ) === '-' ) {
      if ( ! $featured ) {
        $featured = $id;
        continue;
      }
      $others[] = $id;
      continue;
    }
    $others[] = $id;
  }

  if ( ! $featured ) {
    if ( ! empty( $others ) ) {
      $featured = array_shift( $others );
    } elseif ( ! empty( $gallery ) ) {
      $featured = array_shift( $gallery );
    }
  }

  // remaining gallery: preserve gallery candidates first, then the rest
  $gallery = array_merge( $gallery, $others );

  return array( 'featured' => $featured ? intval( $featured ) : null, 'gallery' => $gallery );
}

/**
 * Sync portfolio products into WooCommerce products and set regular price to 500000 for all products.
 * Returns array with created and updated counts.
 */
function beslock_plugin_sync_portfolio_products_and_set_price() {
  if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'wc_get_product' ) ) {
    // proceed but products post_type may not behave as WC expects
  }

  $read = beslock_plugin_read_portfolio_products();
  if ( is_wp_error( $read ) ) {
    return $read;
  }
  $created = 0;
  $updated = 0;
  foreach ( $read as $item ) {
    $name = isset( $item['name'] ) ? $item['name'] : '';
    $desc = isset( $item['desc'] ) ? $item['desc'] : '';
    $image = isset( $item['image'] ) ? $item['image'] : '';

    $slug = sanitize_title( $name );
    $existing = get_posts( array( 'post_type' => 'product', 'name' => $slug, 'posts_per_page' => 1 ) );
    if ( empty( $existing ) ) {
      $post = array(
        'post_title'   => $name,
        'post_content' => $desc,
        'post_status'  => 'publish',
        'post_type'    => 'product',
        'post_name'    => $slug,
      );
      $pid = wp_insert_post( $post );
      if ( is_wp_error( $pid ) || ! $pid ) {
        continue;
      }
      // Ensure slug is exactly the sanitized one and clear caches so template mapping finds it
      wp_update_post( array( 'ID' => $pid, 'post_name' => $slug ) );
      if ( function_exists( 'clean_post_cache' ) ) {
        clean_post_cache( $pid );
      }

      // If WooCommerce CRUD is available, use it to initialize product properties
      if ( function_exists( 'wc_get_product' ) ) {
        try {
          $wc = wc_get_product( $pid );
          if ( $wc ) {
            // set name/description explicitly
            if ( method_exists( $wc, 'set_name' ) ) {
              $wc->set_name( $name );
            }
            if ( method_exists( $wc, 'set_description' ) ) {
              $wc->set_description( $desc );
            }
            if ( method_exists( $wc, 'set_regular_price' ) ) {
              $wc->set_regular_price( '500000' );
            }
            if ( method_exists( $wc, 'set_price' ) ) {
              $wc->set_price( '500000' );
            }
            if ( method_exists( $wc, 'save' ) ) {
              $wc->save();
            }
          }
        } catch ( Exception $e ) {
          // ignore and continue — WP post exists even if WC not available
        }
      }

      $created++;
    } else {
      $pobj = $existing[0];
      $pid = $pobj->ID;
      // update title/content if needed
      wp_update_post( array( 'ID' => $pid, 'post_title' => $name, 'post_content' => $desc ) );
      // ensure slug matches sanitized name used by the template
      if ( isset( $pobj->post_name ) && $pobj->post_name !== $slug ) {
        wp_update_post( array( 'ID' => $pid, 'post_name' => $slug ) );
      }
      if ( function_exists( 'clean_post_cache' ) ) {
        clean_post_cache( $pid );
      }

      // If WooCommerce CRUD is available, use it to update product properties
      if ( function_exists( 'wc_get_product' ) ) {
        try {
          $wc = wc_get_product( $pid );
          if ( $wc ) {
            if ( method_exists( $wc, 'set_name' ) ) {
              $wc->set_name( $name );
            }
            if ( method_exists( $wc, 'set_description' ) ) {
              $wc->set_description( $desc );
            }
            if ( method_exists( $wc, 'set_regular_price' ) ) {
              $wc->set_regular_price( '500000' );
            }
            if ( method_exists( $wc, 'set_price' ) ) {
              $wc->set_price( '500000' );
            }
            if ( method_exists( $wc, 'save' ) ) {
              $wc->save();
            }
          }
        } catch ( Exception $e ) {
          // ignore
        }
      }

      $updated++;
    }

    // handle image import/attachment
    if ( $image ) {
      // if image is a URL, attempt to import
      if ( strpos( $image, 'http://' ) === 0 || strpos( $image, 'https://' ) === 0 ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        // check if attachment exists by URL
        $att_id = attachment_url_to_postid( $image );
        if ( ! $att_id ) {
          // sideload to this product
          $tmp = media_sideload_image( $image, $pid, null, 'src' );
          if ( ! is_wp_error( $tmp ) ) {
            $att_id = attachment_url_to_postid( $tmp );
          }
        }
        if ( $att_id ) {
          set_post_thumbnail( $pid, intval( $att_id ) );
        }
      } else {
        // theme-local path (URL may be get_stylesheet_directory_uri() . '/assets/...')
        // attempt to resolve to absolute file in theme and import if needed
        $theme_dir = get_stylesheet_directory();
        $maybe = str_replace( get_stylesheet_directory_uri(), $theme_dir, $image );
        if ( file_exists( $maybe ) ) {
          // check for existing attachment by filename
          $filename = wp_basename( $maybe );
          $existing_att = get_posts( array( 'post_type' => 'attachment', 'meta_query' => array( array( 'key' => '_wp_attached_file', 'value' => $filename, 'compare' => 'LIKE' ) ), 'posts_per_page' => 1 ) );
          if ( ! empty( $existing_att ) ) {
            set_post_thumbnail( $pid, $existing_att[0]->ID );
          } else {
            // copy into uploads and create attachment
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            $upload_dir = wp_upload_dir();
            $unique = wp_unique_filename( $upload_dir['path'], basename( $maybe ) );
            $new_path = trailingslashit( $upload_dir['path'] ) . $unique;
            if ( copy( $maybe, $new_path ) ) {
              $filetype = wp_check_filetype( $unique );
              $attachment = array(
                'post_mime_type' => $filetype['type'] ?: 'image/jpeg',
                'post_title'     => sanitize_file_name( pathinfo( $unique, PATHINFO_FILENAME ) ),
                'post_content'   => '',
                'post_status'    => 'inherit',
              );
              $attach_id = wp_insert_attachment( $attachment, $new_path );
              if ( ! is_wp_error( $attach_id ) && $attach_id ) {
                $attach_data = wp_generate_attachment_metadata( $attach_id, $new_path );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                set_post_thumbnail( $pid, $attach_id );
              }
            }
          }
        }
      }
    }

    // Assign gallery images when the template provides multiple images
    if ( ! empty( $item['images'] ) && is_array( $item['images'] ) ) {
      global $wpdb;
      $gallery_ids = array();
      foreach ( $item['images'] as $img_item ) {
        // normalize to filename
        $fname = wp_basename( $img_item );
        if ( ! $fname ) continue;
        $like = '%' . $wpdb->esc_like( $fname ) . '%';
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, pm.meta_value as file FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type='attachment' AND pm.meta_key='_wp_attached_file' AND pm.meta_value LIKE %s", $like ) );
        if ( ! empty( $rows ) ) {
          foreach ( $rows as $r ) {
            $gid = intval( $r->ID );
            if ( $gid && ! in_array( $gid, $gallery_ids, true ) ) {
              $gallery_ids[] = $gid;
            }
          }
        }
      }
      if ( ! empty( $gallery_ids ) ) {
        // if no featured set, pick first gallery item as featured
        $current_thumb = get_post_thumbnail_id( $pid );
        if ( ! $current_thumb ) {
          set_post_thumbnail( $pid, intval( $gallery_ids[0] ) );
        }
        update_post_meta( $pid, '_product_image_gallery', implode( ',', $gallery_ids ) );
      }
    }
  }

  // Set regular price 500000 for all products
  $all = get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'post_status' => array( 'publish', 'private', 'draft' ) ) );
  if ( ! empty( $all ) ) {
    foreach ( $all as $p ) {
      $pid = $p->ID;
      update_post_meta( $pid, '_regular_price', '500000' );
      update_post_meta( $pid, '_price', '500000' );
      delete_post_meta( $pid, '_sale_price' );
      // if WC CRUD available, update via WC product
      if ( function_exists( 'wc_get_product' ) ) {
        $wc = wc_get_product( $pid );
        if ( $wc ) {
          $wc->set_regular_price( '500000' );
          $wc->set_sale_price( '' );
          $wc->save();
        }
      }
    }
  }

  return array( 'created' => $created, 'updated' => $updated );
}

  /**
   * Create WooCommerce products from images in the portfolio folder.
   * Looks for optional JSON mapping at wp-content/themes/beslock-custom/assets/images/products.json
   * Returns array('created'=>N,'skipped'=>array()) or WP_Error.
   */
  function beslock_plugin_create_products_from_portfolio() {
    // Determine images dir
    $dir = get_stylesheet_directory() . '/assets/images/products';
    $fallback = WP_CONTENT_DIR . '/themes/beslock-custom/assets/images/products';
    if ( ! is_dir( $dir ) && is_dir( $fallback ) ) {
      $dir = $fallback;
    }

    // using top-level beslock_plugin_read_portfolio_products() and
    // beslock_plugin_sync_portfolio_products_and_set_price() implementations

    if ( ! is_dir( $dir ) ) {
      return new WP_Error( 'no_dir', __( 'Products images directory not found: ' . $dir, 'beslock' ) );
    }

    // optional mapping file
    $map_file = dirname( $dir ) . '/products.json';
    $map = array();
    if ( file_exists( $map_file ) ) {
      $json = file_get_contents( $map_file );
      $decoded = json_decode( $json, true );
      if ( is_array( $decoded ) ) {
        // map by slug
        foreach ( $decoded as $item ) {
          if ( ! empty( $item['slug'] ) ) {
            $map[ $item['slug'] ] = $item;
          }
        }
      }
    }

    $patterns = array( '/*.webp', '/*.png', '/*.jpg', '/*.jpeg', '/*.gif' );
    $files = array();
    foreach ( $patterns as $p ) {
      $found = glob( $dir . $p );
      if ( $found ) $files = array_merge( $files, $found );
    }
    if ( empty( $files ) ) {
      return array( 'created' => 0, 'skipped' => array() );
    }

    // helper to find attachment by filename
    global $wpdb;
    $created = 0;
    $skipped = array();

    foreach ( $files as $file ) {
      $filename = wp_basename( $file );
      $slug = sanitize_title( pathinfo( $filename, PATHINFO_FILENAME ) );

      // check if product exists by slug
      $existing = get_page_by_path( $slug, OBJECT, 'product' );
      if ( $existing ) {
        $skipped[] = $slug;
        continue;
      }

      // find attachment rows by filename (search postmeta)
      $like = '%' . $wpdb->esc_like( $filename ) . '%';
      $rows_att = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, pm.meta_value as file FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type='attachment' AND pm.meta_key='_wp_attached_file' AND pm.meta_value LIKE %s", $like ) );

      // if attachment not found, try to import it (use existing import routine) and query again
      if ( empty( $rows_att ) ) {
        $imp = beslock_plugin_do_import();
        $rows_att = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, pm.meta_value as file FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type='attachment' AND pm.meta_key='_wp_attached_file' AND pm.meta_value LIKE %s", $like ) );
      }

      // Build product post
      $title = isset( $map[ $slug ]['title'] ) ? $map[ $slug ]['title'] : ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
      $content = isset( $map[ $slug ]['description'] ) ? $map[ $slug ]['description'] : '';
      $post = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'product',
        'post_name'    => $slug,
      );

      $pid = wp_insert_post( $post );
      if ( is_wp_error( $pid ) || ! $pid ) {
        $skipped[] = $slug;
        continue;
      }

      // Ensure slug is exactly the sanitized one and clear caches so template mapping finds it
      wp_update_post( array( 'ID' => $pid, 'post_name' => $slug ) );
      if ( function_exists( 'clean_post_cache' ) ) {
        clean_post_cache( $pid );
      }

      // set featured image and gallery if any
      if ( ! empty( $rows_att ) ) {
        $choice = beslock_plugin_choose_featured_and_gallery( $rows_att );
        if ( $choice['featured'] ) {
          set_post_thumbnail( $pid, intval( $choice['featured'] ) );
        }
        if ( ! empty( $choice['gallery'] ) ) {
          update_post_meta( $pid, '_product_image_gallery', implode( ',', $choice['gallery'] ) );
        }
      }

      // set price if provided
      if ( isset( $map[ $slug ]['price'] ) ) {
        $price = sanitize_text_field( $map[ $slug ]['price'] );
        update_post_meta( $pid, '_regular_price', $price );
        update_post_meta( $pid, '_price', $price );
      }

      $created++;
    }

    return array( 'created' => $created, 'skipped' => $skipped );
  }

  /**
   * Orchestrator: import images, create/update products from portfolio template and set price.
   */
  function beslock_plugin_import_products() {
    // First, import all images from theme images folder to Media Library
    $imp = beslock_plugin_do_import();
    $imported_count = 0;
    if ( is_array( $imp ) && isset( $imp['count'] ) ) {
      $imported_count = intval( $imp['count'] );
    }

    // Then sync products and set price
    $sync = beslock_plugin_sync_portfolio_products_and_set_price();
    if ( is_wp_error( $sync ) ) {
      return $sync;
    }

    // Build and store a mapping of portfolio slug => product ID to help the theme template
    if ( ! is_wp_error( $sync ) ) {
      beslock_plugin_build_portfolio_mapping();
    }

    $mapping = get_option( 'beslock_portfolio_mapping', array() );
    return array(
      'created' => isset( $sync['created'] ) ? intval( $sync['created'] ) : 0,
      'updated' => isset( $sync['updated'] ) ? intval( $sync['updated'] ) : 0,
      'imported_images' => $imported_count,
      'mapping' => is_array( $mapping ) ? $mapping : array(),
    );
  }

  /**
   * Build mapping from portfolio product slug => WC product ID and save in option.
   */
  function beslock_plugin_build_portfolio_mapping() {
    $products = beslock_plugin_read_portfolio_products();
    if ( is_wp_error( $products ) || ! is_array( $products ) ) return false;
    $map = array();
    foreach ( $products as $item ) {
      $name = isset( $item['name'] ) ? $item['name'] : '';
      $slug = sanitize_title( $name );
      // try find by slug first
      $found = get_posts( array( 'post_type' => 'product', 'name' => $slug, 'posts_per_page' => 1 ) );
      if ( empty( $found ) ) {
        // fallback to search by title
        $found = get_posts( array( 'post_type' => 'product', 's' => $name, 'posts_per_page' => 1 ) );
      }
      if ( ! empty( $found ) ) {
        $map[ $slug ] = intval( $found[0]->ID );
      }
    }
    if ( ! empty( $map ) ) {
      update_option( 'beslock_portfolio_mapping', $map );
      return true;
    }
    return false;
  }
