<?php
/**
 * Admin utility pages and import/assignment helpers.
 */

error_log( 'Loaded OK: inc/admin-tools.php' );

if ( is_admin() ) {

  add_action( 'admin_menu', function() {
  add_management_page(
    __( 'Import Portfolio Images', 'beslock' ),
    __( 'Import Portfolio Images', 'beslock' ),
    'manage_options',
    'beslock-import-portfolio',
    'beslock_import_portfolio_page'
  );
  } );

  // Register CSV Generator page
  add_action( 'admin_menu', function() {
    add_management_page(
      __( 'CSV Generator', 'beslock' ),
      __( 'CSV Generator', 'beslock' ),
      'manage_options',
      'beslock-csv-generator',
      'beslock_csv_generator_page'
    );
  } );

function beslock_import_portfolio_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions', 'beslock' ) );
  }

  echo '<div class="wrap"><h1>' . esc_html__( 'Import Portfolio Images', 'beslock' ) . '</h1>';

  if ( isset( $_POST['beslock_import_images'] ) ) {
    check_admin_referer( 'beslock_import_images_nonce' );
    $result = beslock_import_portfolio_images();
    if ( is_wp_error( $result ) ) {
      echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
    } else {
      printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( sprintf( _n( 'Imported %d image.', 'Imported %d images.', $result['count'], 'beslock' ), $result['count'] ) ) );
      if ( ! empty( $result['ids'] ) ) {
        echo '<p>' . esc_html__( 'Attachment IDs:', 'beslock' ) . ' ' . esc_html( implode( ', ', $result['ids'] ) ) . '</p>';
      }
    }

    if ( isset( $_POST['beslock_assign_images'] ) ) {
      check_admin_referer( 'beslock_assign_images_nonce' );
      $assign = beslock_assign_images_to_products();
      if ( is_wp_error( $assign ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html( $assign->get_error_message() ) . '</p></div>';
      } else {
        printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( sprintf( _n( 'Assigned images for %d product.', 'Assigned images for %d products.', $assign['assigned'], 'beslock' ), $assign['assigned'] ) ) );
        if ( ! empty( $assign['skipped'] ) ) {
          echo '<p>' . esc_html__( 'Skipped products (no matching images):', 'beslock' ) . ' ' . esc_html( implode( ', ', $assign['skipped'] ) ) . '</p>';
        }
      }
    }
  }
  echo '<form method="post">' . wp_nonce_field( 'beslock_import_images_nonce' );
  echo '<p>' . esc_html__( 'This will import all images from the theme folder', 'beslock' ) . ': <code>wp-content/themes/beslock-custom/assets/images/</code></p>';
  echo '<p><button type="submit" name="beslock_import_images" class="button button-primary">' . esc_html__( 'Import images', 'beslock' ) . '</button></p>';
  echo '<p><button type="submit" name="beslock_assign_images" class="button">' . esc_html__( 'Assign images to products', 'beslock' ) . '</button></p>';
  echo '<p><button type="submit" name="beslock_import_and_assign" class="button button-primary">' . esc_html__( 'Import and assign images', 'beslock' ) . '</button></p>';
  echo '<p><button type="submit" name="beslock_set_all_price" class="button button-secondary" onclick="return confirm(\'' . esc_js( __( 'Set price 500000 for ALL products? This cannot be undone easily.', 'beslock' ) ) . '\');">' . esc_html__( 'Set all products price (500000)', 'beslock' ) . '</button></p>';
  echo '</form></div>';
}

function beslock_csv_generator_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions', 'beslock' ) );
  }
  $script = get_stylesheet_directory() . '/scripts/CSV_portfolio_generator.php';
  if ( ! file_exists( $script ) ) {
    echo '<div class="wrap"><h1>' . esc_html__( 'CSV Generator', 'beslock' ) . '</h1>';
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Script not found: scripts/CSV_portfolio_generator.php', 'beslock' ) . '</p></div>';
    echo '</div>';
    return;
  }
  include $script;
  if ( function_exists( 'beslock_csv_portfolio_admin_ui' ) ) {
    beslock_csv_portfolio_admin_ui();
  } else {
    echo '<div class="wrap"><h1>' . esc_html__( 'CSV Generator', 'beslock' ) . '</h1>';
    echo '<div class="notice notice-error"><p>' . esc_html__( 'CSV functions not available.', 'beslock' ) . '</p></div>';
    echo '</div>';
  }
}

  // Handle combined action if submitted
  if ( isset( $_POST['beslock_import_and_assign'] ) ) {
    check_admin_referer( 'beslock_import_images_nonce' );
    $result = beslock_import_and_assign_portfolio_images();
    if ( is_wp_error( $result ) ) {
      echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
    } else {
      printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( sprintf( _n( 'Imported and assigned for %d image/product.', 'Imported and assigned for %d images/products.', $result['import_count'], 'beslock' ), $result['import_count'] ) ) );
      if ( isset( $result['import_ids'] ) && ! empty( $result['import_ids'] ) ) {
        echo '<p>' . esc_html__( 'Attachment IDs:', 'beslock' ) . ' ' . esc_html( implode( ', ', $result['import_ids'] ) ) . '</p>';
      }
      if ( isset( $result['assigned'] ) ) {
        printf( '<p>' . esc_html__( 'Assigned images for %d products.', 'beslock' ) . '</p>', intval( $result['assigned'] ) );
      }
      if ( ! empty( $result['skipped'] ) ) {
        echo '<p>' . esc_html__( 'Skipped products (no matching images):', 'beslock' ) . ' ' . esc_html( implode( ', ', $result['skipped'] ) ) . '</p>';
      }
    }
  }

  // Handle setting price for all products to a fixed amount (500000)
  if ( isset( $_POST['beslock_set_all_price'] ) ) {
    check_admin_referer( 'beslock_import_images_nonce' );
    $amount = '500000';
    $res = beslock_set_all_products_price( $amount );
    if ( is_wp_error( $res ) ) {
      echo '<div class="notice notice-error"><p>' . esc_html( $res->get_error_message() ) . '</p></div>';
    } else {
      printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( sprintf( __( 'Updated price for %d products to %s', 'beslock' ), intval( $res['updated'] ), $amount ) ) );
    }
  }

} // end if is_admin()

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

