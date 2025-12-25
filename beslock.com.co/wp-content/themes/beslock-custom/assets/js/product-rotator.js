/* product-rotator.js
   Simple cross-fade rotator for product cards containing exactly two images.
   - Toggles .visible every 5s
   - No inline styles, no transforms, no hover behavior
*/

(function () {
  'use strict';

  var INTERVAL_MS = 5000;

  function startRotators() {
    var wrappers = document.querySelectorAll('.product-image-rotator');
    if (!wrappers || wrappers.length === 0) return;

    wrappers.forEach(function (wrapper) {
      var imgs = wrapper.querySelectorAll('img.product-frame');
      if (!imgs || imgs.length !== 2) return; // only operate when exactly two images present

      // Ensure deterministic initial state: first image visible
      if (!imgs[0].classList.contains('visible') && !imgs[1].classList.contains('visible')) {
        imgs[0].classList.add('visible');
      }

      var visibleIndex = imgs[0].classList.contains('visible') ? 0 : 1;

      setInterval(function () {
        imgs[visibleIndex].classList.remove('visible');
        visibleIndex = 1 - visibleIndex;
        imgs[visibleIndex].classList.add('visible');
      }, INTERVAL_MS);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startRotators);
  } else {
    startRotators();
  }
})();
