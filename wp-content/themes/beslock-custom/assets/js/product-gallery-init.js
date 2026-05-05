// Product gallery init fallback — scoped to product pages
// Ensures WooCommerce gallery initialization if the slider hasn't been activated.
(function () {
  'use strict';

  function initGalleryIfNeeded() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    try {
      // Only run on Woo single product pages
      if (!document.querySelector('.single-product')) return;

      var $gallery = $('.woocommerce-product-gallery');
      if (!$gallery.length) return;

      console.info('product-gallery-init: resetting and reinitializing Woo gallery');

      // Remove previous, possibly-broken initialization artifacts
      try {
        $gallery.removeClass('flexslider');
        $gallery.find('.flex-viewport').remove();
        $gallery.find('.flex-control-nav, .flex-direction-nav').remove();
      } catch (e) { /* ignore cleanup errors */ }

      // Re-init cleanly after a short delay to allow other scripts to settle
      setTimeout(function () {
        try {
          if (typeof $.fn.wc_product_gallery === 'function') {
            $gallery.wc_product_gallery();
            console.info('product-gallery-init: wc_product_gallery executed');
          } else if (typeof $.fn.flexslider === 'function') {
            // Fallback: initialize flexslider directly on wrapper
            $gallery.find('.woocommerce-product-gallery__wrapper').flexslider({
              animation: 'slide',
              controlNav: false,
              directionNav: true,
              smoothHeight: true
            });
            console.info('product-gallery-init: flexslider fallback executed');
          } else {
            console.warn('product-gallery-init: no gallery initializer available');
          }
        } catch (e) { console.warn('product-gallery-init: init error', e); }
      }, 100);

    } catch (e) {
      console.warn('product-gallery-init error', e);
    }
  }

  // Run on DOM ready and also after window load to catch late-loaded scripts
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGalleryIfNeeded);
  } else {
    initGalleryIfNeeded();
  }

  // Also try again after window load
  window.addEventListener('load', function () {
    setTimeout(initGalleryIfNeeded, 50);
  });

})();
