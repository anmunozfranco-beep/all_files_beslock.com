(function(){
  'use strict';

  function qs(root, sel){ return Array.from((root||document).querySelectorAll(sel)); }

  function collectSources(root){
    // prefer Woo nodes inside root
    let nodes = qs(root, '.woocommerce-product-gallery__image');
    if(nodes.length === 0) nodes = qs(root, '.woocommerce-product-gallery__wrapper img');
    const seen = new Set();
    const out = [];
    nodes.forEach(n=>{
      try{
        const img = n.tagName === 'IMG' ? n : n.querySelector && n.querySelector('img');
        const a = n.querySelector && n.querySelector('a');
        const src = img && (img.getAttribute('data-large_image') || img.getAttribute('src'));
        const href = a && a.getAttribute('href');
        const key = href || src || (img && img.alt) || n.innerHTML;
        if(!key || seen.has(key)) return;
        seen.add(key);
        // Minimal, non-mutating scroll-snap tracker for product galleries.
        (function(){
          'use strict';

          function findRoots(){
            return Array.from(document.querySelectorAll('.product-page__gallery, .product-page__gallery-wrapper, .woocommerce-product-gallery'));
          }

          function getSlides(root){
            const selectors = ['.product-page__gallery-slide', '.woocommerce-product-gallery__image', '.product-page__gallery-wrapper img', 'img.wp-post-image'];
            for(const sel of selectors){
              const found = Array.from(root.querySelectorAll(sel));
              if(found && found.length) return found;
            }
            return [];
          }

          function ensureTracking(root){
            if(!root || root.dataset.beslockInit === '1') return;
            const slides = getSlides(root);
            if(!slides.length) return;
            const track = root.querySelector('.woocommerce-product-gallery__wrapper') || root.querySelector('.product-page__gallery-wrapper') || root;
            let current = 0; const total = slides.length;
            const io = new IntersectionObserver((entries)=>{
              entries.forEach(entry=>{
                if(entry.isIntersecting && entry.intersectionRatio > 0.5){
                  const idx = slides.indexOf(entry.target);
                  if(idx !== -1 && idx !== current){ current = idx; const ev = new CustomEvent('beslock:gallery:change', {detail:{index: current, total}}); root.dispatchEvent(ev); }
                }
              });
            }, {threshold:[0.5], root: track});

            slides.forEach(s=> io.observe(s));
            root.dataset.beslockInit = '1';
            console.info('product-gallery (snap): initialized slides=', total, 'root=', root);
          }

          function initAll(){
            const roots = findRoots();
            roots.forEach(r=> ensureTracking(r));
          }

          if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll); else initAll();
          window.addEventListener('load', initAll);
          const mo = new MutationObserver(()=> initAll());
          mo.observe(document.body, {childList:true, subtree:true});

        })();
    // Positioning is handled via CSS (margin-top: 2rem); JS positioning removed
