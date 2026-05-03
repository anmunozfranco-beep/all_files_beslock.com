// Product gallery init fallback — scoped to product pages
// Ensures WooCommerce gallery initialization if the slider hasn't been activated.
(function () {
  'use strict';

  function initGalleryIfNeeded() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    try {
      var $gallery = $('.woocommerce-product-gallery');
      if (!$gallery.length) return;

      // If plugin/theme already initialized the gallery it will add flexslider data/class.
      // Only run fallback when not already initialized.
      if (!$gallery.hasClass('flexslider')) {
        console.info('product-gallery-init: forcing WooCommerce gallery init');
        if (typeof $.fn.wc_product_gallery === 'function') {
          $gallery.wc_product_gallery();
        } else if (typeof $.fn.flexslider === 'function') {
          // Older fallback: initialize flexslider directly if available
          $gallery.find('.woocommerce-product-gallery__wrapper').flexslider({
            animation: 'slide',
            controlNav: false,
            directionNav: true,
            smoothHeight: true
          });
        } else {
          console.warn('product-gallery-init: no gallery initializer available');
        }
      }
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
