<?php
// /wp-content/themes/beslock-custom/footer.php
// Footer minimal con logo blanco centrado.
// Incluye aqu��� los elementos que removimos del header: el script de sticky header,
// la sincronizaci���n de la variable CSS del logo (para que el footer calcule 40%),
// y la llamada a wp_footer() seguida de los cierres </body></html>.
?>
<footer class="site-footer">
  <div class="u-container" style="padding:2rem 0; text-align:center;">
    <img
      class="footer-logo"
      src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo-white.png' ); ?>"
      alt="<?php echo esc_attr_x( 'Beslock logo blanco', 'alt text', 'beslock' ); ?>"
      loading="lazy"
    />
  </div>
</footer>

<!-- Sticky header / shrink (moved here desde header.php para centralizar scripts en el footer) -->
<script>
(function(){
  // Throttled scroll handler to toggle .header--scrolled
  var last = 0;
  var throttleMS = 90;
  // HERO gate (12vh) — recomputed on init and resize only
  var HERO_GATE = (window && window.innerHeight) ? window.innerHeight * 0.12 : 0;
  function updateHeroGate() { try { HERO_GATE = window.innerHeight * 0.12; } catch (e) {} }

  function onScroll() {
    var now = Date.now();
    if (now - last < throttleMS) return;
    last = now;
    var header = document.querySelector('.header');
    if (!header) return;
    var y = window.scrollY || 0;
    // While inside the hero keep header fully in its initial state
    if (y < HERO_GATE) {
      header.classList.remove('header--scrolled');
      return;
    }
    if (y > 10) {
      header.classList.add('header--scrolled');
    } else {
      header.classList.remove('header--scrolled');
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  // Keep HERO_GATE correct on resize
  window.addEventListener('resize', function(){
    updateHeroGate();
  }, { passive: true });

  // Sincroniza el tama���o del logo del header con la variable CSS --site-logo-height
  // para que el footer pueda usar calc(var(--site-logo-height) * 0.4)
  function updateSiteLogoHeight() {
    var logo = document.querySelector('.header__logo img');
    if (!logo) return;
    // Si la imagen a���n no tiene tama���o, intenta esperar a load
    var setVar = function() {
      var h = logo.clientHeight || logo.naturalHeight || 0;
      if (h && h > 0) {
        document.documentElement.style.setProperty('--site-logo-height', h + 'px');
      }
    };
    // Si la imagen ya est��� cargada
    if (logo.complete) {
      setVar();
    } else {
      // cuando se cargue la imagen, actualiza
      logo.addEventListener('load', setVar, { once: true });
      // fallback: intenta de inmediato
      setVar();
    }
  }

  // Actualiza al cargar el DOM, al cargar la ventana y al redimensionar
  document.addEventListener('DOMContentLoaded', updateSiteLogoHeight);
  window.addEventListener('load', updateSiteLogoHeight);
  window.addEventListener('resize', function() {
    // debounce ligero
    clearTimeout(window.__beslock_logo_h_timeout);
    window.__beslock_logo_h_timeout = setTimeout(updateSiteLogoHeight, 120);
  });
})();
</script>

<?php wp_footer(); ?>

<!-- product-gallery-reel fetch+eval fallback DISABLED (was causing load issues). -->
<script>console && console.info && console.info('product-gallery-reel: fetch fallback disabled');</script>

</body>
</html>

<script>
// Fallback ligero: si por alguna razón no se inicializó el drawer JS, este handler
// garantiza que el botón del menú abra/cierre el drawer de forma básica.
(function(){
  function initFallback() {
    try {
      var menuBtn = document.getElementById('menuBtn');
      var mobileDrawer = document.getElementById('mobileDrawer');
      var backdrop = document.getElementById('drawerBackdrop') || document.querySelector('.mobile-drawer__backdrop');
      if (!menuBtn || !mobileDrawer) return;

      // No sobrescribimos si ya existe la API moderna
      if (window.beslock && window.beslock.drawer && (typeof window.beslock.drawer.open === 'function')) return;

      function open() {
        mobileDrawer.classList.add('is-open');
        mobileDrawer.setAttribute('aria-hidden','false');
        menuBtn.setAttribute('aria-expanded','true');
        if (backdrop) backdrop.classList.add('backdrop-visible');
        document.documentElement.classList.add('has-drawer-open');
        document.body.style.position = 'fixed';
      }
      function close() {
        mobileDrawer.classList.remove('is-open');
        mobileDrawer.setAttribute('aria-hidden','true');
        menuBtn.setAttribute('aria-expanded','false');
        if (backdrop) backdrop.classList.remove('backdrop-visible');
        document.documentElement.classList.remove('has-drawer-open');
        document.body.style.position = '';
      }

      menuBtn.addEventListener('click', function(e){ e && e.preventDefault && e.preventDefault(); if (mobileDrawer.classList.contains('is-open')) close(); else open(); });
      if (backdrop) backdrop.addEventListener('click', function(e){ e && e.preventDefault && e.preventDefault(); close(); });
    } catch (e) { /* silent */ }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initFallback); else initFallback();
})();
</script>
<script>
// Emergency runtime safeguard: if header or cart are hidden by other scripts,
// force them visible. This runs late to override transient states.
(function(){
  try{
    var hdr = document.querySelector('.header');
    if(hdr){ hdr.style.setProperty('display','flex','important'); hdr.style.setProperty('visibility','visible'); hdr.style.setProperty('opacity','1'); hdr.style.setProperty('z-index','9998'); }
    var cart = document.querySelector('.header__icon--cart');
    if(cart){ cart.style.setProperty('display','flex','important'); cart.style.setProperty('visibility','visible'); cart.style.setProperty('opacity','1'); cart.style.setProperty('z-index','9999'); }
    var tm = document.querySelector('.logo__tm');
    if(tm){ tm.style.setProperty('font-size','20px','important'); tm.style.setProperty('opacity','1'); tm.style.setProperty('display','inline-flex','important'); }
  }catch(e){ console && console.error && console.error('beslock: emergency header restore failed', e); }
})();
</script>
<script>
// Debug helper: runs once on page load to report gallery counter state and force visibility for testing.
(function(){
  try{
    console.info('beslock-debug: start');
    var badge = document.getElementById('beslock-pgr-debug-badge');
    console.info('beslock-debug: badge=', badge && badge.textContent);

    (function(){
      var counters = Array.from(document.querySelectorAll('.product-page__gallery-counter'));
      console.info('beslock-debug: counters found=', counters.length);
      counters.forEach(function(n,i){
        console.info('beslock-debug: counter', i, n.outerHTML, {
          display: getComputedStyle(n).display,
          color: getComputedStyle(n).color,
          zIndex: getComputedStyle(n).zIndex,
          visibility: getComputedStyle(n).visibility,
          opacity: getComputedStyle(n).opacity,
          width: n.clientWidth,
          height: n.clientHeight
        });
        // Force visible for testing
        n.style.setProperty('display','block','important');
        n.style.setProperty('color','#000','important');
        n.style.setProperty('z-index','99999','important');
        n.style.setProperty('visibility','visible','important');
        n.style.setProperty('opacity','1','important');
        n.style.setProperty('position','relative','important');
      });
      if(counters.length === 0){
        var d = document.getElementById('beslock-debug-overlay');
        if(!d){ d = document.createElement('div'); d.id = 'beslock-debug-overlay'; d.style.cssText = 'position:fixed;left:12px;bottom:12px;z-index:999999;background:#fff;border:2px solid #000;padding:8px;color:#000;font-weight:700;'; document.body.appendChild(d); }
        d.textContent = 'Beslock debug: counters=0, badge=' + (badge && badge.textContent || 'none');
      }
    })();
  }catch(e){ console && console.error && console.error('beslock-debug: runtime error', e); }
})();
</script>