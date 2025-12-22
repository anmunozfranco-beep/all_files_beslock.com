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

// Enqueue a minimal CSS reset on WooCommerce pages so the child theme
// does not globally override Kadence/WooCommerce styles.
add_action( 'wp_enqueue_scripts', function() {
  if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
    $css_file = get_stylesheet_directory() . '/assets/css/wc-scope-fix.css';
    if ( file_exists( $css_file ) ) {
      wp_enqueue_style( 'beslock-wc-scope-fix', get_stylesheet_directory_uri() . '/assets/css/wc-scope-fix.css', [ 'beslock-main-style' ], filemtime( $css_file ) );
    }
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
