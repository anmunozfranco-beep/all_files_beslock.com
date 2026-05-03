<?php
/**
 * Enqueue theme assets and head helpers.
 */

add_action( 'wp_enqueue_scripts', function() {

  // If this theme is used as a child theme, ensure the Kadence parent stylesheet
  // is enqueued so the site inherits the parent's layout and e-commerce assets.
  if ( function_exists( 'is_child_theme' ) && is_child_theme() ) {
    wp_enqueue_style( 'kadence-parent-style', get_template_directory_uri() . '/style.css', [], null );
  }

  $theme_dir_uri  = get_stylesheet_directory_uri();
  $theme_dir_path = get_stylesheet_directory();

  $ver_main_css = file_exists( $theme_dir_path . '/assets/css/main.css' )
    ? filemtime( $theme_dir_path . '/assets/css/main.css' )
    : null;

  wp_enqueue_style(
    'beslock-extra-style',
    $theme_dir_uri . '/assets/css/main.css',
    array( 'beslock-main-style' ),
    $ver_main_css
  );

  $inline_header_fallback = "\n.header{position:fixed;top:0;left:0;right:0;z-index:var(--z-header);}\n";
  wp_add_inline_style( 'beslock-main-style', $inline_header_fallback );

  $cart_css_path = $theme_dir_path . '/assets/css/beslock-cart-empty.css';
  if ( file_exists( $cart_css_path ) ) {
    if ( function_exists( 'is_cart' ) && is_cart() ) {
      wp_enqueue_style(
        'beslock-cart-empty',
        $theme_dir_uri . '/assets/css/beslock-cart-empty.css',
        array( 'beslock-main-style' ),
        filemtime( $cart_css_path )
      );
    }
  }

  wp_enqueue_style(
    'beslock-bootstrap-icons',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css',
    [],
    '1.13.1'
  );

  $menu_css_path = $theme_dir_path . '/assets/css/menu-products-mobile.css';
  $ver_menu_css = file_exists( $menu_css_path ) ? filemtime( $menu_css_path ) : null;

  wp_enqueue_style(
    'beslock-menu-products-mobile',
    $theme_dir_uri . '/assets/css/menu-products-mobile.css',
    [ 'beslock-main-style' ],
    $ver_menu_css
  );

  $models_css_path = $theme_dir_path . '/assets/css/models-mobile.css';
  $ver_models_css = file_exists( $models_css_path ) ? filemtime( $models_css_path ) : null;

  wp_enqueue_style(
    'beslock-models-mobile',
    $theme_dir_uri . '/assets/css/models-mobile.css',
    [ 'beslock-main-style', 'beslock-menu-products-mobile' ],
    $ver_models_css
  );

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

  $main_js_path = $theme_dir_path . '/assets/js/main.js';
  $ver_main_js = file_exists( $main_js_path ) ? filemtime( $main_js_path ) : null;

  wp_enqueue_script(
    'beslock-main-js',
    $theme_dir_uri . '/assets/js/main.js',
    [],
    $ver_main_js,
    true
  );

  // Debug helper: force a cache-busted load of main.js while debugging JS loading issues.
  if ( file_exists( $main_js_path ) ) {
    wp_enqueue_script( 'beslock-debug', $theme_dir_uri . '/assets/js/main.js', array(), time(), true );
  }

  $fix_placeholder_js = $theme_dir_path . '/assets/js/fix-placeholder.js';
  if ( file_exists( $fix_placeholder_js ) ) {
    wp_enqueue_script( 'beslock-fix-placeholder-js', $theme_dir_uri . '/assets/js/fix-placeholder.js', array( 'beslock-main-js' ), filemtime( $fix_placeholder_js ), true );
  }

  $menu_js_path = $theme_dir_path . '/assets/js/menu-products-mobile.js';
  $ver_menu_js = file_exists( $menu_js_path ) ? filemtime( $menu_js_path ) : null;

  wp_enqueue_script(
    'beslock-menu-products-mobile-js',
    $theme_dir_uri . '/assets/js/menu-products-mobile.js',
    [ 'beslock-main-js' ],
    $ver_menu_js,
    true
  );

  $models_js_path = $theme_dir_path . '/assets/js/models-mobile.js';
  $ver_models_js = file_exists( $models_js_path ) ? filemtime( $models_js_path ) : null;

  wp_enqueue_script(
    'beslock-models-mobile-js',
    $theme_dir_uri . '/assets/js/models-mobile.js',
    [ 'beslock-main-js', 'beslock-menu-products-mobile-js' ],
    $ver_models_js,
    true
  );

  $widgets_css = $theme_dir_path . '/assets/css/product-widgets.css';
  if ( file_exists( $widgets_css ) ) {
    wp_enqueue_style( 'beslock-product-widgets', $theme_dir_uri . '/assets/css/product-widgets.css', [ 'beslock-main-style' ], filemtime( $widgets_css ) );
  }

  $product_page_css = $theme_dir_path . '/assets/css/product-page.css';
  if ( file_exists( $product_page_css ) ) {
    wp_enqueue_style( 'beslock-product-page', $theme_dir_uri . '/assets/css/product-page.css', [ 'beslock-main-style' ], filemtime( $product_page_css ) );
  }

  $product_tabs_css = $theme_dir_path . '/assets/css/product-tabs.css';
  if ( file_exists( $product_tabs_css ) ) {
    wp_enqueue_style( 'beslock-product-tabs', $theme_dir_uri . '/assets/css/product-tabs.css', [ 'beslock-product-page' ], filemtime( $product_tabs_css ) );
  }
  $product_tabs_js = $theme_dir_path . '/assets/js/product-tabs.js';
  if ( file_exists( $product_tabs_js ) ) {
    wp_enqueue_script( 'beslock-product-tabs-js', $theme_dir_uri . '/assets/js/product-tabs.js', [ 'beslock-main-js' ], filemtime( $product_tabs_js ), true );
  }

  $qty_js = $theme_dir_path . '/assets/js/product-quantity-controls.js';
  if ( file_exists( $qty_js ) ) {
    wp_enqueue_script( 'beslock-product-qty-js', $theme_dir_uri . '/assets/js/product-quantity-controls.js', [ 'beslock-main-js' ], filemtime( $qty_js ), true );
  }

  /* Badge injector removed to avoid duplicate client-side injections.
     Server-side rendering in template-parts/product-card.php provides the badge. */

  $product_rotator_css = $theme_dir_path . '/assets/css/product-rotator.css';
  if ( file_exists( $product_rotator_css ) ) {
    wp_enqueue_style( 'beslock-product-rotator', $theme_dir_uri . '/assets/css/product-rotator.css', [ 'beslock-main-style' ], filemtime( $product_rotator_css ) );
  }

  $product_rotator_js = $theme_dir_path . '/assets/js/product-rotator.js';
  if ( file_exists( $product_rotator_js ) ) {
    wp_enqueue_script( 'beslock-product-rotator-js', $theme_dir_uri . '/assets/js/product-rotator.js', [ 'beslock-main-js' ], filemtime( $product_rotator_js ), true );
  }

  $product_card_alt = $theme_dir_path . '/assets/css/product-card-alt.css';
  if ( file_exists( $product_card_alt ) ) {
    if ( function_exists( 'is_cart' ) && is_cart() ) {
      wp_enqueue_style( 'beslock-product-card-alt', $theme_dir_uri . '/assets/css/product-card-alt.css', array( 'beslock-main-style', 'beslock-product-rotator' ), filemtime( $product_card_alt ) );
    }
  }

  $product_card_fade = $theme_dir_path . '/assets/css/product-card-fade.css';
  if ( file_exists( $product_card_fade ) ) {
    if ( function_exists( 'is_cart' ) && is_cart() ) {
      wp_enqueue_style( 'beslock-product-card-fade', $theme_dir_uri . '/assets/css/product-card-fade.css', array( 'beslock-main-style' ), filemtime( $product_card_fade ) );
    }
  }

  // Product gallery reel assets removed to restore WooCommerce baseline gallery.
  // $reel_css = $theme_dir_path . '/assets/css/product-gallery-reel.css';
  // if ( file_exists( $reel_css ) ) {
  //   wp_enqueue_style( 'beslock-product-gallery-reel', $theme_dir_uri . '/assets/css/product-gallery-reel.css', [ 'beslock-main-style' ], filemtime( $reel_css ) );
  // }
  // $reel_js = $theme_dir_path . '/assets/js/product-gallery-reel.js';
  // if ( file_exists( $reel_js ) ) {
  //   wp_enqueue_script( 'beslock-product-gallery-reel-js', $theme_dir_uri . '/assets/js/product-gallery-reel.js', [ 'beslock-main-js' ], filemtime( $reel_js ), true );
  // }

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

}, 10 );

