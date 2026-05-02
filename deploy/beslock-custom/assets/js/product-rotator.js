/* product-rotator.js
   Simple cross-fade rotator for product cards containing exactly two images.
   - Toggles .visible every 5s
   - No inline styles, no transforms, no hover behavior
*/

(function () {
  'use strict';

  var INTERVAL_MS = 5000;

  function startRotators() {
    // Prefer BEM container, fall back to legacy selector
    var wrappers = document.querySelectorAll('.product-card__image-rotator, .product-image-rotator');
    if (!wrappers || wrappers.length === 0) return;

    wrappers.forEach(function (wrapper) {
      // Prefer BEM frame, fall back to legacy
      var imgs = wrapper.querySelectorAll('img.product-card__frame, img.product-frame');
      if (!imgs || imgs.length < 1) return; // need at least one image

      // Initialize active index using BEM modifier or legacy 'is-active' / 'visible'
      var visibleIndex = 0;
      imgs.forEach(function(img, idx){
        if (img.classList.contains('product-card__frame--active') || img.classList.contains('is-active') || img.classList.contains('visible')) {
          visibleIndex = idx;
        }
        // Ensure only the active image has the active classes
        if (idx === visibleIndex) {
          img.classList.add('product-card__frame--active');
          img.classList.add('is-active');
        } else {
          img.classList.remove('product-card__frame--active');
          img.classList.remove('is-active');
        }
      });

      if (imgs.length === 1) return; // nothing to rotate

      setInterval(function () {
        imgs[visibleIndex].classList.remove('product-card__frame--active');
        imgs[visibleIndex].classList.remove('is-active');
        visibleIndex = (visibleIndex + 1) % imgs.length;
        imgs[visibleIndex].classList.add('product-card__frame--active');
        imgs[visibleIndex].classList.add('is-active');
      }, 5000);
      });
    }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startRotators);
  } else {
    startRotators();
  }

})();
