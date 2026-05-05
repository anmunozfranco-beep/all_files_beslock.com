(function(){
  'use strict';

  // Idempotent DOM restructure: ensure all images inside .product-page__gallery
  // are moved into a single .product-page__gallery-reel and wrapped as slides.
  function restructureGallery(root){
    if(!root) return;
    if(root.dataset.beslockReflow === '1') return;

    // find or create reel
    let reel = root.querySelector('.product-page__gallery-reel');
    if(!reel){
      reel = document.createElement('div');
      reel.className = 'product-page__gallery-reel';
      root.appendChild(reel);
    }

    // collect images inside root but not already inside reel
    const imgs = Array.from(root.querySelectorAll('img'));
    imgs.forEach(img=>{
      if(reel.contains(img)) return; // already moved
      // prefer moving the anchor wrapper if present
      const movable = img.closest('a') || img;
      // create slide wrapper
      const slide = document.createElement('div');
      slide.className = 'product-page__gallery-slide';
      // move node
      try{
        slide.appendChild(movable);
      }catch(e){
        // fallback: move image only
        if(movable !== img && movable.contains && movable.contains(img)){
          slide.appendChild(img);
          if(movable.parentElement && movable.parentElement.childNodes.length === 0) movable.parentElement.remove();
        }
      }
      reel.appendChild(slide);
    });

    // remove any stray direct img children of root
    Array.from(root.querySelectorAll(':scope > img')).forEach(i=> i.remove());

    root.dataset.beslockReflow = '1';
  }

  function findRoots(){
    return Array.from(document.querySelectorAll('.product-page__gallery, .product-page__gallery-wrapper, .woocommerce-product-gallery'));
  }

  function getSlides(root){
    return Array.from(root.querySelectorAll('.product-page__gallery-reel > .product-page__gallery-slide, .product-page__gallery-reel > .woocommerce-product-gallery__image'));
  }

  function ensureTracking(root){
    if(!root || root.dataset.beslockInit === '1') return;
    restructureGallery(root);
    const slides = getSlides(root);
    if(!slides.length) return;

    const track = root.querySelector('.product-page__gallery-reel') || root.querySelector('.woocommerce-product-gallery__wrapper') || root.querySelector('.product-page__gallery-wrapper') || root;
    let current = 0; const total = slides.length;

    const io = new IntersectionObserver((entries)=>{
      entries.forEach(entry=>{
        if(entry.isIntersecting && entry.intersectionRatio > 0.5){
          const idx = slides.indexOf(entry.target);
          if(idx !== -1 && idx !== current){
            current = idx;
            const ev = new CustomEvent('beslock:gallery:change', {detail:{index: current, total}});
            root.dispatchEvent(ev);
          }
        }
      });
    }, {threshold:[0.5], root: track});

    slides.forEach(s=> io.observe(s));

    function preventFullscreenClick(e){
      const tgt = e.target;
      const a = tgt.closest && tgt.closest('a');
      if(a && a.getAttribute && a.getAttribute('href')){
        e.preventDefault(); e.stopPropagation(); root.dispatchEvent(new CustomEvent('beslock:gallery:tap', {detail:{target: tgt}}));
      } else if(tgt && tgt.tagName === 'IMG'){
        e.preventDefault(); e.stopPropagation(); root.dispatchEvent(new CustomEvent('beslock:gallery:tap', {detail:{target: tgt}}));
      }
    }

    // attach handler on the track with capture to intercept theme/lightbox handlers
    track.addEventListener('click', preventFullscreenClick, true);

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
