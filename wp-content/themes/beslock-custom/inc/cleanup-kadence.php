<?php
/**
 * Cleanup Kadence theme output and aggressive CSS stripping helpers.
 */

/*
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
  if ( ob_get_level() ) {
    ob_end_flush();
  }
}, 9999 );
*/

function beslock_strip_kadence_css( $buffer ) {
  // Disabled: modifying full HTML output via regex is unsafe and broke rendering.
  // Keep as a no-op to avoid stripping content.
  return $buffer;
}

/*
add_action( 'after_setup_theme', function() {
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
        remove_action( $hook, $cb );
      } elseif ( is_array( $cb ) && count( $cb ) === 2 ) {
        remove_action( $hook, $cb[0], $cb[1] );
      }
    }
    // remove_all_actions( $hook );
  }

}, 30 );
*/

add_action( 'kadence_entry_archive_hero', 'beslock_kadence_archive_hero_buffer_start', 0 );
function beslock_kadence_archive_hero_buffer_start() {
  if ( ! function_exists( 'is_shop' ) ) {
    return;
  }
  ob_start();
}

add_action( 'kadence_entry_archive_hero', 'beslock_kadence_archive_hero_buffer_end', 9999 );
function beslock_kadence_archive_hero_buffer_end() {
  if ( ! function_exists( 'is_shop' ) ) {
    return;
  }
  $content = ( is_callable( 'ob_get_clean' ) ) ? ob_get_clean() : '';
  if ( is_shop() || is_product_category() || is_product_tag() ) {
    return;
  }
  echo $content;
}
