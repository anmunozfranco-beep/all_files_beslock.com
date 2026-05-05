(function(){
  'use strict';

  function findGalleries(){
    return Array.from(document.querySelectorAll('.product-page__gallery'));
  }

  function ensureReel(root){
    if(!root) return null;
    let reel = root.querySelector('.product-page__gallery-reel');
    if(reel) return reel;
    reel = document.createElement('div');
    reel.className = 'product-page__gallery-reel';
    // move any existing images into the reel
    const imgs = Array.from(root.querySelectorAll('img'));
    imgs.forEach(img=>{
      const movable = img.closest('a') || img;
      const slide = document.createElement('div');
      slide.className = 'product-page__gallery-slide';
      // move the node
      slide.appendChild(movable);
      reel.appendChild(slide);
    });

    // remove legacy wrappers that may contain duplicate images (non-destructive)
    Array.from(root.querySelectorAll('.product-page__gallery-wrapper, .product-page__gallery-canonical')).forEach(n=>n.remove());

    root.appendChild(reel);
    return reel;
  }

  function buildUI(root, reel, slides){
    if(!root || !reel) return null;
    let ui = root.querySelector('.product-gallery__ui');
    if(ui) return ui;
    ui = document.createElement('div'); ui.className = 'product-gallery__ui';
    const dots = document.createElement('div'); dots.className = 'product-gallery__dots';
    const counter = document.createElement('div'); counter.className = 'product-gallery__counter';
    ui.appendChild(dots); ui.appendChild(counter);
    root.appendChild(ui);

    slides.forEach((slide, i)=>{
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'product-gallery__dot';
      dot.setAttribute('aria-label', 'Go to slide ' + (i+1));
      dot.addEventListener('click', ()=>{
        // scroll to slide
        if(typeof slide.scrollIntoView === 'function') slide.scrollIntoView({behavior:'smooth', inline:'center'});
        else reel.scrollTo({left: slide.offsetLeft, behavior: 'smooth'});
      });
      dots.appendChild(dot);
    });

    return ui;
  }

  function updateUI(root, reel, slides){
    const ui = root.querySelector('.product-gallery__ui');
    if(!ui) return;
    const dots = Array.from(ui.querySelectorAll('.product-gallery__dot'));
    const counter = ui.querySelector('.product-gallery__counter');
    const cw = reel.clientWidth || 1;
    const idx = Math.round(reel.scrollLeft / cw);
    const index = Math.max(0, Math.min(slides.length-1, idx));
    dots.forEach((d, i)=> d.classList.toggle('is-active', i === index));
    if(counter) counter.textContent = (index+1) + ' / ' + slides.length;
    // hide UI when not needed
    ui.style.display = (slides.length <= 1) ? 'none' : 'flex';
  }

  function initGallery(root){
    if(!root || root.dataset.beslockInit === '1') return;
    const reel = ensureReel(root);
    if(!reel) return;
    // ensure slides are direct children (in case some existed already)
    const slides = Array.from(reel.children).filter(c => c.matches && c.matches('.product-page__gallery-slide'));
    // ensure native touch-action and smooth scrolling (allow horizontal swipe)
    try{ reel.style.touchAction = 'pan-x pan-y'; }catch(e){}

    // sanitize anchors/images to prevent fullscreen/lightbox and disable drag
    Array.from(reel.querySelectorAll('a')).forEach(a=>{
      try{
        if(a.hasAttribute('href')){
          a.setAttribute('data-beslock-href', a.getAttribute('href'));
          a.removeAttribute('href');
        }
        // remove common lightbox attributes (non-destructive)
        ['data-fancybox','data-lightbox','data-mfp','data-pswp-uid','rel','data-gallery'].forEach(attr=> a.removeAttribute(attr));
        a.addEventListener('click', function(ev){ ev.preventDefault(); ev.stopImmediatePropagation(); }, {passive:false});
        a.addEventListener('touchstart', function(ev){ /* noop to prioritize touch */ }, {passive:true});
      }catch(e){ /* ignore */ }
    });

    Array.from(reel.querySelectorAll('img')).forEach(img=>{
      try{
        img.draggable = false;
        img.addEventListener('dragstart', function(ev){ ev.preventDefault(); }, {passive:false});
        // ensure pointer-events remain on images
        img.style.userSelect = 'none';
        img.style.webkitUserDrag = 'none';
      }catch(e){}
    });
    // build UI (dots + counter)
    buildUI(root, reel, slides);
    // initial update
    updateUI(root, reel, slides);

    // sync on scroll with rAF throttle
    let rafPending = false;
    function onScroll(){
      if(rafPending) return;
      rafPending = true;
      requestAnimationFrame(()=>{ updateUI(root, reel, slides); rafPending = false; });
    }
    reel.addEventListener('scroll', onScroll, {passive:true});

    // update on resize (slides width may change)
    const ro = new ResizeObserver(()=> updateUI(root, reel, slides));
    ro.observe(reel);

    // ensure dots reflect current position on load
    setTimeout(()=> updateUI(root, reel, slides), 120);

    root.dataset.beslockInit = '1';
    console.info('product-gallery: initialized', root, 'slides=', slides.length);
  }

  function initAll(){
    const roots = findGalleries();
    roots.forEach(r=> initGallery(r));
  }

  /* Hide product excerpt on mobile via inline style to ensure override
     This is a defensive fix in case theme CSS loads after our stylesheet. */
  (function hideExcerptOnMobile(){
    const selectors = [
      '.single-product .product-page__excerpt',
      '.single-product .woocommerce-product-details__short-description',
      '.single-product .summary .woocommerce-product-details__short-description'
    ];
    const els = selectors.map(s => Array.from(document.querySelectorAll(s))).flat();
    if(!els.length) return;
    let raf;
    function apply(){
      const hide = window.innerWidth < 768;
      els.forEach(el => { el.style.display = hide ? 'none' : ''; });
    }
    window.addEventListener('resize', ()=>{ if(raf) cancelAnimationFrame(raf); raf = requestAnimationFrame(apply); }, {passive:true});
    try{ apply(); }catch(e){}
  })();

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll); else initAll();
  window.addEventListener('load', initAll);
  const mo = new MutationObserver(()=> initAll());
  mo.observe(document.body, {childList:true, subtree:true});

})();
