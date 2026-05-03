<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

$args = isset( $args ) ? $args : array();
$section = $args['section'] ?? '';

switch ( $section ) {
  case 'badges':
    $badges = $args['badges'] ?? array();
    if ( empty( $badges ) ) break;
    echo '<div class="beslock-trust-badges">';
    foreach ( $badges as $b ) {
      $slug = sanitize_title( $b );
      printf( '<span class="beslock-badge beslock-badge--%s">%s</span>', esc_attr( $slug ), esc_html( $b ) );
    }
    echo '</div>';
    break;

  case 'confianza':
    $conf = $args['confianza'] ?? '';
    if ( empty( $conf ) ) break;
    echo '<div class="beslock-confianza">' . wp_kses_post( $conf ) . '</div>';
    break;

  case 'psb':
    $problem = $args['problem'] ?? '';
    $solution = $args['solution'] ?? '';
    $benefits = $args['benefits'] ?? '';
    echo '<div class="beslock-psb">';
    if ( $problem ) echo '<div class="psb-col"><h4>Problema</h4><div>' . wp_kses_post( $problem ) . '</div></div>';
    if ( $solution ) echo '<div class="psb-col"><h4>Solución</h4><div>' . wp_kses_post( $solution ) . '</div></div>';
    if ( $benefits ) echo '<div class="psb-col"><h4>Beneficios</h4><div>' . wp_kses_post( $benefits ) . '</div></div>';
    echo '</div>';
    break;

  case 'specs':
    $specs = $args['specs'] ?? '';
    $has_attrs = ! empty( $args['has_attrs'] );
    $product_id = $args['product_id'] ?? 0;
    echo '<div class="beslock-specs">';
    echo '<h3>Especificaciones técnicas</h3>';
    if ( ! empty( $specs ) ) echo '<div class="beslock-specs__meta">' . wp_kses_post( $specs ) . '</div>';
    if ( $has_attrs && function_exists( 'wc_get_product' ) ) {
      $product = wc_get_product( intval( $product_id ) );
      if ( $product && function_exists( 'wc_display_product_attributes' ) ) {
        echo '<div class="beslock-specs__attrs">' . wc_display_product_attributes( $product ) . '</div>';
      }
    }
    echo '</div>';
    break;

  case 'demo':
    $embed = $args['embed'] ?? '';
    if ( empty( $embed ) ) break;
    echo '<div class="beslock-demo"><h3>Demo / Uso</h3><div class="beslock-demo__embed">' . wp_kses_post( $embed ) . '</div></div>';
    break;

  case 'who':
    $who = $args['who'] ?? '';
    if ( empty( $who ) ) break;
    echo '<div class="beslock-who"><h3>Para quién es</h3><div>' . wp_kses_post( $who ) . '</div></div>';
    break;

  case 'faq':
    $faq = $args['faq'] ?? '';
    if ( empty( $faq ) ) break;
    echo '<div class="beslock-faq"><h3>Preguntas frecuentes</h3><div>' . wp_kses_post( $faq ) . '</div></div>';
    break;

  case 'cta':
    $pid = intval( $args['product_id'] ?? 0 );
    if ( ! $pid ) break;
    $product = wc_get_product( $pid );
    if ( ! $product ) break;
    echo '<div class="beslock-cta">';
    echo '<div class="beslock-cta__price">' . $product->get_price_html() . '</div>';
    if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
      echo '<div class="beslock-cta__buy">';
      woocommerce_template_single_add_to_cart();
      echo '</div>';
    } else {
      echo '<a class="button" href="' . esc_url( get_permalink( $product->get_id() ) ) . '">Comprar</a>';
    }
    echo '</div>';
    break;
}
