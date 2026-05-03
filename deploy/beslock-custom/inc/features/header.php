<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/features/header.php' );

if ( ! function_exists( 'beslock_get_header_widget_html' ) ) {
  function beslock_get_header_widget_html() {
    $tpl = get_stylesheet_directory() . '/template-parts/header/header-widget.php';
    if ( file_exists( $tpl ) ) {
      ob_start();
      include $tpl;
      return ob_get_clean();
    }
    return '';
  }
}

if ( ! function_exists( 'beslock_header_widget_shortcode' ) ) {
  function beslock_header_widget_shortcode( $atts = array() ) {
    return beslock_get_header_widget_html();
  }
  add_shortcode( 'beslock_header_widget', 'beslock_header_widget_shortcode' );
}

if ( ! function_exists( 'beslock_render_header_widget' ) ) {
  function beslock_render_header_widget() {
    echo beslock_get_header_widget_html();
  }
}
