<?php
/**
 * Beslock Custom Theme – Functions
 * Mobile-first + BEM + GSAP Ready
 *
 * Actualizado: encolado seguro de assets del menú móvil (CSS + JS)
 */

add_action( 'wp_enqueue_scripts', function() {

  // If this theme is used as a child theme, ensure the Kadence parent stylesheet
  // is enqueued so the site inherits the parent's layout and e-commerce assets.
  if ( function_exists( 'is_child_theme' ) && is_child_theme() ) {
    wp_enqueue_style( 'kadence-parent-style', get_template_directory_uri() . '/style.css', [], null );
  }


  // Helper para versiones basadas en tiempo de modificación (si existe el archivo)
  $theme_dir_uri  = get_stylesheet_directory_uri();
  $theme_dir_path = get_stylesheet_directory();

  $ver_main_css = file_exists( $theme_dir_path . '/assets/css/main.css' )
    ? filemtime( $theme_dir_path . '/assets/css/main.css' )
    : null;

  /* -------------------------------
   * CSS PRINCIPAL
   * ------------------------------- */
  wp_enqueue_style(
    'beslock-main-style',
    $theme_dir_uri . '/assets/css/main.css',
    [],
    $ver_main_css
  );

  /* -------------------------------
   * Bootstrap Icons (CDN) - GLOBAL
   * Cargado de forma global en frontend para uso en header/menu/modales
   * Versión: 1.13.1 (sin SRI por flujo de desarrollo)
   * ------------------------------- */
  wp_enqueue_style(
    'beslock-bootstrap-icons',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css',
    [],
    '1.13.1'
  );

  /* -------------------------------
   * CSS ESPECÍFICO PARA MENÚ PRODUCTOS MÓVIL
   * Se cargará siempre para aplicar la versión móvil en todas las resoluciones
   * ------------------------------- */
  $menu_css_path = $theme_dir_path . '/assets/css/menu-products-mobile.css';
  $ver_menu_css = file_exists( $menu_css_path ) ? filemtime( $menu_css_path ) : null;

  wp_enqueue_style(
    'beslock-menu-products-mobile',
    $theme_dir_uri . '/assets/css/menu-products-mobile.css',
    [ 'beslock-main-style' ],
    $ver_menu_css
  );

  // CSS del nuevo componente models (mobile)
  $models_css_path = $theme_dir_path . '/assets/css/models-mobile.css';
  $ver_models_css = file_exists( $models_css_path ) ? filemtime( $models_css_path ) : null;

  wp_enqueue_style(
    'beslock-models-mobile',
    $theme_dir_uri . '/assets/css/models-mobile.css',
    [ 'beslock-main-style', 'beslock-menu-products-mobile' ],
    $ver_models_css
  );


  /* -------------------------------
   * GSAP + ScrollTrigger desde CDN
   * ------------------------------- */
  wp_enqueue_script(
    'gsap',
    'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
    [],
    null,
    true
  );
  wp_enqueue_script(
    'scrolltrigger',
    'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js',
    [ 'gsap' ],
    null,
    true
  );

  /* -------------------------------
   * JS PRINCIPAL DEL TEMA
   * ------------------------------- */
  $main_js_path = $theme_dir_path . '/assets/js/main.js';
  $ver_main_js = file_exists( $main_js_path ) ? filemtime( $main_js_path ) : null;

  wp_enqueue_script(
    'beslock-main-js',
    $theme_dir_uri . '/assets/js/main.js',
    [ 'scrolltrigger' ], // asegura carga en el orden correcto
    $ver_main_js,
    true
  );

  /* -------------------------------
   * JS ESPECÍFICO PARA MENÚ PRODUCTOS MÓVIL
   * Se encola siempre para que el drawer funcione en todas las resoluciones
   * ------------------------------- */
  $menu_js_path = $theme_dir_path . '/assets/js/menu-products-mobile.js';
  $ver_menu_js = file_exists( $menu_js_path ) ? filemtime( $menu_js_path ) : null;

  wp_enqueue_script(
    'beslock-menu-products-mobile-js',
    $theme_dir_uri . '/assets/js/menu-products-mobile.js',
    [ 'beslock-main-js' ],
    $ver_menu_js,
    true
  );

  // JS del nuevo componente models (manejo de toggle del panel Products)
  $models_js_path = $theme_dir_path . '/assets/js/models-mobile.js';
  $ver_models_js = file_exists( $models_js_path ) ? filemtime( $models_js_path ) : null;

  wp_enqueue_script(
    'beslock-models-mobile-js',
    $theme_dir_uri . '/assets/js/models-mobile.js',
    [ 'beslock-main-js', 'beslock-menu-products-mobile-js' ],
    $ver_models_js,
    true
  );

  // NOTE: Cross-fade system disabled.
  // We use the rotator implementation (product-rotator.css/js) with `.product-image-rotator` + `.product-frame`.
  // The previous cross-fade assets were targeting different class names and were causing layout drift.

  /* Product rotator (grid-based, opacity-only) */
  $product_rotator_css = $theme_dir_path . '/assets/css/product-rotator.css';
  if ( file_exists( $product_rotator_css ) ) {
    wp_enqueue_style( 'beslock-product-rotator', $theme_dir_uri . '/assets/css/product-rotator.css', [ 'beslock-main-style' ], filemtime( $product_rotator_css ) );
  }

  $product_rotator_js = $theme_dir_path . '/assets/js/product-rotator.js';
  if ( file_exists( $product_rotator_js ) ) {
    wp_enqueue_script( 'beslock-product-rotator-js', $theme_dir_uri . '/assets/js/product-rotator.js', [ 'beslock-main-js' ], filemtime( $product_rotator_js ), true );
  }

  /* -------------------------------
   * Header state script + CSS (BBC-like behavior)
   * Adds a minimal, reversible scroll-state controller for the header.
   * ------------------------------- */
  $header_state_js = $theme_dir_path . '/assets/js/header-state.js';
  $ver_header_state_js = file_exists( $header_state_js ) ? filemtime( $header_state_js ) : null;
  wp_enqueue_script(
    'beslock-header-state',
    $theme_dir_uri . '/assets/js/header-state.js',
    [],
    $ver_header_state_js,
    true
  );

  $header_state_css = $theme_dir_path . '/assets/css/header-state.css';
  if ( file_exists( $header_state_css ) ) {
    wp_enqueue_style( 'beslock-header-state-css', $theme_dir_uri . '/assets/css/header-state.css', [ 'beslock-main-style' ], filemtime( $header_state_css ) );
  }

});

