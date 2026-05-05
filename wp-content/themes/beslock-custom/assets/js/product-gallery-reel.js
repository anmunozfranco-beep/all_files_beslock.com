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
    canonical.style.overflow = 'hidden'; canonical.style.width = '100%'; canonical.style.position = 'relative';
    const reel = document.createElement('div'); reel.className = 'product-page__gallery-reel';
    reel.style.display='flex'; reel.style.flexWrap='nowrap'; reel.style.width='100%'; reel.style.transition='transform 420ms ease';
    canonical.appendChild(reel);
    root.insertBefore(canonical, root.firstChild);

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

  function attachCounter(root, total){
    // remove any existing counters anywhere to avoid duplicates
    try{ Array.from(document.querySelectorAll('.product-page__gallery-counter')).forEach(n=>n && n.remove()); }catch(e){}
    if(total <= 1) return null;
    const wrap = document.createElement('div'); wrap.className='product-page__gallery-counter';
    const span = document.createElement('span'); span.className='product-page__gallery-counter-text'; span.textContent = `1/${total}`; wrap.appendChild(span);
    // Prefer inserting inside canonical viewport so it's centered w.r.t the reel
    const canonical = root.querySelector('.product-page__gallery-canonical') || root;
    canonical.style.position = canonical.style.position || 'relative';
    canonical.appendChild(wrap);
    return span;
  }

  function init(root){
    if(!root || root.dataset.beslockInit === '1') return;
    const items = collectSources(root);
    if(!items || items.length === 0) return;
    const built = buildCanonical(root, items);
    const reel = built.reel; const slides = built.slides; const total = slides.length;
    const counter = attachCounter(root, total);

    let index = 0;
    const getW = ()=> Math.round((slides[0] && slides[0].getBoundingClientRect().width) || (root.getBoundingClientRect().width));
    function go(i){ index = ((i % total) + total) % total; reel.style.transform = `translateX(-${index * getW()}px)`; if(counter) counter.textContent = `${index+1}/${total}`; }
    window.addEventListener('resize', ()=> setTimeout(()=> go(index), 120));

    // pointer drag
    let down=false, startX=0, delta=0;
    reel.addEventListener('pointerdown', e=>{ down=true; startX=e.clientX; try{ reel.setPointerCapture && reel.setPointerCapture(e.pointerId);}catch(e){} reel.style.transition='none'; });
    reel.addEventListener('pointermove', e=>{ if(!down) return; delta = e.clientX - startX; reel.style.transform = `translateX(${ -index * getW() + delta }px)`; });
    reel.addEventListener('pointerup', e=>{ if(!down) return; down=false; reel.style.transition='transform 420ms ease'; try{ reel.releasePointerCapture && reel.releasePointerCapture(e.pointerId); }catch(e){} if(Math.abs(delta) > getW()*0.18){ if(delta>0) go(index-1); else go(index+1); } else { go(index); } delta=0; });
    reel.addEventListener('pointercancel', ()=>{ down=false; reel.style.transition='transform 420ms ease'; go(index); delta=0; });

    // click/tap advances to next slide (small affordance for non-drag interactions)
    reel.addEventListener('click', function(e){
      // ignore clicks that are part of a drag
      if(Math.abs(delta) > 8) { delta = 0; return; }
      go(index+1);
    });

    go(0);
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
