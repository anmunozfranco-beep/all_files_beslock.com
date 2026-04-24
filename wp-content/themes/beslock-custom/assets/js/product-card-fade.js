/* product-card-fade.js
   Simple cross-fade for product cards with exactly two images.
   - Toggles the `visible` class every 5 seconds
   - Leaves single-image cards untouched
   - No transforms, no scaling, no hover effects
*/

(function () {
  'use strict';

  function initFade() {
    var wrappers = document.querySelectorAll('.product-image-wrapper');
    if (!wrappers || wrappers.length === 0) return;

    wrappers.forEach(function (wrapper) {
      var imgs = wrapper.querySelectorAll('.product-img');
      if (!imgs || imgs.length !== 2) return; // only operate when exactly 2 images

      // Ensure a deterministic initial visible image (prefer first)
      if (!imgs[0].classList.contains('visible') && !imgs[1].classList.contains('visible')) {
        imgs[0].classList.add('visible');
      }

      var visibleIndex = imgs[0].classList.contains('visible') ? 0 : 1;

      // Use a per-wrapper interval so each card toggles independently.
      // 5000ms = 5s
      setInterval(function () {
        imgs[visibleIndex].classList.remove('visible');
        visibleIndex = 1 - visibleIndex;
        imgs[visibleIndex].classList.add('visible');
      }, 5000);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFade);
  } else {
    initFade();
  }
})();
