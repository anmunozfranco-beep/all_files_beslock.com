<?php
/**
 * Product card template — outputs a markup compatible with the theme rotator JS
 * Expected markup:
 * - wrapper: .product-card__image-rotator or .product-image-rotator
 * - image frames: img.product-card__frame and img.product-frame
 * - first frame must include active modifiers (.product-card__frame--active and .is-active)
 */

$product = isset( $product ) && is_array( $product ) ? $product : array();
$theme_dir = get_stylesheet_directory();

// Build canonical image list: prefer WooCommerce product attachments when mapped
$images = array();
if ( ! empty( $product['product_id'] ) && function_exists( 'wc_get_product' ) ) {
  $pid = intval( $product['product_id'] );
  $thumb_id = get_post_thumbnail_id( $pid );
  if ( $thumb_id ) {
    $url = wp_get_attachment_image_url( $thumb_id, 'large' );
    if ( $url ) $images[] = $url;
  }
  $gallery_meta = get_post_meta( $pid, '_product_image_gallery', true );
  if ( $gallery_meta ) {
    $gids = array_filter( array_map( 'intval', explode( ',', $gallery_meta ) ) );
    foreach ( $gids as $gid ) {
      $url = wp_get_attachment_image_url( $gid, 'large' );
      if ( $url ) $images[] = $url;
    }
  }
}

// Do NOT fallback to theme template images; only use WooCommerce attachments.
// If no images available, leave $images empty so template shows empty placeholder.

// Normalize to absolute URLs where possible
$normalized = array();
foreach ( $images as $img ) {
  if ( is_numeric( $img ) ) {
    $url = wp_get_attachment_image_url( intval( $img ), 'large' );
    if ( $url ) $normalized[] = $url;
    continue;
  }
  if ( is_string( $img ) && ( strpos( $img, 'http://' ) === 0 || strpos( $img, 'https://' ) === 0 ) ) {
    $normalized[] = $img;
    continue;
  }
  // treat as theme-local filename under assets/images/
  $name = ltrim( (string) $img, "/" );
  $abs = $theme_dir . '/assets/images/' . $name;
  if ( file_exists( $abs ) ) {
    $normalized[] = get_stylesheet_directory_uri() . '/assets/images/' . $name . '?v=' . filemtime( $abs );
  }
}
$images = array_values( array_unique( $normalized ) );
?>

<?php
/**
 * Wrapper that adapts an array-based $product used by the portfolio block
 * into a canonical WC_Product object and delegates rendering to the
 * template-parts/product-card component to avoid duplicate markup.
 */

// Accept product from set_query_var('product') or local $product variable
$p = isset( $product ) ? $product : ( get_query_var( 'product', null ) );

// Determine show_desc flag when provided as array
$show_desc = true;
if ( is_array( $p ) && isset( $p['show_desc'] ) ) {
  $show_desc = (bool) $p['show_desc'];
}

// If array with product_id, pass that id to the component for normalization
$arg_product = null;
if ( is_array( $p ) && ! empty( $p['product_id'] ) ) {
  if ( function_exists( 'wc_get_product' ) ) {
    $arg_product = wc_get_product( intval( $p['product_id'] ) );
  }
} elseif ( is_object( $p ) ) {
  // If it's already a WC_Product object, use it; otherwise attempt to map by ID
  if ( is_a( $p, 'WC_Product' ) ) {
    $arg_product = $p;
  } elseif ( isset( $p->ID ) && function_exists( 'wc_get_product' ) ) {
    $arg_product = wc_get_product( intval( $p->ID ) );
  }
}

if ( $arg_product ) {
  get_template_part( 'template-parts/product-card', null, array( 'product' => $arg_product, 'show_description' => $show_desc ) );
}
?>

<?php
/* end file */
