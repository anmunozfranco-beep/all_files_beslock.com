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