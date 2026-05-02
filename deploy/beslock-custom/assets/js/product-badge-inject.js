(function(params){
  if (!params) return;
  try {
    var targets = (params.targets || []).map(function(t){ return (t||'').trim().toLowerCase(); });
    var src = (params.theme_uri || '') + '/assets/images/instal.png';

    function injectBadges(){
      document.querySelectorAll('.product-card').forEach(function(card){
        if (card.querySelector('.product-card__badge')) return; // already has badge
        var titleEl = card.querySelector('.product-card__title');
        var name = titleEl && titleEl.textContent && titleEl.textContent.trim().toLowerCase();
        if (!name) return;
        if (targets.indexOf(name) !== -1) {
          var container = card.querySelector('.product-card__image') || card;
          // ensure container is positioned so absolute badge aligns
          var cs = window.getComputedStyle(container);
          if (cs.position === 'static') container.style.position = 'relative';
          var img = document.createElement('img');
          img.className = 'product-card__badge';
          img.src = src;
          img.alt = 'Instalación incluida';
          img.setAttribute('aria-hidden', 'true');
          container.appendChild(img);
        }
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', injectBadges);
    } else {
      injectBadges();
    }

    // Also observe for dynamically injected product-cards (e.g., AJAX grids)
    var observer = new MutationObserver(function(){ injectBadges(); });
    observer.observe(document.body, { childList: true, subtree: true });
  } catch (e) {
    console.error('beslock badge injector error', e);
  }
})(window.beslock_badge_params);
