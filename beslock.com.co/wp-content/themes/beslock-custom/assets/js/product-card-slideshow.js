/* product-card-slideshow.js
   Simple cross-fade slideshow for product cards with two images.
   - Does nothing if a wrapper has only one image.
   - Cross-fades by toggling the `visible` class every 5 seconds.
   - No transforms, no scaling, no hover interactions.
*/
(function () {
  const INTERVAL = 5000; // ms

  function init() {
    const wrappers = document.querySelectorAll('.product-image-wrapper');
    wrappers.forEach((wrapper) => {
      const imgs = Array.from(wrapper.querySelectorAll('img.product-img'));
      if (!imgs.length || imgs.length < 2) return; // one or none -> ignore

      // Only use the first two images; extra images are ignored.
      const pair = imgs.slice(0, 2);

      // Ensure initial state: first visible
      pair.forEach((img, idx) => {
        if (idx === 0) img.classList.add('visible');
        else img.classList.remove('visible');
      });

      let current = 0;

      const tick = () => {
        const next = (current + 1) % pair.length;
        pair[current].classList.remove('visible');
        pair[next].classList.add('visible');
        current = next;
      };

      // Start the interval. Store it so it can be cleared if needed.
      const timer = setInterval(tick, INTERVAL);
      // Attach to element for possible future cleanup
      wrapper.__beslock_slideshow_timer = timer;
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
