/**
 * Beslock – Main JS (complete, improved mobile menu)
 *
 * Integrates:
 * - GSAP reveal
 * - IntersectionObserver lazy images
 * - Header behaviors (scrollRestoration control, pageshow, logo click)
 * - Mobile menu (idempotent handlers, focus-trap, scroll lock, swipe)
 * - Products panel
 *
 * Replace your existing /assets/js/main.js with this file (backup first).
 */
(function () {
  'use strict';

  // ===== Utilities =====
  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };
  var on = function (el, ev, fn, opts) { if (!el) return; el.addEventListener(ev, fn, opts || false); };
  var off = function (el, ev, fn, opts) { if (!el || !fn) return; el.removeEventListener(ev, fn, opts || false); };
  var once = function (el, ev, fn, opts) { if (!el) return; el.addEventListener(ev, fn, Object.assign({ once: true }, opts || {})); };

  // ===== Header behaviors =====
  function headerBehaviorsInit() {
    var SCROLL_THRESHOLD = 10;
    var header = $('.header');
    // Hysteresis thresholds to prevent flicker when user scrolls around the boundary.
    // Enter threshold = when we add the scrolled class; Exit threshold = when we remove it.
    var ENTER_THRESHOLD = 28; // px scrolled before applying shrink state
    var EXIT_THRESHOLD = 12;  // px scrolled below this removes the shrink state
    var isScrolled = header && header.classList.contains('header--scrolled');
    // HERO gate: don't run header toggles until user scrolls past 12vh
    var HERO_GATE = window.innerHeight * 0.12;
    function updateHeroGate() { try { HERO_GATE = window.innerHeight * 0.12; } catch (e) {} }
    var logoAnchor = document.querySelector('.header__logo a') || document.querySelector('.header__logo');

    function updateHeader() {
      if (!header) return;
      var y = window.scrollY || window.pageYOffset || 0;
      // While within the hero (top of page), keep header fully in its initial state
      // and do not evaluate threshold transitions. Once y >= HERO_GATE allow
      // the existing threshold-based logic to run unchanged.
      if (y < HERO_GATE) {
        if (isScrolled) {
          header.classList.remove('header--scrolled');
          isScrolled = false;
        }
        return;
      }
      // Only toggle when crossing thresholds to avoid rapid on/off when user
      // is hovering near the trigger point.
      if (!isScrolled && y > ENTER_THRESHOLD) {
        header.classList.add('header--scrolled');
        isScrolled = true;
      } else if (isScrolled && y < EXIT_THRESHOLD) {
        header.classList.remove('header--scrolled');
        isScrolled = false;
      }
    }

    function setScrollRestorationManualIfReload() {
      try {
        var navType = null;
        if (performance && performance.getEntriesByType) {
          var entries = performance.getEntriesByType('navigation');
          if (entries && entries[0] && entries[0].type) navType = entries[0].type;
        }
        if (navType === null && performance && performance.navigation) {
          navType = performance.navigation.type === 1 ? 'reload' : (performance.navigation.type === 2 ? 'back_forward' : 'navigate');
        }
        if (navType === 'reload' && 'scrollRestoration' in history) {
          history.scrollRestoration = 'manual';
        }
      } catch (e) {}
      window.scrollTo(0, 0);
    }

    function closeDrawerIfOpen() {
      var mobileDrawer = document.getElementById('mobileDrawer');
      var menuBtn = document.getElementById('menuBtn');
      if (!mobileDrawer) return;
      if (mobileDrawer.classList.contains('open')) {
        mobileDrawer.classList.remove('open');
        mobileDrawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('drawer-no-scroll');
        if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
        try { if (menuBtn) menuBtn.focus(); } catch (e) {}
      }
    }

    function hookLogo() {
      if (!logoAnchor) return;
      // remove previous if any
      if (logoAnchor._beslockLogoHandler) {
        off(logoAnchor, 'click', logoAnchor._beslockLogoHandler);
        delete logoAnchor._beslockLogoHandler;
      }
      logoAnchor._beslockLogoHandler = function (ev) {
        try { ev.preventDefault(); } catch (e) {}
        closeDrawerIfOpen();
        window.scrollTo(0, 0);
        updateHeader();
        var href = logoAnchor.getAttribute ? (logoAnchor.getAttribute('href') || logoAnchor.href) : (logoAnchor.href || '/');
        if (!href || href === '#' || href === window.location.href || href === window.location.pathname) {
          history.replaceState(null, '', window.location.pathname);
          return;
        }
        setTimeout(function () { window.location.href = href; }, 80);
      };
      on(logoAnchor, 'click', logoAnchor._beslockLogoHandler, { passive: true });
    }

    function onPageShow(e) {
      if (e && e.persisted) {
        window.scrollTo(0, 0);
      }
      updateHeader();
    }

    setScrollRestorationManualIfReload();
    updateHeader();
    // ensure HERO_GATE is correct on init
    updateHeroGate();
    on(window, 'scroll', function () { window.requestAnimationFrame(updateHeader); }, { passive: true });
    // Recompute HERO_GATE on resize and schedule a single rAF'ed update
    window.addEventListener('resize', function () {
      updateHeroGate();
      window.requestAnimationFrame(updateHeader);
    }, { passive: true });
    on(window, 'pageshow', onPageShow);
    hookLogo();
    on(window, 'beforeunload', function () {
      try { if ('scrollRestoration' in history) history.scrollRestoration = 'auto'; } catch (e) {}
    });
    console.log('main.js: header behaviors initialized');
  }

  // ===== Lazy images =====
  function initLazyImages() {
    var selector = 'img.lazyload, img[data-src]';
    var imgs = $$(selector);
    if (!imgs.length) return;

    function loadImage(img) {
      var dataSrcset = img.getAttribute('data-srcset');
      var dataSrc = img.getAttribute('data-src');
      if (dataSrcset) img.setAttribute('srcset', dataSrcset);
      if (dataSrc) img.setAttribute('src', dataSrc);
      img.classList.remove('lazyload');
    }

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var img = entry.target;
            loadImage(img);
            observer.unobserve(img);
          }
        });
      }, { rootMargin: '200px 0px', threshold: 0.01 });

      imgs.forEach(function (i) { io.observe(i); });
    } else {
      imgs.forEach(function (img) { loadImage(img); });
    }
    console.log('main.js: lazy images init');
  }

  // ===== GSAP reveals =====
  function initGsapReveals() {
    if (window.gsap && window.ScrollTrigger) {
      try {
        gsap.registerPlugin(ScrollTrigger);
        gsap.utils.toArray('.reveal').forEach(function (el) {
          gsap.fromTo(el, { opacity: 0, y: 50 }, {
            opacity: 1, y: 0, duration: 1, ease: "power3.out",
            scrollTrigger: { trigger: el, start: "top 85%", toggleActions: "play none none reverse" }
          });
        });
        console.log('main.js: GSAP reveals initialized');
        return;
      } catch (e) {
        console.warn('main.js: GSAP failed, falling back', e);
      }
    }
    $$('.reveal').forEach(function (el) { el.style.opacity = 1; });
    console.log('main.js: GSAP not available, reveal fallback applied');
  }

  // ===== Mobile menu (idempotent) =====
  function mobileMenuInit() {
    // If another menu script is present (menu-products-mobile.js), skip init to avoid double handlers.
    if (document.querySelector('script[src*="menu-products-mobile"]') && !window.__beslock_force_main_menu_init) {
      console.log('mobileMenuInit: menu-products-mobile script present — skipping main menu init.');
      return;
    }

    var menuBtn = document.getElementById('menuBtn');
    var mobileDrawer = document.getElementById('mobileDrawer');
    var closeDrawer = document.getElementById('closeDrawer');
    var drawerBackdrop = document.getElementById('drawerBackdrop');

    if (!mobileDrawer || !menuBtn) {
      console.warn('main.js: mobileMenuInit aborted, #mobileDrawer or #menuBtn not found.');
      return;
    }

    // Mark that main.js initialized the menu to avoid re-init by other scripts.
    window.__beslock_menu_initialized = window.__beslock_menu_initialized || 'main';

    // cleanup previous handlers if any (idempotent)
    function removeStoredHandler(el, propName) {
      if (!el || !propName) return;
      var h = el[propName];
      if (h && typeof h === 'function') {
        try { off(el, h._evType || 'click', h); } catch (e) {}
        delete el[propName];
      }
    }

    // store multiple handlers mapping on element._beslockHandlers
    function storeHandler(el, key, evType, fn) {
      if (!el) return;
      // remove existing
      if (el._beslockHandlers && el._beslockHandlers[key]) {
        try { off(el, el._beslockHandlers[key]._evType || evType, el._beslockHandlers[key]); } catch (e) {}
      } else {
        el._beslockHandlers = el._beslockHandlers || {};
      }
      fn._evType = evType;
      el._beslockHandlers[key] = fn;
      on(el, evType, fn);
    }

    // focus trap helper
    function trapFocus(container) {
      var focusable = container.querySelectorAll('a, button, input, textarea, [tabindex]:not([tabindex="-1"])');
      if (!focusable || !focusable.length) return function () {};
      var first = focusable[0], last = focusable[focusable.length - 1];
      function handle(e) {
        if (e.key !== 'Tab') return;
        if (e.shiftKey) {
          if (document.activeElement === first) { e.preventDefault(); last.focus(); }
        } else {
          if (document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
      }
      document.addEventListener('keydown', handle);
      return function () { document.removeEventListener('keydown', handle); };
    }

    var releaseTrap = function () {};
    var releaseTrapProducts = function () {};

    function openDrawer() {
      mobileDrawer.classList.add('open');
      mobileDrawer.setAttribute('aria-hidden', 'false');
      document.body.classList.add('drawer-no-scroll');
      if (menuBtn) menuBtn.setAttribute('aria-expanded', 'true');
      if (closeDrawer) try { closeDrawer.focus(); } catch (e) {}
      releaseTrap = trapFocus(mobileDrawer);
      console.log('main.js: mobile menu opened');
    }

    function closeDrawerFn() {
      mobileDrawer.classList.remove('open');
      mobileDrawer.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('drawer-no-scroll');
      if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
      try { if (menuBtn) menuBtn.focus(); } catch (e) {}
      if (typeof releaseTrap === 'function') releaseTrap();
      console.log('main.js: mobile menu closed');
    }

    // attach idempotent handlers with a small debounce to avoid touch->click double-fire
    var lastToggle = 0;
    var MIN_INTERVAL = 300;
    storeHandler(menuBtn, 'toggle', 'click', function (e) {
      e && e.preventDefault && e.preventDefault();
      var now = Date.now();
      if (now - lastToggle < MIN_INTERVAL) {
        return;
      }
      lastToggle = now;
      (mobileDrawer.classList.contains('open') ? closeDrawerFn() : openDrawer());
    });

    if (closeDrawer) {
      storeHandler(closeDrawer, 'close', 'click', function (e) { e && e.preventDefault && e.preventDefault(); closeDrawerFn(); });
    }
    if (drawerBackdrop) {
      storeHandler(drawerBackdrop, 'backdrop', 'click', function () { closeDrawerFn(); });
    }

    // ESC (idempotent)
    if (document._beslockEscHandler) {
      try { off(document, 'keydown', document._beslockEscHandler); } catch (e) {}
      delete document._beslockEscHandler;
    }
    document._beslockEscHandler = function (e) { if (e.key === 'Escape' && mobileDrawer.classList.contains('open')) closeDrawerFn(); };
    on(document, 'keydown', document._beslockEscHandler);

    // close when clicking normal links inside drawer (except inside #productsPanel)
    // remove previous link handlers
    if (mobileDrawer._linkHandlers && Array.isArray(mobileDrawer._linkHandlers)) {
      mobileDrawer._linkHandlers.forEach(function (obj) {
        try { off(obj.el, obj.ev, obj.fn); } catch (e) {}
      });
    }
    mobileDrawer._linkHandlers = [];
    mobileDrawer.querySelectorAll('a.mobile-menu__link, a.product-card').forEach(function (lnk) {
      var fn = function (ev) {
        if (ev.target && ev.target.closest && ev.target.closest('#productsPanel')) return;
        closeDrawerFn();
      };
      mobileDrawer._linkHandlers.push({ el: lnk, ev: 'click', fn: fn });
      on(lnk, 'click', fn);
    });

    // swipe-to-close (idempotent attach)
    // remove old handlers if present
    if (mobileDrawer._swipeHandlersAttached) {
      // nothing needed, handlers are passive; we'll reattach safely
    }
    (function () {
      var panel = mobileDrawer.querySelector('.mobile-drawer__panel');
      if (!panel) return;
      var startX = 0, startY = 0, tracking = false, deltaX = 0, deltaY = 0;
      // Remove old ones if stored
      if (panel._beslockTouchStart) {
        try { off(panel, 'touchstart', panel._beslockTouchStart); off(panel, 'touchmove', panel._beslockTouchMove); off(panel, 'touchend', panel._beslockTouchEnd); } catch (e) {}
      }
      panel._beslockTouchStart = function (ev) {
        if (!mobileDrawer.classList.contains('open')) return;
        if (!ev.touches || !ev.touches.length) return;
        startX = ev.touches[0].clientX;
        startY = ev.touches[0].clientY;
        tracking = true;
      };
      panel._beslockTouchMove = function (ev) {
        if (!tracking) return;
        if (!ev.touches || !ev.touches.length) return;
        deltaX = ev.touches[0].clientX - startX;
        deltaY = ev.touches[0].clientY - startY;
        if (Math.abs(deltaY) > Math.abs(deltaX)) { tracking = false; return; }
      };
      panel._beslockTouchEnd = function () {
        if (!tracking) return;
        tracking = false;
        var threshold = 60;
        if (deltaX < -threshold) closeDrawerFn();
        deltaX = 0; deltaY = 0;
      };
      on(panel, 'touchstart', panel._beslockTouchStart, { passive: true });
      on(panel, 'touchmove', panel._beslockTouchMove, { passive: true });
      on(panel, 'touchend', panel._beslockTouchEnd, { passive: true });
      mobileDrawer._swipeHandlersAttached = true;
    })();

    console.log('main.js: mobile menu initialized (idempotent)');
  }

  // ===== Products panel =====
  function productsPanelInit() {
    var productsToggle = document.getElementById('productsToggle');
    var productsPanel = document.getElementById('productsPanel');
    var productsBack = document.getElementById('productsBack');
    var productsClose = document.getElementById('productsClose');

    if (!productsToggle || !productsPanel) return;

    if (!productsPanel.hasAttribute('hidden')) {
      productsPanel.setAttribute('hidden', 'true');
      productsPanel.setAttribute('aria-hidden', 'true');
    }

    function show() {
      var menuContent = document.getElementById('menuContent');
      if (menuContent) { menuContent.setAttribute('aria-hidden', 'true'); menuContent.style.display = 'none'; }
      var nav = document.getElementById('mobileDrawer');
      var backdrop = document.getElementById('drawerBackdrop') || document.querySelector('.mobile-drawer__backdrop');
      // Open the main drawer (same animation as hamburger menu)
      try { if (nav) nav.classList.add('is-open'); } catch (e) {}
      try { if (backdrop) backdrop.classList.add('backdrop-visible'); } catch (e) {}
      try { document.documentElement.classList.add('has-drawer-open'); } catch (e) {}
      // Prepare opening from left: add opening class, reveal products panel, then activate .products-open
      try { if (nav) nav.classList.add('products-opening'); } catch (e) {}
      productsPanel.removeAttribute('hidden'); productsPanel.setAttribute('aria-hidden', 'false');
      // force reflow then switch to visible state so CSS animates from left -> center
      try { void productsPanel.offsetWidth; } catch (e) {}
      try { if (nav) { nav.classList.add('products-open'); nav.classList.remove('products-opening'); } } catch (e) {}
      if (productsBack) productsBack.focus();
      document.body.classList.add('drawer-no-scroll');
    }
    function hide() {
      var nav = document.getElementById('mobileDrawer');
      // animate closing to the right: add closing class then remove products-open
      try { if (nav) nav.classList.add('products-closing'); } catch (e) {}
      try { if (nav) nav.classList.remove('products-open'); } catch (e) {}
      // hide backdrop and main drawer after animation completes
      var backdrop = document.getElementById('drawerBackdrop') || document.querySelector('.mobile-drawer__backdrop');
      var TRANS_DUR = 340; /* ms, slightly above --products-duration */
      setTimeout(function(){
        try { productsPanel.setAttribute('hidden', 'true'); productsPanel.setAttribute('aria-hidden', 'true'); } catch(e) {}
        var menuContent = document.getElementById('menuContent');
        if (menuContent) { menuContent.removeAttribute('aria-hidden'); menuContent.style.display = ''; }
        try { if (productsToggle) productsToggle.focus(); } catch(e) {}
        document.body.classList.remove('drawer-no-scroll');
        try { if (nav) { nav.classList.remove('products-closing'); nav.classList.remove('is-open'); } } catch(e) {}
        try { if (backdrop) backdrop.classList.remove('backdrop-visible'); } catch(e) {}
        try { document.documentElement.classList.remove('has-drawer-open'); } catch(e) {}
      }, TRANS_DUR);
    }

    on(productsToggle, 'click', function (e) {
      e.preventDefault();
      var expanded = productsToggle.getAttribute('aria-expanded') === 'true';
      if (expanded) { productsToggle.setAttribute('aria-expanded', 'false'); hide(); }
      else { productsToggle.setAttribute('aria-expanded', 'true'); show(); }
    });
    if (productsBack) on(productsBack, 'click', hide);
    if (productsClose) on(productsClose, 'click', hide);

    console.log('main.js: products panel init (lightweight)');
  }

  // ===== Init sequence =====
  function initAll() {
    try { initGsapReveals(); } catch (e) { console.warn(e); }
    if ('requestIdleCallback' in window) requestIdleCallback(initLazyImages, { timeout: 1000 }); else setTimeout(initLazyImages, 300);
    try { headerBehaviorsInit(); } catch (e) { console.warn('header behaviors error', e); }
    try { mobileMenuInit(); } catch (e) { console.warn('mobile menu error', e); }
    try { productsPanelInit(); } catch (e) { console.warn('products panel error', e); }
    console.log('main.js: all modules initialized');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { setTimeout(initAll, 60); }, { once: true });
  } else {
    setTimeout(initAll, 60);
  }

})();

