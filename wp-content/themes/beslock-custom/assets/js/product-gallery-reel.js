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
        out.push({src: src, href: href, alt: img && img.getAttribute('alt')});
      }catch(e){}
    });
    return out;
  }

  function buildCanonical(root, items){
    // remove old
    const old = root.querySelector('.product-page__gallery-canonical');
    if(old) old.remove();

    const canonical = document.createElement('div'); canonical.className = 'product-page__gallery-canonical';
    canonical.style.overflow = 'hidden'; canonical.style.width = '100%'; canonical.style.position = 'relative'; canonical.style.height = '100%';
    const reel = document.createElement('div'); reel.className = 'product-page__gallery-reel';
    reel.style.display='flex'; reel.style.flexWrap='nowrap'; reel.style.width='100%'; reel.style.transition='transform 420ms ease';
    canonical.appendChild(reel);
    // Prefer inserting canonical where the original gallery wrapper is located so vertical
    // placement matches the original element. Fall back to firstChild.
    const anchor = root.querySelector('.product-page__gallery-wrapper, .woocommerce-product-gallery__wrapper, .woocommerce-product-gallery');
    if(anchor && anchor.parentNode) anchor.parentNode.insertBefore(canonical, anchor);
    else root.insertBefore(canonical, root.firstChild);

    items.forEach(it=>{
      const slide = document.createElement('div'); slide.className='product-page__gallery-slide';
      slide.style.flex='0 0 100%'; slide.style.boxSizing='border-box';
      const link = document.createElement('a');
      // Do NOT set href on the canonical anchor — prevent fullscreen / navigation on click
      link.setAttribute('role', 'button');
      const img = document.createElement('img'); if(it.src) img.setAttribute('src', it.src); if(it.alt) img.setAttribute('alt', it.alt);
      img.style.width='100%'; img.style.height='auto'; img.style.display='block'; link.appendChild(img); slide.appendChild(link); reel.appendChild(slide);
    });

    return {canonical, reel, slides: Array.from(reel.children)};
  }

  // NOTE: counter creation/management removed from here — will be handled
  // by a dedicated counter module appended below. This keeps the reel
  // logic intact while enabling a fresh, independent counter rebuild.

  function init(root){
    if(!root || root.dataset.beslockInit === '1') return;
    const items = collectSources(root);
    console.info('product-gallery-reel: init items.length=', items && items.length, 'root=', root);
    if(!items || items.length === 0) return;
    const built = buildCanonical(root, items);
    const reel = built.reel; const slides = built.slides; const total = slides.length;
    const counter = null; // counter handled by separate module

    // Positioning is handled via CSS (margin-top: 2rem); JS positioning removed

    // If only one slide, normal simple behavior
    if(total <= 1){
      let index = 0;
      const getW = ()=> Math.round((root.getBoundingClientRect().width));
      function go(i){ index = ((i % total) + total) % total; reel.style.transform = `translateX(-${index * getW()}px)`; if(counter) counter.textContent = `${index+1}/${total}`; }
      window.addEventListener('resize', ()=> setTimeout(()=> go(index), 120));
      // pointer drag minimal
      let down=false, startX=0, delta=0;
      reel.addEventListener('pointerdown', e=>{ down=true; startX=e.clientX; try{ reel.setPointerCapture && reel.setPointerCapture(e.pointerId);}catch(e){} reel.style.transition='none'; });
      reel.addEventListener('pointermove', e=>{ if(!down) return; delta = e.clientX - startX; reel.style.transform = `translateX(${ -index * getW() + delta }px)`; });
      reel.addEventListener('pointerup', e=>{ if(!down) return; down=false; reel.style.transition='transform 420ms ease'; try{ reel.releasePointerCapture && reel.releasePointerCapture(e.pointerId); }catch(e){} go(index); delta=0; });
      reel.addEventListener('pointercancel', ()=>{ down=false; reel.style.transition='transform 420ms ease'; go(index); delta=0; });
      go(0);
      root.dataset.beslockInit = '1';
      return;
    }

    // Circular behavior without DOM clones: use index wrapping and percent/px transforms.
    let index = 0; // logical index 0..total-1
    const getW = ()=> Math.round((root.getBoundingClientRect().width));

    function setTransform(i, withTransition=true){
      if(!withTransition) reel.style.transition = 'none'; else reel.style.transition = 'transform 420ms ease';
      reel.style.transform = `translateX(-${i * getW()}px)`;
    }

    function go(i){
      index = ((i % total) + total) % total;
      setTransform(index, true);
      if(counter) counter.textContent = `${index+1}/${total}`;
    }

    window.addEventListener('resize', ()=> setTimeout(()=> setTransform(index, false), 120));

    // pointer drag
    let down=false, startX=0, delta=0;
    reel.addEventListener('pointerdown', e=>{ down=true; startX=e.clientX; try{ reel.setPointerCapture && reel.setPointerCapture(e.pointerId);}catch(e){} reel.style.transition='none'; });
    reel.addEventListener('pointermove', e=>{ if(!down) return; delta = e.clientX - startX; reel.style.transform = `translateX(${ -index * getW() + delta }px)`; });
    reel.addEventListener('pointerup', e=>{ if(!down) return; down=false; reel.style.transition='transform 420ms ease'; try{ reel.releasePointerCapture && reel.releasePointerCapture(e.pointerId); }catch(e){} if(Math.abs(delta) > getW()*0.18){ if(delta>0) go(index-1); else go(index+1); } else { go(index); } delta=0; });
    reel.addEventListener('pointercancel', ()=>{ down=false; reel.style.transition='transform 420ms ease'; go(index); delta=0; });

    // click/tap advances to next slide (ignore small drags)
    reel.addEventListener('click', function(e){ if(Math.abs(delta) > 8) { delta = 0; return; } go(index+1); });

    // Initialize position to first slide
    setTransform(0, false);
    root.dataset.beslockInit = '1';
  }

  function tryInitAll(){
    const roots = document.querySelectorAll('.product-page__gallery');
    roots.forEach(r=> init(r));
  }

  document.addEventListener('DOMContentLoaded', tryInitAll);
  window.addEventListener('load', tryInitAll);
  // mutation observer for late-inserted galleries
  const mo = new MutationObserver((m)=> tryInitAll());
  mo.observe(document.body, {childList:true, subtree:true});
  // immediate attempt
  tryInitAll();

})();

  /* New: stand-alone minimal gallery counter (robust, visible, independent)
     - Creates a single `.product-page__gallery-counter` inside any
       `.product-page__gallery-canonical`, `.product-page__gallery-wrapper` or
       `.product-page__gallery` found on product pages.
     - Uses IntersectionObserver to determine which slide is visible and
       updates `X / N` accordingly.
  */
  (function(){
    'use strict';
    function findCanonicalRoots(){
      return Array.from(document.querySelectorAll('.product-page__gallery-canonical, .product-page__gallery-wrapper, .product-page__gallery'));
    }

    function ensureCounterFor(root){
      if(!root) return;
      // avoid double-init
      if(root.dataset.beslockCounter === '1') return;
      const slides = Array.from(root.querySelectorAll('.product-page__gallery-slide, .woocommerce-product-gallery__image, .product-page__gallery-wrapper img'));
      if(!slides.length) return;
      // remove any existing counters to ensure single source
      Array.from(root.querySelectorAll('.product-page__gallery-counter')).forEach(n=>n.remove());

      const wrap = document.createElement('div'); wrap.className = 'product-page__gallery-counter';
      const text = document.createElement('span'); text.className = 'product-page__gallery-counter-text'; wrap.appendChild(text);
      // ensure parent is positioned so absolute inside it works
      if(getComputedStyle(root).position === 'static') root.style.position = 'relative';
      root.appendChild(wrap);

      function update(i){ text.textContent = (i+1) + ' / ' + slides.length; }

      let current = 0;
      update(0);
      const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if(entry.isIntersecting && entry.intersectionRatio > 0.45){
            const idx = slides.indexOf(entry.target);
            if(idx !== -1 && idx !== current){ current = idx; update(current); }
          }
        });
      }, {threshold: [0.45, 0.5]});

      slides.forEach(s=> io.observe(s));
      slides.forEach((s,i)=> s.addEventListener('click', ()=> { current = i; update(i); }));
      root.dataset.beslockCounter = '1';
      console.info('product-gallery-counter: initialized for', root, 'slides=', slides.length);
    }

    function initAll(){
      const roots = findCanonicalRoots();
      if(!roots.length) return;
      roots.forEach(r => ensureCounterFor(r));
    }

    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
    else initAll();
    // watch for dynamically-inserted galleries
    const mo = new MutationObserver(()=> initAll());
    mo.observe(document.body, {childList:true, subtree:true});
  })();
