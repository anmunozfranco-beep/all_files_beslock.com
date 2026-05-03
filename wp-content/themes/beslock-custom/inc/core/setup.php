<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

error_log( 'Loaded OK: inc/core/setup.php' );

// General theme setup hooks (non-WooCommerce). Keep minimal to avoid behavioral changes.
if ( ! function_exists( 'beslock_core_setup' ) ) {
  function beslock_core_setup() {
    // reserved for theme setup actions (menus, image sizes, textdomain, etc.)
  }
  add_action( 'after_setup_theme', 'beslock_core_setup', 10 );
}