// Inline early-capture script: prevent navigation to raw uploads image URLs and open overlay
add_action( 'wp_head', function(){
  ?>
  <script>
  (function(){
    function openOverlay(src){
      try{
        if(window.__beslock_inline_overlay) return;
        var overlay = document.createElement('div');
        overlay.id = 'beslock-inline-overlay';
        overlay.style.position = 'fixed';
        overlay.style.left = '0';
        overlay.style.top = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.background = 'rgba(8,8,8,0.95)';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.zIndex = 2147483647;
        overlay.style.cursor = 'zoom-out';

        var img = document.createElement('img');
        img.src = src;
        img.alt = '';
        img.style.maxWidth = '100%';
        img.style.maxHeight = '100%';
        img.style.objectFit = 'contain';

        var btn = document.createElement('button');
        btn.setAttribute('type','button');
        btn.setAttribute('aria-label','Close image');
        btn.className = 'beslock-inline-close';
        btn.innerHTML = '\u00D7';
        btn.style.position = 'absolute';
        btn.style.top = '12px';
        btn.style.right = '12px';
        btn.style.width = '44px';
        btn.style.height = '44px';
        btn.style.border = '0';
        btn.style.borderRadius = '22px';
        btn.style.background = 'rgba(0,0,0,0.5)';
        btn.style.color = '#fff';
        btn.style.fontSize = '28px';
        btn.style.lineHeight = '44px';
        btn.style.textAlign = 'center';
        btn.style.cursor = 'pointer';
        btn.style.zIndex = 2147483650;

        btn.addEventListener('click', function(e){
          e.stopPropagation();
          try{ if(overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay); }catch(err){}
          window.__beslock_inline_overlay = null;
          document.documentElement.style.overflow = '';
          document.body.style.overflow = '';
          document.removeEventListener('keydown', onKey);
        }, false);

        overlay.style.position = 'fixed';
        overlay.style.padding = '24px';

        overlay.appendChild(btn);
        overlay.appendChild(img);

        function close(){
          try{ if(overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay); }catch(e){}
          window.__beslock_inline_overlay = null;
          document.documentElement.style.overflow = '';
          document.body.style.overflow = '';
          document.removeEventListener('keydown', onKey);
        }

        function onKey(e){ if(e.key === 'Escape' || e.keyCode === 27) close(); }

        overlay.addEventListener('click', function(e){
          var actionable = e.target.closest && e.target.closest('a, button');
          if(actionable) return;
          close();
        }, { capture: true });

        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        document.body.appendChild(overlay);
        window.__beslock_inline_overlay = overlay;
        document.addEventListener('keydown', onKey);
      }catch(e){ }
    }

    function inertAnchors(){
      try{
        var anchors = document.querySelectorAll('.woocommerce div.product div.images a');
        anchors.forEach(function(a){
          try{
            var href = a.getAttribute('href') || a.href || '';
            if(href && href.indexOf('/wp-content/uploads/') !== -1){
              a.setAttribute('data-beslock-inert','1');
              try{ a.removeAttribute('href'); }catch(e){}
              a.style.cursor = 'default';
            }
          }catch(e){}
        });
      }catch(e){}
    }

    function onPointer(e){
      try{
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if(!a) return;
        if(!(a.closest && a.closest('.woocommerce div.product'))) return;
        var href = a.getAttribute('href') || a.href || '';
        if(href && href.indexOf('/wp-content/uploads/') !== -1){
          e.preventDefault();
          e.stopImmediatePropagation();
          try{ a.removeAttribute('href'); }catch(e){}
          openOverlay(href);
        }
      }catch(err){}
    }

    if(document.readyState === 'loading'){
      document.addEventListener('DOMContentLoaded', inertAnchors);
    } else { inertAnchors(); }

    document.addEventListener('pointerdown', onPointer, true);
    document.addEventListener('touchstart', onPointer, true);

    try{
      var prod = document.querySelector('.woocommerce div.product');
      if(prod && window.MutationObserver){
        var mo = new MutationObserver(function(){ inertAnchors(); });
        mo.observe(prod, { childList:true, subtree:true, attributes:true });
      }
    }catch(e){}
  })();
  </script>
  <?php
}, 1 );