add_action( 'admin_menu', function() {
  add_management_page(
    __( 'Fix Placeholder Images', 'beslock' ),
    __( 'Fix placeholders', 'beslock' ),
    'manage_options',
    'beslock-fix-placeholders',
    'beslock_fix_placeholders_page'
  );
} );

// Admin notice with direct link to CSV Generator (help if menu missing)
add_action( 'admin_notices', function() {
  if ( ! current_user_can( 'manage_options' ) ) return;
  $url = admin_url( 'tools.php?page=beslock-csv-generator' );
  echo '<div class="notice notice-info is-dismissible"><p>' . sprintf( esc_html__( 'CSV Generator available: %sOpen CSV Generator%s', 'beslock' ), '<a href="' . esc_url( $url ) . '">', '</a>' ) . '</p></div>';
} );

function beslock_fix_placeholders_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions', 'beslock' ) );
  }

  $script = get_stylesheet_directory() . '/scripts/fix-placeholder-images.php';
  if ( ! file_exists( $script ) ) {
    echo '<div class="wrap"><h1>' . esc_html__( 'Fix Placeholder Images', 'beslock' ) . '</h1>';
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Script not found: scripts/fix-placeholder-images.php', 'beslock' ) . '</p></div>';
    echo '</div>';
    return;
  }

  $applied = false;
  $output = '';

  if ( isset( $_POST['beslock_fix_apply'] ) ) {
    check_admin_referer( 'beslock_fix_placeholders_nonce' );
    $GLOBALS['argv'] = array();
    ob_start();
    include $script;
    $output = ob_get_clean();
    $applied = true;
  } else {
    $GLOBALS['argv'] = array('--dry-run');
    ob_start();
    include $script;
    $output = ob_get_clean();
  }

  $placeholder_pattern = '/lupa|magnif|magnifier|magnifying|search|lens|placeholder|🔍|\/assets\/images\/products\/[\w\-\.]+/i';
  $found_pages = array();
  $products = get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => array('publish','draft','private') ) );
  if ( ! empty( $products ) ) {
    foreach ( $products as $pp ) {
      $url = get_permalink( $pp->ID );
      if ( ! $url ) continue;
      $resp = wp_remote_get( $url, array( 'timeout' => 5 ) );
      if ( is_wp_error( $resp ) ) continue;
      $body = wp_remote_retrieve_body( $resp );
      if ( $body && preg_match( $placeholder_pattern, $body, $m ) ) {
        $found_pages[ $pp->ID ] = array( 'permalink' => $url, 'match' => $m[0] );
      }
    }
  }

  echo '<div class="wrap"><h1>' . esc_html__( 'Fix Placeholder Images', 'beslock' ) . '</h1>';
  if ( $applied ) {
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Apply executed. Review output below and backup file in uploads/beslock-backups/', 'beslock' ) . '</p></div>';
  } else {
    echo '<p>' . esc_html__( 'Dry-run output shown below. If the results look safe, click Apply to perform the changes (a backup file will be written by default).', 'beslock' ) . '</p>';
  }

  echo '<form method="post">' . wp_nonce_field( 'beslock_fix_placeholders_nonce' );
  echo '<p><button type="submit" name="beslock_fix_apply" class="button button-primary" onclick="return confirm(\'' . esc_js( __( 'Apply replacements? This will modify product attachment meta. A backup file is created by default.', 'beslock' ) ) . '\');">' . esc_html__( 'Apply replacements', 'beslock' ) . '</button></p>';
  echo '<h2>' . esc_html__( 'Script output', 'beslock' ) . '</h2>';
  echo '<pre style="white-space:pre-wrap;background:#fff;border:1px solid #ddd;padding:12px;">' . esc_html( $output ) . '</pre>';
  echo '</form>';
  echo '</div>';
}

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

function beslock_assign_images_to_products() {
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