/* === HERO BESLOCK LOGIC (appended) === */
(function () {
  'use strict';
  var H = {
    slideCount: 6,
    slideDuration: 8000,
    // Start overlay animation immediately at 0s so the overlay fades in smoothly
    // as the video plays (user requested overlay starting at second 0).
    overlayStartAt: 0,
    resistanceFactor: 0.3,
    swipeThreshold: 50
  };

  function $q(sel, ctx){ return (ctx||document).querySelector(sel); }
  function $qa(sel, ctx){ return Array.prototype.slice.call((ctx||document).querySelectorAll(sel)); }

  function HeroInit(selector){
    var root = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!root) return null;
    var slides = $qa('.hero-slide', root);
    var dots = $qa('.hero-dot', root);
    var loader = $q('.beslock-loader', root);
    var current = 0, timer = null, isPlaying = false;
    var overlaySchedule = []; // { id, target, fn }
    var featureSchedule = []; // { id, target, fn }
    var slideStartTs = 0;
    var isTouchPaused = false, touchPauseAt = 0, autoplayRemaining = null, autoplayDeadline = null;

    // Loader image is handled by template (favicon); no inline SVG injection needed.

    function waitFirst(){
      return new Promise(function(resolve){
        var v = slides[0] && slides[0].querySelector('.slide-video');
        if (!v) return resolve();
        if (v.readyState >= 4) return resolve();
        var t = setTimeout(function(){ resolve(); }, 5000);
        v.addEventListener('canplaythrough', function(){ clearTimeout(t); resolve(); }, { once:true });
      });
    }

    // Features scheduling helpers (isolated module)
    function clearFeatureTimeouts(){ if (Array.isArray(featureSchedule)){ featureSchedule.forEach(function(obj){ try{ if (obj.id) clearTimeout(obj.id); }catch(e){} }); featureSchedule=[]; } }
    function resetFeaturesOnSlide(slide){ try{ if(!slide) return; var fw = slide.querySelector('.features-wrapper'); if (!fw) return; fw.classList.remove('features--fading'); Array.prototype.slice.call(fw.querySelectorAll('.feature')).forEach(function(f){ f.classList.remove('feature--visible'); }); }catch(e){} }
    function scheduleFeatures(slide){ try{
        clearFeatureTimeouts(); if(!slide) return; var fw = slide.querySelector('.features-wrapper'); if(!fw) return;
        // reset immediately
        resetFeaturesOnSlide(slide);
        var features = Array.prototype.slice.call(fw.querySelectorAll('.feature'));
        // Show all features at once after a short delay so entrance animation still plays
        var SHOW_DELAY = 1200; // ms
        var now = Date.now();
        var base = slideStartTs || now;
        var tShowTarget = base + SHOW_DELAY;
        var tFadeTarget = base + 6500;
        var tHideTarget = base + 7400;

        function scheduleAt(target, fn){ var delay = Math.max(0, target - Date.now()); if (delay === 0) { try{ fn(); }catch(e){} } else { var id = setTimeout(fn, delay); featureSchedule.push({ id:id, target: target, fn: fn }); } }

        scheduleAt(tShowTarget, function(){ try{ features.forEach(function(f){ f.classList.add('feature--visible'); }); }catch(e){} });
        scheduleAt(tFadeTarget, function(){ try{ fw.classList.add('features--fading'); }catch(e){} });
        scheduleAt(tHideTarget, function(){ try{ features.forEach(function(f){ f.classList.remove('feature--visible'); }); fw.classList.remove('features--fading'); }catch(e){} });
      }catch(e){}
    }

    function showSlide(idx, opts){
      opts = opts||{}; idx = (idx + slides.length) % slides.length; // normalize
      // clear overlay scheduled timeouts and remove any attached timeupdate listeners
      if (Array.isArray(overlaySchedule)) {
        overlaySchedule.forEach(function(obj){ try{ if (obj.id) clearTimeout(obj.id); }catch(e){} });
        overlaySchedule = [];
      }
      // clear feature timers and reset features on all slides
      clearFeatureTimeouts(); slides.forEach(function(s){ try{ resetFeaturesOnSlide(s); }catch(e){} });
      // remove any per-overlay timeupdate handlers and loop watchers left on previous videos
      slides.forEach(function(s){
        var pv = s.querySelector('.slide-video'); if (!pv) return;
        s.querySelectorAll('.slide-overlay').forEach(function(o){ try{ if (o._ontime) { pv.removeEventListener('timeupdate', o._ontime); delete o._ontime; } }catch(e){} });
        try{ if (pv._loopWatcher) { pv.removeEventListener('timeupdate', pv._loopWatcher); delete pv._loopWatcher; delete pv._lastCurrent; } }catch(e){}
      });

      var oldIndex = current;
      var oldSlide = slides[oldIndex];
      var newSlide = slides[idx];

      // If immediate option requested (initial load), set active without fade delay
      if (opts.immediate) {
        // make sure all other slides are not active and hidden for a11y
        slides.forEach(function(s,i){ if (s!==newSlide){ s.classList.remove('is-active'); s.classList.remove('is-exiting'); s.setAttribute('aria-hidden','true'); } });
        newSlide.classList.add('is-active'); newSlide.setAttribute('aria-hidden','false');
        // play new video, pause others
        slides.forEach(function(s,i){ var v=s.querySelector('.slide-video'); if (!v) return; if (s===newSlide){ try{ v.currentTime=0; }catch(e){} v.play().catch(function(){}); } else { try{ v.pause(); v.currentTime=0; }catch(e){} } });
        // schedule overlays for new slide below (same logic as before)
        slideStartTs = Date.now();
        current = idx;
        // schedule features for the newly active slide
        try{ scheduleFeatures(newSlide); }catch(e){}
      } else {
        if (oldSlide === newSlide) {
          // nothing to do
        } else {
          // keep all unrelated slides hidden (not active)
          slides.forEach(function(s){ if (s!==oldSlide && s!==newSlide){ s.classList.remove('is-active'); s.classList.remove('is-exiting'); s.setAttribute('aria-hidden','true'); } });

          // Start fade: remove active from old (it will transition opacity 1->0)
          if (oldSlide) {
            oldSlide.classList.remove('is-active');
            // mark as exiting so it sits under the new slide during crossfade
            oldSlide.classList.add('is-exiting');
            // after transition ends, remove exiting and mark hidden
            (function(s){ setTimeout(function(){ try{ s.classList.remove('is-exiting'); s.setAttribute('aria-hidden','true'); }catch(e){} }, 650); })(oldSlide);
            // pause old video's playback after fade completes to free resources
            (function(v){ setTimeout(function(){ try{ v.pause(); v.currentTime = 0; }catch(e){} }, 650); })( (oldSlide.querySelector && oldSlide.querySelector('.slide-video')) || null );
          }

          // Immediately make new slide active (it will fade in 0->1)
          if (newSlide) {
            newSlide.classList.add('is-active'); newSlide.classList.remove('is-exiting'); newSlide.setAttribute('aria-hidden','false');
            // rewind and play new slide video. For known clips that show an initial
            // black frame on play we skip a small epsilon to avoid the flash.
            try{ var nv = newSlide.querySelector('.slide-video'); if (nv){
                try{
                  var SRC = (nv.getAttribute('src') || nv.currentSrc || '').toString();
                  var SKIP_EPS = 0.06; // skip ~60ms to avoid initial black frame
                  if (/_?e-(orbit|shield)/i.test(SRC)) {
                    try { nv.currentTime = SKIP_EPS; } catch (e) {}
                  } else {
                    try { nv.currentTime = 0; } catch (e) {}
                  }
                }catch(e){}
                nv.play().catch(function(){});
              } }catch(e){}
          }

          // make sure other slides than old/new are not interfering
          slides.forEach(function(s){ if (s!==oldSlide && s!==newSlide){ s.classList.remove('is-active'); s.classList.remove('is-exiting'); } });

          slideStartTs = Date.now();
          current = idx;
          // schedule features for new slide
          try{ scheduleFeatures(newSlide); }catch(e){}
        }
      }

      // Update dots accessibility state
      dots.forEach(function(d,i){ d.classList.toggle('is-active', i===current); d.setAttribute('aria-selected', i===current? 'true':'false'); });

      // overlay show logic - support multiple overlays per slide, each may have a data-start attribute
      var sl = slides[current];
      function activateOverlay(targetOverlay) {
        try {
          var siblings = Array.prototype.slice.call(sl.querySelectorAll('.slide-overlay'));
          siblings.forEach(function(s){
            if (s !== targetOverlay) {
              s.classList.remove('overlay--visible');
              try { s.style.removeProperty('transform'); s.style.removeProperty('--overlay-offset'); } catch(e){}
            }
          });
          // If overlay provides a data-offset attribute (numeric, px optional),
          // set a CSS variable instead of writing inline `transform`. Stylesheets
          // will use `--overlay-offset` to position the overlay.
          var dataOff = targetOverlay.getAttribute('data-offset');
          if (dataOff !== null && dataOff !== undefined && dataOff !== '') {
            try {
              if (String(dataOff).indexOf('px') === -1) dataOff = dataOff + 'px';
              targetOverlay.style.setProperty('--overlay-offset', dataOff);
              // Set an inline transform using the numeric offset and mark it important
              // so it wins over author stylesheet `!important` rules (main.css).
              var inlineTransform = 'translateY(calc(-50% + ' + dataOff + ')) scale(1)';
              try { targetOverlay.style.setProperty('transform', inlineTransform, 'important'); } catch(e) {}
            } catch(e) { try{ targetOverlay.style.removeProperty('--overlay-offset'); targetOverlay.style.removeProperty('transform'); }catch(e){} }
          } else {
            try{ targetOverlay.style.removeProperty('--overlay-offset'); targetOverlay.style.removeProperty('transform'); }catch(e){}
          }
          targetOverlay.classList.add('overlay--visible');
        } catch (e) {}
      }
      if (sl){
        var vid = sl.querySelector('.slide-video');
        var ovs = Array.prototype.slice.call(sl.querySelectorAll('.slide-overlay'));
        // Ensure overlays start hidden when (re)showing the slide so transitions run on each loop
        // Also clear any inline transforms and per-overlay CSS variables set previously.
        ovs.forEach(function(o){
          o.classList.remove('overlay--visible');
          try { o.style.removeProperty('transform'); o.style.removeProperty('--overlay-offset'); } catch(e){}
        });
        if (vid && ovs.length){
          // attach a loop watcher to reset overlays when the video loops internally (preserve previous logic)
          if (!vid._loopWatcher) {
            vid._lastCurrent = typeof vid.currentTime === 'number' ? vid.currentTime : 0;
            vid._loopWatcher = function(){
              try{
                if (typeof vid._lastCurrent === 'number' && vid.currentTime < 0.2 && vid._lastCurrent > vid.currentTime + 0.5) {
                  var curOvs = Array.prototype.slice.call(sl.querySelectorAll('.slide-overlay'));
                  curOvs.forEach(function(o){ o.classList.remove('overlay--visible'); try{ if (o._ontime) { vid.removeEventListener('timeupdate', o._ontime); delete o._ontime; } }catch(e){} });
                  curOvs.forEach(function(o){
                    var startAtLoop = parseFloat(o.getAttribute('data-start'));
                    if (isNaN(startAtLoop)) startAtLoop = H.overlayStartAt;
                    if (vid.currentTime >= startAtLoop) { activateOverlay(o); }
                    else {
                      o._ontime = function(){ if (vid.currentTime >= startAtLoop){ activateOverlay(o); try{ vid.removeEventListener('timeupdate', o._ontime); delete o._ontime; }catch(e){} } };
                      vid.addEventListener('timeupdate', o._ontime, { passive:true });
                      var fallbackDelay = Math.max(200, H.slideDuration-1200);
                      var target = (slideStartTs || Date.now()) + fallbackDelay;
                      var delay = Math.max(0, target - Date.now());
                      var t2 = setTimeout(function(){ if (!o.classList.contains('overlay--visible')) activateOverlay(o); try{ if (o._ontime) { vid.removeEventListener('timeupdate', o._ontime); delete o._ontime; } }catch(e){} }, delay);
                      overlaySchedule.push({ id: t2, target: target, fn: function(){ if (!o.classList.contains('overlay--visible')) activateOverlay(o); try{ if (o._ontime) { vid.removeEventListener('timeupdate', o._ontime); delete o._ontime; } }catch(e){} } });
                    }
                  });
                }
              }catch(e){}
              vid._lastCurrent = vid.currentTime;
            };
            vid.addEventListener('timeupdate', vid._loopWatcher, { passive:true });
            try {
              var _src = vid.getAttribute('src') || vid.currentSrc || '';
              if (/_?e-(orbit|shield)/i.test(_src)) {
                if (!vid._loopFix) {
                  vid._loopFixPending = false;
                  vid._loopFix = function(){
                    try {
                      if (!vid.duration || !isFinite(vid.duration)) return;
                      var SKIP_EPS = 0.06;
                      var NEAR_END = Math.max(0.06, vid.duration - 0.08);
                      if (vid.currentTime >= NEAR_END) {
                        if (!vid._loopFixPending) {
                          vid._loopFixPending = true;
                          try { vid.currentTime = SKIP_EPS; } catch(e){}
                          setTimeout(function(){ vid._loopFixPending = false; }, 120);
                        }
                      }
                    } catch (e) {}
                  };
                  vid.addEventListener('timeupdate', vid._loopFix, { passive:true });
                }
              }
            } catch (e) {}
          }

          ovs.forEach(function(ov){
            var startAt = parseFloat(ov.getAttribute('data-start'));
            if (isNaN(startAt)) startAt = H.overlayStartAt;
            if (vid.currentTime >= startAt) {
              activateOverlay(ov);
            } else {
              ov._ontime = function(){ if (vid.currentTime >= startAt){ activateOverlay(ov); try{ vid.removeEventListener('timeupdate', ov._ontime); delete ov._ontime; }catch(e){} } };
              vid.addEventListener('timeupdate', ov._ontime, { passive:true });
              var fallbackDelay = Math.max(200, H.slideDuration - 1200);
              var target = (slideStartTs || Date.now()) + fallbackDelay;
              var delay = Math.max(0, target - Date.now());
              var t = setTimeout(function(){ if (!ov.classList.contains('overlay--visible')) activateOverlay(ov); try{ if (ov._ontime) { vid.removeEventListener('timeupdate', ov._ontime); delete ov._ontime; } }catch(e){} }, delay);
              overlaySchedule.push({ id: t, target: target, fn: function(){ if (!ov.classList.contains('overlay--visible')) activateOverlay(ov); try{ if (ov._ontime) { vid.removeEventListener('timeupdate', ov._ontime); delete ov._ontime; } }catch(e){} } });
            }
          });
        }
      }
    }

    // Ensure the .features-wrapper is placed where CSS expects for each breakpoint.
    // On desktop (>=600px) we want the features block to be direct child of .hero-slide
    // so absolute positioning (top) is relative to the slide. On mobile we move it
    // back inside .slide-content so it sits under the subtitle.
    function relocateFeaturesForBreakpoint() {
      try {
        var isDesktop = window.innerWidth >= 600;
        slides.forEach(function(s){
          var fw = s.querySelector('.features-wrapper');
          var sc = s.querySelector('.slide-content');
          if (!fw || !sc) return;
          if (isDesktop) {
            if (fw.parentNode !== s) s.appendChild(fw);
          } else {
            if (fw.parentNode !== sc) sc.appendChild(fw);
          }
        });
      } catch (e) { console.warn('relocateFeaturesForBreakpoint error', e); }
    }

    // run once on init and on resize (debounced)
    relocateFeaturesForBreakpoint();
    var _relocTid = null;
    window.addEventListener('resize', function(){ if (_relocTid) clearTimeout(_relocTid); _relocTid = setTimeout(relocateFeaturesForBreakpoint, 140); }, { passive: true });

    function startAutoplay(){ stopAutoplay(); isPlaying=true; autoplayDeadline = Date.now() + H.slideDuration; timer = setTimeout(nextSlide, H.slideDuration); }
    function stopAutoplay(){ if (timer){ clearTimeout(timer); timer=null; } isPlaying=false; autoplayDeadline = null; autoplayRemaining = null; }
    function resetAutoplay(){ stopAutoplay(); setTimeout(function(){ startAutoplay(); }, 120); }
    function nextSlide(){ showSlide(current+1); startAutoplay(); }

    // bind dots
    dots.forEach(function(d){ d.addEventListener('click', function(){ var i=parseInt(d.getAttribute('data-index'),10); showSlide(i); resetAutoplay(); }, { passive:true }); });

    // TOUCH SWIPE: mobile-friendly, non-destructive handling
    // Rules enforced:
    // - pause all videos on touchstart
    // - do NOT move slides during touchmove (only measure)
    // - on touchend decide based on threshold and call showSlide()
    // - ensure only one slide visible at any time and only its video plays
    (function(){
      var touch = { startX: 0, deltaX: 0, dragging: false };

      function ensureSingleVisible() {
        try {
          slides.forEach(function(s,i){ if (i === current) { s.classList.add('is-active'); s.classList.remove('is-exiting'); s.setAttribute('aria-hidden','false'); } else { s.classList.remove('is-active'); s.classList.remove('is-exiting'); s.setAttribute('aria-hidden','true'); } });
        } catch(e) {}
      }

      // Scheduling helpers for pause/resume during touch
      function pauseAllVideos() {
        try { slides.forEach(function(s){ var v = s.querySelector && s.querySelector('.slide-video'); if (v && typeof v.pause === 'function') { try{ v.pause(); }catch(e){} } }); } catch(e){}
      }

      function pauseSchedulesForTouch(){
        if (isTouchPaused) return;
        isTouchPaused = true;
        touchPauseAt = Date.now();
        // pause autoplay
        if (autoplayDeadline) {
          autoplayRemaining = Math.max(0, autoplayDeadline - touchPauseAt);
          if (timer) { clearTimeout(timer); timer = null; }
        }
        // pause overlay schedules
        overlaySchedule.forEach(function(obj){ try{ if (obj.id) { clearTimeout(obj.id); obj.remaining = Math.max(0, obj.target - touchPauseAt); obj.id = null; } else { obj.remaining = Math.max(0, obj.target - touchPauseAt); } }catch(e){} });
        // pause feature schedules
        featureSchedule.forEach(function(obj){ try{ if (obj.id) { clearTimeout(obj.id); obj.remaining = Math.max(0, obj.target - touchPauseAt); obj.id = null; } else { obj.remaining = Math.max(0, obj.target - touchPauseAt); } }catch(e){} });
      }

      function resumeSchedulesAfterTouch(){
        if (!isTouchPaused) return;
        var now = Date.now();
        overlaySchedule.forEach(function(obj){ try{ var rem = typeof obj.remaining === 'number' ? obj.remaining : Math.max(0, obj.target - now); if (rem <= 0) { try{ obj.fn(); }catch(e){} } else { obj.id = setTimeout(obj.fn, rem); obj.target = now + rem; } }catch(e){} });
        featureSchedule.forEach(function(obj){ try{ var rem = typeof obj.remaining === 'number' ? obj.remaining : Math.max(0, obj.target - now); if (rem <= 0) { try{ obj.fn(); }catch(e){} } else { obj.id = setTimeout(obj.fn, rem); obj.target = now + rem; } }catch(e){} });
        overlaySchedule.forEach(function(o){ try{ delete o.remaining; }catch(e){} });
        featureSchedule.forEach(function(o){ try{ delete o.remaining; }catch(e){} });
        // resume autoplay
        if (typeof autoplayRemaining === 'number') {
          if (autoplayRemaining <= 0) { nextSlide(); }
          else { timer = setTimeout(nextSlide, autoplayRemaining); autoplayDeadline = Date.now() + autoplayRemaining; }
          autoplayRemaining = null;
        }
        isTouchPaused = false;
      }

      function onStart(e){
        if (!e.touches || e.touches.length !== 1) return;
        touch.startX = e.touches[0].clientX;
        touch.deltaX = 0;
        touch.dragging = true;
        // Pause all videos while swiping
        pauseAllVideos();
        // Ensure only the current slide is visible during the gesture
        ensureSingleVisible();
        // Pause schedules (features, overlays, autoplay)
        pauseSchedulesForTouch();
      }

      function onMove(e){
        if (!touch.dragging) return;
        if (!e.touches || e.touches.length !== 1) return;
        touch.deltaX = e.touches[0].clientX - touch.startX;
        // Do not move DOM or apply transforms here — only measure
      }

      function onEnd(e){
        if (!touch.dragging) return;
        touch.dragging = false;
        var dx = touch.deltaX || 0;
        // Decide change only after gesture ends
        if (Math.abs(dx) > H.swipeThreshold) {
          if (dx < 0) {
            showSlide(current + 1);
          } else {
            showSlide(current - 1);
          }
        } else {
          // No change: do NOT call showSlide(current) because that clears
          // feature timeouts and hides the features block. Instead, resume
          // playback of the current slide's video and ensure slide classes
          // remain as they were during the gesture.
          try {
            var cur = slides[current];
            if (cur) {
              // ensure only current slide visible
              slides.forEach(function(s,i){ if (i===current){ s.classList.add('is-active'); s.classList.remove('is-exiting'); s.setAttribute('aria-hidden','false'); } else { s.classList.remove('is-active'); s.classList.remove('is-exiting'); s.setAttribute('aria-hidden','true'); } });
              var cv = cur.querySelector && cur.querySelector('.slide-video');
              if (cv && typeof cv.play === 'function') {
                // resume from currentTime (we paused on touchstart)
                cv.play().catch(function(){});
              }
            }
          } catch (e) {}
        }
        // resume schedules (features, overlays, autoplay)
        resumeSchedulesAfterTouch();
      }

      document.addEventListener('touchstart', onStart, { passive:true });
      document.addEventListener('touchmove', onMove, { passive:true });
      document.addEventListener('touchend', onEnd, { passive:true });
      document.addEventListener('touchcancel', onEnd, { passive:true });
    })();

    // visibility pause
    document.addEventListener('visibilitychange', function(){ if (document.hidden) stopAutoplay(); else startAutoplay(); });

    // wait first video then keep loader visible 2.5s, then hide loader and start
    waitFirst().then(function(){
      var DELAY = 2500; // ms
      setTimeout(function(){
        if (loader) loader.setAttribute('aria-hidden','true');
        root.classList.add('ready');
        showSlide(0, { immediate:true });
        startAutoplay();
      }, DELAY);
    });

    // expose for debug
    window.__beslockHero = { show: showSlide, next: nextSlide, prev:function(){ showSlide(current-1); }, stop:stopAutoplay, start:startAutoplay };
    return window.__beslockHero;
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ HeroInit('#beslockHero'); }, { once:true }); else HeroInit('#beslockHero');

})();