// Enqueue wc-scope-fix stylesheet (kept with enqueue assets)
add_action( 'wp_enqueue_scripts', function() {
  $css_file = get_stylesheet_directory() . '/assets/css/wc-scope-fix.css';
  if ( file_exists( $css_file ) ) {
    wp_enqueue_style( 'beslock-wc-scope-fix', get_stylesheet_directory_uri() . '/assets/css/wc-scope-fix.css', [ 'beslock-main-style' ], filemtime( $css_file ) );
  }
}, 20 );

// Dequeue Kadence styles late to allow parent enqueues first
add_action( 'wp_enqueue_scripts', function() {
  if ( is_admin() ) { return; }
  global $wp_styles;
  if ( empty( $wp_styles ) || empty( $wp_styles->registered ) ) { return; }
  foreach ( $wp_styles->registered as $handle => $data ) {
    if ( strpos( $handle, 'kadence' ) === 0 ) {
      wp_dequeue_style( $handle );
      wp_deregister_style( $handle );
    }
  }
  if ( wp_style_is( 'kadence-parent-style', 'enqueued' ) || wp_style_is( 'kadence-parent-style', 'registered' ) ) {
    wp_dequeue_style( 'kadence-parent-style' );
    wp_deregister_style( 'kadence-parent-style' );
  }
  $extra_handles = [ 'global-styles', 'classic-theme-styles', 'wp-block-library-theme', 'wp-block-library' ];
  foreach ( $extra_handles as $h ) {
    if ( wp_style_is( $h, 'enqueued' ) || wp_style_is( $h, 'registered' ) ) {
      wp_dequeue_style( $h );
      wp_deregister_style( $h );
    }
  }

}, 100 );
