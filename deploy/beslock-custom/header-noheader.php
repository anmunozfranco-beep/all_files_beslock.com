<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Performance: preconnect a CDN / fonts -->
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Keep frontpage hero preload when on front page -->
  <?php if ( function_exists( 'is_front_page' ) && is_front_page() ) : 
    $hero_poster = get_stylesheet_directory_uri() . '/assets/images/hero-poster.webp';
  ?>
    <link rel="preload" as="image" href="<?php echo esc_url( $hero_poster ); ?>">
  <?php endif; ?>

  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<!-- Intentionally omitted header markup for pages that load this header variant. -->
