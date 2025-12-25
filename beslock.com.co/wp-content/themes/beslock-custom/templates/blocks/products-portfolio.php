<?php
/**
 * products-portfolio.php
 *
 * Front-page product grid — must use the underscore variants located in /assets/images/
 * (these are the larger/front-page images reserved for the portfolio).
 */

$products = [
  [
    'name'  => 'e-Nova',
    'desc'  => "Acceso inteligente sin llaves para tu día a día.\nHuella y control simple para moverte con libertad y tranquilidad.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-nova_.webp',
    'images' => [ 'e-nova.webp', 'e-nova_s.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Orbit',
    'desc'  => "Mira y controla quién llega a tu puerta, estés donde estés.\nSeguridad avanzada con video y acceso inteligente desde tu celular.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-orbit_.webp',
    'images' => [ 'e-orbit_.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Touch',
    'desc'  => "Acceso fácil para espacios compartidos.\nClave y huella para que todos puedan entrar, sin complicaciones.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-touch_.webp',
    'images' => [ 'e-touch.webp', 'e-touch_c.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Flex',
    'desc'  => "Ideal para recibir y dar acceso sin estar presente.\nCódigos temporales, huella y control remoto para estancias cortas.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-flex_.webp',
    'images' => [ 'e-flex_.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Shield',
    'desc'  => "Protección inteligente para tu hogar.\nSeguridad robusta con acceso electrónico y respaldo mecánico.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-shield_.webp',
    'images' => [ 'e-shield.webp', 'e-shield_e.webp' ],
    'link'  => '#'
  ],
  [
    'name'  => 'e-Prime',
    'desc'  => "Control total para accesos exigentes.\nGestión avanzada de usuarios para espacios de alto flujo.",
    'image' => get_stylesheet_directory_uri() . '/assets/images/e-prime_.webp',
    'images' => [ 'e-prime_.webp' ],
    'link'  => '#'
  ],
];

echo '<section class="products-portfolio section reveal"><div class="u-container products-portfolio__grid">';
foreach ($products as $product) {
  set_query_var('product', $product);
  get_template_part('templates/blocks/product-card');
}
echo '</div></section>';
?>