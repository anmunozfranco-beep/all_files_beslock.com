<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/admin/tools.php' );

// Admin menu pages and wiring. Importer helper functions live in importer.php
if ( ! function_exists( 'beslock_admin_tools_menu' ) ) {
  function beslock_admin_tools_menu() {
    add_management_page(
      __( 'Import Portfolio Images', 'beslock' ),
      __( 'Import Portfolio Images', 'beslock' ),
      'manage_options',
      'beslock-import-portfolio',
      'beslock_import_portfolio_page'
    );

    add_management_page(
      __( 'Fix Placeholder Images', 'beslock' ),
      __( 'Fix placeholders', 'beslock' ),
      'manage_options',
      'beslock-fix-placeholders',
      'beslock_fix_placeholders_page'
    );

    // New importer admin page: Cargar Portfolio Data (reads data/products.json)
    add_management_page(
      __( 'Cargar Portfolio Data', 'beslock' ),
      __( 'Cargar Portfolio Data', 'beslock' ),
      'manage_options',
      'beslock-carga-portfolio',
      'beslock_carga_portfolio_page'
    );

    // CSV generator page
    add_management_page(
      __( 'CSV Generator', 'beslock' ),
      __( 'CSV Generator', 'beslock' ),
      'manage_options',
      'beslock-csv-generator',
      'beslock_csv_generator_page'
    );
  }
  add_action( 'admin_menu', 'beslock_admin_tools_menu' );
}

if ( ! function_exists( 'beslock_import_portfolio_page' ) ) {
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
}

if ( ! function_exists( 'beslock_fix_placeholders_page' ) ) {
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
}

// Admin page wrapper for carga_portfolio_data importer
if ( ! function_exists( 'beslock_carga_portfolio_page' ) ) {
  function beslock_carga_portfolio_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( __( 'Insufficient permissions', 'beslock' ) );
    }

    $script = get_stylesheet_directory() . '/scripts/carga_portfolio_data.php';
    if ( ! file_exists( $script ) ) {
      echo '<div class="wrap"><h1>' . esc_html__( 'Cargar Portfolio Data', 'beslock' ) . '</h1>';
      echo '<div class="notice notice-error"><p>' . esc_html__( 'Importer script not found: scripts/carga_portfolio_data.php', 'beslock' ) . '</p></div>';
      echo '</div>';
      return;
    }

    // Include the script which defines UI and processing functions
    include $script;
    // Render the included admin UI helper
    if ( function_exists( 'beslock_carga_portfolio_admin_ui' ) ) {
      beslock_carga_portfolio_admin_ui();
    } else {
      echo '<div class="wrap"><h1>' . esc_html__( 'Cargar Portfolio Data', 'beslock' ) . '</h1>';
      echo '<div class="notice notice-error"><p>' . esc_html__( 'Importer functions not available.', 'beslock' ) . '</p></div>';
      echo '</div>';
    }
  }
}

if ( ! function_exists( 'beslock_csv_generator_page' ) ) {
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
}