/**
 * Declare WooCommerce support for the child theme if not already present.
 * Using after_setup_theme with priority > 10 helps run after the parent theme
 * so we don't accidentally override parent theme setup.
 */
add_action( 'after_setup_theme', function() {
  if ( ! current_theme_supports( 'woocommerce' ) ) {
    add_theme_support( 'woocommerce' );
  }
}, 11 );

  /**
   * Redirect the WooCommerce Shop page to the front-page products portfolio section.
   * This makes the homepage portfolio act as the shop landing.
   */
  add_action( 'template_redirect', function() {
    if ( function_exists( 'is_shop' ) && is_shop() ) {
      // Use a 301 redirect to the homepage anchor where the products portfolio is located
      wp_safe_redirect( home_url( '/' ) . '#productos', 301 );
      exit;
    }
  }, 5 );

  /**
   * For safety, ensure WooCommerce canonical URLs that expect a shop page don't break.
   * When WooCommerce queries for the shop page, prefer the home URL so internal links remain valid.
   */
  add_filter( 'woocommerce_get_shop_page_id', function( $page_id ) {
    // return 0 to indicate no special shop page; redirect handles UX
    return 0;
  } );

  /**
   * Admin Tools page: Import portfolio images into Media Library.
   */
  add_action( 'admin_menu', function() {
    add_management_page(
      __( 'Import Portfolio Images', 'beslock' ),
      __( 'Import Portfolio Images', 'beslock' ),
      'manage_options',
      'beslock-import-portfolio',
      'beslock_import_portfolio_page'
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
    // Add combined import+assign button
    echo '<form method="post">' . wp_nonce_field( 'beslock_import_images_nonce' );
    echo '<p>' . esc_html__( 'This will import all images from the theme folder', 'beslock' ) . ': <code>wp-content/themes/beslock-custom/assets/images/</code></p>';
    echo '<p><button type="submit" name="beslock_import_images" class="button button-primary">' . esc_html__( 'Import images', 'beslock' ) . '</button></p>';
    echo '<p><button type="submit" name="beslock_assign_images" class="button">' . esc_html__( 'Assign images to products', 'beslock' ) . '</button></p>';
    echo '<p><button type="submit" name="beslock_import_and_assign" class="button button-primary">' . esc_html__( 'Import and assign images', 'beslock' ) . '</button></p>';
    echo '<p><button type="submit" name="beslock_set_all_price" class="button button-secondary" onclick="return confirm(\'' . esc_js( __( 'Set price 500000 for ALL products? This cannot be undone easily.', 'beslock' ) ) . '\');">' . esc_html__( 'Set all products price (500000)', 'beslock' ) . '</button></p>';
    echo '</form></div>';
  }

    // Handle combined action if submitted (separate nonce)
    if ( isset( $_POST['beslock_import_and_assign'] ) ) {
      check_admin_referer( 'beslock_import_images_nonce' );
      $result = beslock_import_and_assign_portfolio_images();
      if ( is_wp_error( $result ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
      } else {
        // $result contains import_count, import_ids, assigned, skipped
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

      // Check if an attachment with this filename already exists (search by meta _wp_attached_file)
      $existing = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'meta_query' => array(
          array( 'key' => '_wp_attached_file', 'value' => $filename, 'compare' => 'LIKE' ),
        ),
      ) );
      if ( ! empty( $existing ) ) {
        // skip existing
        $imported[] = $existing[0]->ID;
        continue;
      }

      // Copy file into uploads
      $unique = wp_unique_filename( $upload_dir['path'], $filename );
      $new_path = trailingslashit( $upload_dir['path'] ) . $unique;
      if ( ! copy( $file, $new_path ) ) {
        // skip on failure
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

  /**
   * Combined import + assign helper for admin and WP-CLI.
   * Returns array: import_count, import_ids, assigned, skipped
   */
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

  /**
   * Set all products regular price to given amount (string/number).
   * Returns array('updated' => N) or WP_Error.
   */
  function beslock_set_all_products_price( $amount ) {
    if ( ! $amount || ! is_scalar( $amount ) ) {
      return new WP_Error( 'invalid_amount', __( 'Invalid amount', 'beslock' ) );
    }
    // Normalize to decimal string with two places (WooCommerce expects dot decimal)
    $amount_float = floatval( str_replace( ',', '.', (string) $amount ) );
    $amount = number_format( $amount_float, 2, '.', '' );

    $products = get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'post_status' => array( 'publish', 'private', 'draft' ) ) );
    if ( empty( $products ) ) {
      return new WP_Error( 'no_products', __( 'No products found', 'beslock' ) );
    }

    $updated = 0;
    foreach ( $products as $p ) {
      $pid = $p->ID;
      // Use WC CRUD when available to keep caches consistent
      if ( function_exists( 'wc_get_product' ) ) {
        $wc = wc_get_product( $pid );
        if ( $wc ) {
          if ( $wc->is_type( 'variable' ) ) {
            // Update each variation explicitly
            $child_ids = $wc->get_children();
            foreach ( $child_ids as $cid ) {
              $vc = wc_get_product( $cid );
              if ( $vc ) {
                $vc->set_regular_price( $amount );
                $vc->set_sale_price( '' );
                // Save CRUD and also ensure meta keys are written
                $vc->save();
                update_post_meta( $cid, '_regular_price', $amount );
                update_post_meta( $cid, '_price', $amount );
                delete_post_meta( $cid, '_sale_price' );
              }
            }
            // For the parent variable product, set price meta to the same amount
            $wc->set_regular_price( $amount );
            $wc->save();
            update_post_meta( $pid, '_regular_price', $amount );
            update_post_meta( $pid, '_price', $amount );
            delete_post_meta( $pid, '_sale_price' );
          } else {
            // Simple / grouped / external: set regular price
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

      // Fallback if WC not available or wc_get_product failed
      update_post_meta( $pid, '_regular_price', $amount );
      update_post_meta( $pid, '_price', $amount );
      delete_post_meta( $pid, '_sale_price' );
      $updated++;
    }

    return array( 'updated' => $updated );
  }

  /**
   * Assign imported images to products:
   * - Finds attachments whose filename contains the product slug
   * - Sets the first match as featured image, others as product gallery
   */
  function beslock_assign_images_to_products() {
    // Normalize string: lower, transliterate accents, replace non-alnum with hyphen
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
        // Replace any non-alphanumeric with a dash
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

      // Prefer slug for matching, fall back to normalized title
      $match_key = $slug ? $slug : $title;
      $norm_key = beslock_normalize_string( $match_key );
      if ( ! $norm_key ) {
        $skipped[] = $p->post_title . '(no-slug)';
        continue;
      }

      // First, query candidate attachments by a conservative LIKE using raw slug/title
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

      // Fallback: if no matches found, try a broader scan (all attachments up to a reasonable limit)
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

// Enqueue a minimal CSS reset for WooCommerce pages. The stylesheet is scoped
// to `body.woocommerce` selectors so it's safe to include globally — this
// ensures the shop/cart/checkout pages receive the intended header fixes.
add_action( 'wp_enqueue_scripts', function() {
  $css_file = get_stylesheet_directory() . '/assets/css/wc-scope-fix.css';
  if ( file_exists( $css_file ) ) {
    wp_enqueue_style( 'beslock-wc-scope-fix', get_stylesheet_directory_uri() . '/assets/css/wc-scope-fix.css', [ 'beslock-main-style' ], filemtime( $css_file ) );
  }
}, 20 );

/**
 * Optionally dequeue Kadence styles so the child theme has full visual control.
 * This removes styles whose handle begins with "kadence-" but leaves all scripts
 * and template functionality intact. Run late (priority 100) so it can catch
 * styles enqueued by the parent and other components.
 */
add_action( 'wp_enqueue_scripts', function() {
  // Only run on the front-end.
  if ( is_admin() ) {
    return;
  }

  // Deregister any style handles that start with 'kadence-'.
  global $wp_styles;
  if ( empty( $wp_styles ) || empty( $wp_styles->registered ) ) {
    return;
  }

  foreach ( $wp_styles->registered as $handle => $data ) {
    if ( strpos( $handle, 'kadence' ) === 0 ) {
      wp_dequeue_style( $handle );
      wp_deregister_style( $handle );
    }
  }

  // Also ensure any parent style explicitly enqueued under 'kadence-parent-style' is removed.
  if ( wp_style_is( 'kadence-parent-style', 'enqueued' ) || wp_style_is( 'kadence-parent-style', 'registered' ) ) {
    wp_dequeue_style( 'kadence-parent-style' );
    wp_deregister_style( 'kadence-parent-style' );
  }

  // Additional aggressive removals: WordPress global/block styles and classic theme styles
  $extra_handles = [ 'global-styles', 'classic-theme-styles', 'wp-block-library-theme', 'wp-block-library' ];
  foreach ( $extra_handles as $h ) {
    if ( wp_style_is( $h, 'enqueued' ) || wp_style_is( $h, 'registered' ) ) {
      wp_dequeue_style( $h );
      wp_deregister_style( $h );
    }
  }

}, 100 );

/**
 * Remove Kadence header rendering hooks so the child theme header is the only one used.
 * Runs on after_setup_theme with slightly higher priority so parent registrations exist.
 */
add_action( 'after_setup_theme', function() {
  // List of Kadence hooks and callbacks to remove. This is aggressive by design.
  $removals = [
    'kadence_header' => [ 'Kadence\\header_markup' ],
    'kadence_top_header' => [ 'Kadence\\top_header' ],
    'kadence_main_header' => [ 'Kadence\\main_header' ],
    'kadence_bottom_header' => [ 'Kadence\\bottom_header' ],
    'kadence_mobile_header' => [ 'Kadence\\mobile_header' ],
    'kadence_mobile_top_header' => [ 'Kadence\\mobile_top_header' ],
    'kadence_mobile_main_header' => [ 'Kadence\\mobile_main_header' ],
    'kadence_mobile_bottom_header' => [ 'Kadence\\mobile_bottom_header' ],
    'kadence_header_html' => [ 'Kadence\\header_html' ],
    'kadence_header_button' => [ 'Kadence\\header_button' ],
    'kadence_header_cart' => [ 'Kadence\\header_cart' ],
    'kadence_header_social' => [ 'Kadence\\header_social' ],
    'kadence_header_search' => [ 'Kadence\\header_search' ],
    'kadence_site_branding' => [ 'Kadence\\site_branding' ],
    'kadence_primary_navigation' => [ 'Kadence\\primary_navigation' ],
    'kadence_secondary_navigation' => [ 'Kadence\\secondary_navigation' ],
    'kadence_mobile_site_branding' => [ 'Kadence\\mobile_site_branding' ],
    'kadence_navigation_popup_toggle' => [ 'Kadence\\navigation_popup_toggle' ],
    'kadence_mobile_navigation' => [ 'Kadence\\mobile_navigation' ],
    'kadence_mobile_html' => [ 'Kadence\\mobile_html' ],
    'kadence_mobile_button' => [ 'Kadence\\mobile_button' ],
    'kadence_mobile_cart' => [ 'Kadence\\mobile_cart' ],
  ];

  foreach ( $removals as $hook => $callbacks ) {
    foreach ( $callbacks as $cb ) {
      if ( is_string( $cb ) ) {
        // Namespaced function remove format: 'Kadence\\function_name'
        remove_action( $hook, $cb );
      } elseif ( is_array( $cb ) && count( $cb ) === 2 ) {
        remove_action( $hook, $cb[0], $cb[1] );
      }
    }
    // Also remove any remaining callbacks attached to these hooks as a last resort.
    remove_all_actions( $hook );
  }

}, 30 );

/**
 * DEBUG: Volcar estilos encolados a un archivo para diagnóstico local.
 * Se ejecuta en `wp_print_styles` para capturar lo encolado antes de imprimir.
 * El archivo se crea en `wp-content/themes/beslock-custom/debug/enqueued-styles.log`.
 */
add_action( 'wp_print_styles', function() {
  if ( is_admin() ) {
    return;
  }
  global $wp_styles;
  if ( empty( $wp_styles ) ) {
    return;
  }
  $out = [];
  // queued handles order
  $out[] = "=== QUEUE ===";
  if ( ! empty( $wp_styles->queue ) ) {
    foreach ( $wp_styles->queue as $h ) {
      $r = isset( $wp_styles->registered[ $h ] ) ? $wp_styles->registered[ $h ] : null;
      $src = $r && ! empty( $r->src ) ? $r->src : 'inline/unknown';
      $out[] = "$h => $src";
    }
  }
  $out[] = "\n=== REGISTERED (selected) ===";
  foreach ( $wp_styles->registered as $handle => $obj ) {
    $src = isset( $obj->src ) && $obj->src ? $obj->src : 'inline/unknown';
    $out[] = "$handle => $src";
  }

  $dir = get_stylesheet_directory() . '/debug';
  if ( ! file_exists( $dir ) ) {
    wp_mkdir_p( $dir );
  }
  $file = $dir . '/enqueued-styles.log';
  @file_put_contents( $file, implode( "\n", $out ) );
}, 100 );

/**
 * Buffer `wp_head` output and strip Kadence CSS link/style blocks.
 * This is aggressive: it removes any <link> or <style> node that references
 * "kadence" so the child theme CSS is the visual source of truth.
 */
add_action( 'wp_head', function() {
  if ( is_admin() ) {
    return;
  }
  ob_start( 'beslock_strip_kadence_css' );
}, 1 );

add_action( 'wp_head', function() {
  if ( is_admin() ) {
    return;
  }
  // Flush buffer late so all wp_head output is captured.
  if ( ob_get_level() ) {
    ob_end_flush();
  }
}, 9999 );

function beslock_strip_kadence_css( $buffer ) {
  // Remove <link ... href="...kadence...css" ...> or links to the Kadence theme folder
  $buffer = preg_replace( '#<link[^>]+href=(?:"|\')[^"\']*/themes/[^/]*kadence[^"\']*(?:"|\')[^>]*>#i', '', $buffer );
  $buffer = preg_replace( '#<link[^>]+href=(?:"|\')[^"\']*kadence[^"\']*(?:"|\')[^>]*>#i', '', $buffer );
  // Remove <style id="...kadence...">...</style>
  $buffer = preg_replace( '#<style[^>]*id=(?:"|\')[^"\']*kadence[^"\']*(?:"|\')[^>]*>.*?</style>#is', '', $buffer );
  // Remove any inline <style> blocks that contain the comment or marker 'kadence' inside
  $buffer = preg_replace( '#<style[^>]*>[^<]*kadence[^<]*</style>#is', '', $buffer );
  // Additionally remove any linked CSS that points at the parent theme folder explicitly
  $buffer = preg_replace( '#<link[^>]+href=(?:"|\')[^"\']*/wp-content/themes/kadence/[^"\']*(?:"|\')[^>]*>#i', '', $buffer );
    // --- NEW: remove WordPress/global/block inline style blocks and font blocks ---
    // Remove explicit global styles inline block
    $buffer = preg_replace( '#<style[^>]*id=(?:"|\')global-styles-inline-css(?:"|\')[^>]*>.*?</style>#is', '', $buffer );
    // Remove any <style id="...-inline-css"> blocks (wp-block-* inline styles, block editor inline CSS, etc.)
    $buffer = preg_replace( '#<style[^>]*id=(?:"|\')[^"\']+-inline-css(?:"|\')[^>]*>.*?</style>#is', '', $buffer );
    // Remove wp-block heading/separator inline styles and other block inline styles
    $buffer = preg_replace( '#<style[^>]*id=(?:"|\')wp-block[^"\']*(?:"|\')[^>]*>.*?</style>#is', '', $buffer );
    // Remove local font blocks injected by core/plugins (class 'wp-fonts-local' or similar)
    $buffer = preg_replace( '#<style[^>]*class=(?:"|\')?wp-fonts-local(?:"|\')?[^>]*>.*?</style>#is', '', $buffer );
    // Remove any remaining style tags that declare CSS variables from theme.json (very aggressive)
    $buffer = preg_replace( '#<style[^>]*>\s*:root\s*\{.*?--wp--preset--.*?</style>#is', '', $buffer );
  return $buffer;
}

// NOTE: aggressive final buffer removed — it caused child theme styles to be stripped.
// If needed, we'll implement a safer, targeted removal for specific handles only.

/**
 * Beslock: disable Kadence Page Title / Hero for WooCommerce archives (Shop, product category/tag).
 *
 * Rationale:
 * - Kadence renders the archive "hero" via the template part `template-parts/content/archive_hero.php`,
 *   which fires the action `kadence_entry_archive_hero` to output the title/hero markup.
 * - Removing parent theme hooks is fragile (class methods, unknown priorities). Instead we
 *   capture all output for that action using an output buffer and conditionally discard it
 *   when the request is the shop or a product taxonomy archive.
 * - This approach keeps Kadence behavior intact elsewhere (pages, single posts, other archives)
 *   and does not rely on CSS hiding.
 *
 * Implementation details:
 * - Start buffering at very low priority (0) and end buffering at very high priority (9999)
 *   so we capture everything rendered by the action during the request.
 * - For `is_shop()`, `is_product_category()` and `is_product_tag()` we silently drop the buffer,
 *   effectively preventing the hero from being rendered.
 *
 * Notes:
 * - Prefixed with `beslock_` per project conventions.
 */
add_action( 'kadence_entry_archive_hero', 'beslock_kadence_archive_hero_buffer_start', 0 );
function beslock_kadence_archive_hero_buffer_start() {
  if ( ! function_exists( 'is_shop' ) ) {
    return;
  }
  // Start output buffering to capture all hero output.
  ob_start();
}

add_action( 'kadence_entry_archive_hero', 'beslock_kadence_archive_hero_buffer_end', 9999 );
function beslock_kadence_archive_hero_buffer_end() {
  if ( ! function_exists( 'is_shop' ) ) {
    return;
  }
  $content = ( is_callable( 'ob_get_clean' ) ) ? ob_get_clean() : '';
  // If this is the shop page or product taxonomy archive, drop the hero output.
  if ( is_shop() || is_product_category() || is_product_tag() ) {
    // Do nothing (buffer discarded) — hero removed for these contexts.
    return;
  }
  // Otherwise, echo the captured content so parent theme output is preserved.
  echo $content;
}

  /**
   * Render basic product page sections for theme scaffold hooks.
   * These provide safe, minimal outputs and fallbacks when product meta is missing.
   */
  add_action( 'beslock_product_trust_badges', function() {
    global $product;
    if ( ! $product ) {
      return;
    }
    $name = $product->get_name();
    $badges = array();
    $candidates = array( 'e-Orbit', 'e-Flex', 'e-Prime', 'e-Shield' );
    foreach ( $candidates as $c ) {
      if ( stripos( $name, $c ) !== false ) {
        $badges[] = $c;
      }
    }
    if ( empty( $badges ) ) {
      return;
    }
    echo '<div class="beslock-trust-badges">';
    foreach ( $badges as $b ) {
      $slug = sanitize_title( $b );
      $src = get_stylesheet_directory_uri() . '/assets/images/instal.png';
      echo '<span class="beslock-badge beslock-badge--' . esc_attr( $slug ) . '"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $b ) . '"/></span>';
    }
    echo '</div>';
  }, 10 );

  add_action( 'beslock_product_confianza', function() {
    global $product;
    if ( ! $product ) {
      return;
    }
    echo '<div class="beslock-confianza">';
    echo '<h3>Confianza</h3>';
    echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet.</p>';
    echo '<p>Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa.</p>';
    echo '</div>';
  } );

  add_action( 'beslock_product_psb', function() {
    global $product, $post;
    if ( ! $product ) {
      return;
    }
    echo '<div class="beslock-psb">';
    echo '<div class="psb-col"><h4>Problema</h4><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.</p></div>';
    echo '<div class="psb-col"><h4>Solución</h4><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra.</p></div>';
    echo '<div class="psb-col"><h4>Beneficios</h4><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes.</p></div>';
    echo '</div>';
  } );

  add_action( 'beslock_product_specs', function() {
    global $product;
    if ( ! $product ) {
      return;
    }
    echo '<div class="beslock-specs">';
    echo '<h3>Especificaciones técnicas</h3>';
    echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>';
    if ( function_exists( 'wc_display_product_attributes' ) ) {
      echo '<div class="beslock-specs__attrs">';
      echo wc_display_product_attributes( $product );
      echo '</div>';
    }
    echo '</div>';
  } );

  add_action( 'beslock_product_demo', function() {
    global $product, $post;
    if ( ! $product ) {
      return;
    }
    $embed = get_post_meta( $post->ID, 'beslock_demo_embed', true );
    echo '<div class="beslock-demo">';
    echo '<h3>Demo / Uso</h3>';
    if ( $embed ) {
      echo '<div class="beslock-demo__embed">' . wp_kses_post( $embed ) . '</div>';
    } else {
      echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>';
    }
    echo '</div>';
  } );

  add_action( 'beslock_product_who', function() {
    echo '<div class="beslock-who"><h3>Para quién es</h3><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh.</p></div>';
  } );

  add_action( 'beslock_product_faq', function() {
    echo '<div class="beslock-faq"><h3>Preguntas frecuentes</h3><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante. Donec id elit non mi porta gravida at eget metus.</p></div>';
  } );

  add_action( 'beslock_product_cta', function() {
    global $product;
    if ( ! $product ) {
      return;
    }
    echo '<div class="beslock-cta">';
    echo '<div class="beslock-cta__price">' . $product->get_price_html() . '</div>';
    // Use WC template for add-to-cart to preserve variations handling
    if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
      echo '<div class="beslock-cta__buy">';
      woocommerce_template_single_add_to_cart();
      echo '</div>';
    } else {
      echo '<a class="button" href="' . esc_url( get_permalink( $product->get_id() ) ) . '">Comprar</a>';
    }
    echo '</div>';
  } );

