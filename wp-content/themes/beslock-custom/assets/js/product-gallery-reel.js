(function(){
  'use strict';

  function getImageSourcesFromGallery(){
    var imgs = document.querySelectorAll('.woocommerce div.product div.images img');
    var seen = {};
    var list = [];
    imgs.forEach(function(img){
      try{
        var src = img.getAttribute('data-large_image') || img.getAttribute('data-src') || img.getAttribute('src') || '';
        if(!src) return;
        if(seen[src]) return;
        seen[src] = true;
        var srcset = img.getAttribute('srcset') || '';
        var sizes = img.getAttribute('sizes') || '';
        list.push({ src: src, srcset: srcset, sizes: sizes, alt: img.getAttribute('alt') || '' });
      }catch(e){}
    });
    return list;
  }

  function buildReel(images){
    if(!images || images.length===0) return null;
    var reel = document.createElement('div');
    reel.className = 'beslock-gallery-reel';
    reel.setAttribute('role','list');
    images.forEach(function(imgData, idx){
      var slide = document.createElement('div');
      slide.className = 'beslock-gallery-slide';
      slide.setAttribute('role','listitem');
      var img = document.createElement('img');
      img.src = imgData.src;
      if(imgData.srcset) img.setAttribute('srcset', imgData.srcset);
      if(imgData.sizes) img.setAttribute('sizes', imgData.sizes);
      img.alt = imgData.alt || '';
      img.loading = 'lazy';
      slide.appendChild(img);
      reel.appendChild(slide);
    });
    return reel;
  }

  function initReel(){
    try{
      if(!document.querySelector('.woocommerce div.product')) return;
      var images = getImageSourcesFromGallery();
      if(!images || images.length===0) return;
      // remove existing bespoke reel if present
      var existing = document.querySelector('.beslock-gallery-reel');
      if(existing) existing.parentNode.removeChild(existing);

      var galleryWrap = document.querySelector('.woocommerce div.product div.images');
      if(!galleryWrap) return;

      var reel = buildReel(images);
      if(!reel) return;

      // Insert reel after galleryWrap
      galleryWrap.parentNode.insertBefore(reel, galleryWrap.nextSibling);

      // Optionally hide original gallery thumbnails to avoid duplication
      galleryWrap.style.display = 'none';

      // Add keyboard navigation (left/right)
      reel.tabIndex = 0;
      reel.addEventListener('keydown', function(e){
        if(e.key === 'ArrowRight'){
          e.preventDefault();
          reel.scrollBy({ left: window.innerWidth * 0.9, behavior: 'smooth' });
        } else if(e.key === 'ArrowLeft'){
          e.preventDefault();
          reel.scrollBy({ left: -window.innerWidth * 0.9, behavior: 'smooth' });
        }
      });

      // Ensure first slide is centered
      setTimeout(function(){
        var first = reel.querySelector('.beslock-gallery-slide');
        if(first) first.scrollIntoView({ inline: 'center', behavior: 'smooth' });
      }, 120);
    }catch(e){ console.warn('beslock reel init error', e); }
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', initReel);
  } else {
    initReel();
  }

  // Re-init when gallery mutates
  try{
    var gallery = document.querySelector('.woocommerce div.product div.images');
    if(gallery && window.MutationObserver){
      var mo = new MutationObserver(function(){ setTimeout(initReel, 120); });
      mo.observe(gallery, { childList:true, subtree:true, attributes:true });
    }
  }catch(e){}

})();
