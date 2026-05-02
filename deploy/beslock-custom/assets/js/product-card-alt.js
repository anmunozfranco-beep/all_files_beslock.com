(function(){
  'use strict';
  if (typeof window === 'undefined') return;
  // Idempotent init
  if (window.__beslock_product_alt_init) return; window.__beslock_product_alt_init = true;

  var INTERVAL = 5000; // ms
  var containers = Array.prototype.slice.call(document.querySelectorAll('.products-portfolio .product-card__image.has-alt'));
  if (!containers.length) return;

  containers.forEach(function (c) {
    // ensure only one timer per container
    if (c.__altTimer) return;
    var visible = false;
    // Ensure images are present
    var altImg = c.querySelector('.product-card__image--alt');
    var mainImg = c.querySelector('.product-card__image--main');
    if (!altImg || !mainImg) return;

    // Strict inline safety: enforce no transforms and only opacity transitions
    try {
      mainImg.style.setProperty('transform', 'none', 'important');
      mainImg.style.setProperty('scale', '1', 'important');
      mainImg.style.setProperty('transition-property', 'opacity', 'important');
      altImg.style.setProperty('transform', 'none', 'important');
      altImg.style.setProperty('scale', '1', 'important');
      altImg.style.setProperty('transition-property', 'opacity', 'important');
    } catch (e) {}

    // Preload alt image source (if lazyloaded, let IntersectionObserver handle real load)
    try {
      var src = altImg.getAttribute('data-src') || altImg.getAttribute('src');
      if (src) {
        var pre = new Image(); pre.src = src;
      }
    } catch (e) {}

    // Lock container height to the main image aspect ratio to prevent CLS
    var aspect = 0;
    function lockHeight() {
      try {
        var w = c.clientWidth || c.getBoundingClientRect().width;
        // prefer natural size ratio if available
        if (mainImg.naturalWidth && mainImg.naturalHeight) {
          aspect = mainImg.naturalHeight / mainImg.naturalWidth;
        }
        // fallback: if we don't have natural dims yet, try measured image height
        if (!aspect) {
          var imgRect = mainImg.getBoundingClientRect();
          if (imgRect.width > 0) aspect = imgRect.height / imgRect.width;
        }
        if (aspect) {
          var h = Math.round(w * aspect);
          c.style.height = h + 'px';
          c.style.minHeight = h + 'px';
          c.style.maxHeight = h + 'px';
        } else {
          // As a last resort, lock current computed height to avoid movement
          var cur = c.getBoundingClientRect().height;
          if (cur) {
            c.style.height = Math.round(cur) + 'px';
            c.style.minHeight = c.style.height;
          }
        }
      } catch (e) {}
    }

    // If main image not loaded yet, wait for load to compute aspect
    if (mainImg.complete && mainImg.naturalWidth) {
      lockHeight();
    } else {
      try { mainImg.addEventListener('load', lockHeight, { once: true, passive: true }); } catch (e) { mainImg.addEventListener('load', lockHeight); }
      // also attempt to lock after a small timeout in case lazy loader sets src
      setTimeout(lockHeight, 800);
    }

    // Update on resize (throttled)
    var resizeTimer = null;
    window.addEventListener('resize', function () {
      if (resizeTimer) clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () { lockHeight(); resizeTimer = null; }, 250);
    }, { passive: true });

    // Toggle function
    function toggle() {
      visible = !visible;
      if (visible) c.classList.add('alt-visible'); else c.classList.remove('alt-visible');
    }

    // Start after a slight stagger so not all cards swap at once
    var startDelay = 500 + Math.floor(Math.random() * 1200);
    c.__altTimer = setTimeout(function(){
      // first toggle to show alt after startDelay
      toggle();
      // then interval
      c.__altTimer = setInterval(toggle, INTERVAL);
    }, startDelay);

    // Clear timers if element is removed from DOM later
    var mo = new MutationObserver(function(){
      if (!document.body.contains(c)) {
        try { clearInterval(c.__altTimer); clearTimeout(c.__altTimer); } catch(e){}
        try { mo.disconnect(); } catch(e){}
      }
    });
    mo.observe(document.body, { childList: true, subtree: true });
  });
})();
