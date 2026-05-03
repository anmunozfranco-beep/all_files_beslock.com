<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Product feature hooks: register callbacks that prepare data and delegate
 * rendering to template parts to keep markup out of logic files.
 */

error_log( 'Loaded OK: inc/woocommerce/product-features.php' );

add_action( 'beslock_product_trust_badges', function() {
  global $product;
  if ( ! $product ) return;
  $pid = intval( $product->get_id() );
  $badges_meta = get_post_meta( $pid, 'beslock_trust_badges', true );
  if ( empty( $badges_meta ) ) return;
  $badges = is_array( $badges_meta ) ? $badges_meta : array_map( 'trim', explode( ',', (string) $badges_meta ) );
  if ( empty( $badges ) ) return;
  get_template_part( 'template-parts/product-features', null, array( 'section' => 'badges', 'badges' => $badges ) );
}, 10 );

add_action( 'beslock_product_confianza', function() {
  global $product;
  if ( ! $product ) return;
  $pid = intval( $product->get_id() );
  $conf = get_post_meta( $pid, 'beslock_confianza', true );
  if ( empty( $conf ) ) return;
  get_template_part( 'template-parts/product-features', null, array( 'section' => 'confianza', 'confianza' => $conf ) );
} );

add_action( 'beslock_product_psb', function() {
  global $post;
  if ( empty( $post ) ) return;
  $pid = intval( $post->ID );
  $problem = get_post_meta( $pid, 'beslock_psb_problem', true );
  $solution = get_post_meta( $pid, 'beslock_psb_solution', true );
  $benefits = get_post_meta( $pid, 'beslock_psb_benefits', true );
  if ( empty( $problem ) && empty( $solution ) && empty( $benefits ) ) return;
  get_template_part( 'template-parts/product-features', null, array( 'section' => 'psb', 'problem' => $problem, 'solution' => $solution, 'benefits' => $benefits ) );
} );

add_action( 'beslock_product_specs', function() {
  global $product;
  if ( ! $product ) return;
  $pid = intval( $product->get_id() );
  $has_attrs = ( ! empty( $product->get_attributes() ) );
  $specs_meta = get_post_meta( $pid, 'beslock_specs', true );
  if ( ! $has_attrs && empty( $specs_meta ) ) return;
  get_template_part( 'template-parts/product-features', null, array( 'section' => 'specs', 'specs' => $specs_meta, 'has_attrs' => $has_attrs, 'product_id' => $pid ) );
} );

add_action( 'beslock_product_demo', function() {
  global $post;
  if ( empty( $post ) ) return;
  $embed = get_post_meta( $post->ID, 'beslock_demo_embed', true );
  if ( empty( $embed ) ) return;
  get_template_part( 'template-parts/product-features', null, array( 'section' => 'demo', 'embed' => $embed ) );
} );

add_action( 'beslock_product_who', function() {
  global $post;
  $who = get_post_meta( $post->ID, 'beslock_who', true );
  if ( empty( $who ) ) return;
  get_template_part( 'template-parts/product-features', null, array( 'section' => 'who', 'who' => $who ) );
} );

add_action( 'beslock_product_faq', function() {
  global $post;
  $faq = get_post_meta( $post->ID, 'beslock_faq', true );
  if ( empty( $faq ) ) return;
  get_template_part( 'template-parts/product-features', null, array( 'section' => 'faq', 'faq' => $faq ) );
} );

add_action( 'beslock_product_cta', function() {
  global $product;
  if ( ! $product ) return;
  get_template_part( 'template-parts/product-features', null, array( 'section' => 'cta', 'product_id' => intval( $product->get_id() ) ) );
} );

/**
 * Admin product checkbox: toggle 'beslock_badge' per product
 */
add_action( 'woocommerce_product_options_general_product_data', function() {
  woocommerce_wp_checkbox( array(
    'id' => 'beslock_badge',
    'label' => __( 'Mostrar badge de instalación', 'beslock' ),
    'desc_tip' => true,
    'description' => __( 'Activa el badge "Instalación incluida" en este producto.', 'beslock' ),
  ) );
} );

add_action( 'woocommerce_process_product_meta', function( $post_id ) {
  $value = isset( $_POST['beslock_badge'] ) && $_POST['beslock_badge'] ? 'yes' : 'no';
  update_post_meta( $post_id, 'beslock_badge', $value );
} );
