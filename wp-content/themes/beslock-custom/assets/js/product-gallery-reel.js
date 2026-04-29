(function(){
  'use strict';

  // Enhancer for server-rendered `beslock-gallery-reel` slides
  function enhanceReel(){
    try{
      var reel = document.querySelector('.beslock-gallery-reel');
      if(!reel) return;
      if(reel.__beslockEnhanced) return;
      reel.__beslockEnhanced = true;
      // Wrap reel in a nav container for buttons
      var wrapper = document.createElement('div');
      wrapper.className = 'beslock-gallery-nav';
      reel.parentNode.insertBefore(wrapper, reel);
      wrapper.appendChild(reel);

      // No lateral prev/next buttons — navigation is tactical (swipe/scroll/dots)

      // Dots container
      var dots = document.createElement('div'); dots.className = 'beslock-gallery-dots';
      wrapper.parentNode.insertBefore(dots, wrapper.nextSibling);

      // Counter (1 / N)
      var counter = document.createElement('div'); counter.className = 'beslock-gallery-counter';
      dots.parentNode.insertBefore(counter, dots.nextSibling);

      var slides = Array.prototype.slice.call(reel.querySelectorAll('.beslock-gallery-slide'));
      slides.forEach(function(s, i){
        var d = document.createElement('button'); d.type = 'button'; d.setAttribute('data-index', i);
        if(i===0) d.className = 'active';
        d.addEventListener('click', function(){ scrollToSlide(i); });
        dots.appendChild(d);
        // Clicking a slide itself should jump to that slide (useful for thumbnails)
        s.style.cursor = 'pointer';
        s.addEventListener('click', function(e){
          try{
            if(i === 0) return; // main image: keep native behavior (e.g. lightbox)
            // Swap image attributes between main image (index 0) and this thumbnail
            var mainImg = slides[0].querySelector('img');
            var thumbImg = s.querySelector('img');
            if(mainImg && thumbImg){
              // Collect attributes
              var aAttrs = Array.prototype.slice.call(mainImg.attributes).map(function(a){ return {name:a.name, value:a.value}; });
              var bAttrs = Array.prototype.slice.call(thumbImg.attributes).map(function(a){ return {name:a.name, value:a.value}; });
              // Remove all attributes
              Array.prototype.slice.call(mainImg.attributes).forEach(function(a){ mainImg.removeAttribute(a.name); });
              Array.prototype.slice.call(thumbImg.attributes).forEach(function(a){ thumbImg.removeAttribute(a.name); });
              // Apply swapped attributes
              bAttrs.forEach(function(attr){ mainImg.setAttribute(attr.name, attr.value); });
              aAttrs.forEach(function(attr){ thumbImg.setAttribute(attr.name, attr.value); });
            }
            // Mark this thumbnail as active and update counter/dots
            setActive(i);
            // keep main image in view (center first slide)
            scrollToSlide(0);
            e.preventDefault();
          }catch(ex){ console.warn('thumb click swap error', ex); }
        });
      });

      // keyboard
      reel.tabIndex = 0;
      reel.addEventListener('keydown', function(e){
        if(e.key === 'ArrowRight'){ e.preventDefault(); goToRelative(1); }
        else if(e.key === 'ArrowLeft'){ e.preventDefault(); goToRelative(-1); }
      });

      // Prevent native lightbox or anchor navigation on desktop while keeping slide click handlers.
      // We only prevent the default action (navigation/lightbox trigger) for anchors inside the reel
      // on desktop widths so clicking still runs our slide handlers.
      reel.addEventListener('click', function(e){
        try{
          if(window.matchMedia && window.matchMedia('(min-width: 1024px)').matches){
            var a = e.target.closest && e.target.closest('a');
            if(a && reel.contains(a)){
              e.preventDefault();
              // do not stopPropagation so our slide click handlers still run
            }
          }
        }catch(ignore){}
      });

      // no button handlers (controls removed)

      // track active index
      var activeIndex = 0;
      function setActive(i){
        activeIndex = Math.max(0, Math.min(slides.length-1, i));
        var ds = dots.querySelectorAll('button');
        ds.forEach(function(b, idx){ b.classList.toggle('active', idx===activeIndex); });
        // update slide active class for subtle visual effect
        slides.forEach(function(s, idx){ s.classList.toggle('active', idx===activeIndex); });
        // update counter
        if(counter) counter.textContent = (activeIndex+1) + ' / ' + slides.length;
        // preload next image via link rel=preload for best effect
        try{
          var next = slides[activeIndex+1];
          if(next){
            var img = next.querySelector('img');
            if(img && img.src){
              // remove previous preload
              var old = document.head.querySelector('link[data-beslock-preload]');
              if(old) old.parentNode.removeChild(old);
              var l = document.createElement('link');
              l.rel = 'preload'; l.as = 'image'; l.href = img.src; l.setAttribute('data-beslock-preload','1');
              document.head.appendChild(l);
            }
          }
        }catch(e){}
      }

      function scrollToSlide(i){
        var s = slides[i];
        if(!s) return;
        var left = s.offsetLeft - (reel.clientWidth - s.clientWidth)/2;
        reel.scrollTo({ left: left, behavior: 'smooth' });
        setActive(i);
      }

      function goToRelative(delta){ scrollToSlide(activeIndex + delta); }

      // Update active on scroll (debounced - lower latency)
      var scrollTimeout = null;
      reel.addEventListener('scroll', function(){
        if(scrollTimeout) clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(function(){
          var center = reel.scrollLeft + reel.clientWidth/2;
          var closest = 0; var bestDiff = Infinity;
          slides.forEach(function(s, idx){
            var sCenter = s.offsetLeft + s.clientWidth/2;
            var diff = Math.abs(sCenter - center);
            if(diff < bestDiff){ bestDiff = diff; closest = idx; }
          });
          setActive(closest);
        }, 48);
      });

      // Pointer swipe handling
      var pointer = { down:false, startX:0, lastX:0, startTime:0 };
      reel.addEventListener('pointerdown', function(e){
        pointer.down = true; pointer.startX = e.clientX; pointer.lastX = e.clientX; pointer.startTime = Date.now();
        reel.setPointerCapture && reel.setPointerCapture(e.pointerId);
      }, { passive:false });
      reel.addEventListener('pointermove', function(e){ if(pointer.down) pointer.lastX = e.clientX; }, { passive:true });
      reel.addEventListener('pointerup', function(e){
        if(!pointer.down) return; pointer.down = false;
        var dx = e.clientX - pointer.startX; var dt = Date.now() - pointer.startTime;
        var vx = dx / Math.max(1, dt);
        var threshold = Math.min(reel.clientWidth * 0.18, 120);
        if(Math.abs(dx) > threshold || Math.abs(vx) > 0.5){
          if(dx < 0) goToRelative(1); else goToRelative(-1);
        } else {
          // snap to nearest
          var center = reel.scrollLeft + reel.clientWidth/2;
          var closest = 0; var bestDiff = Infinity;
          slides.forEach(function(s, idx){ var sCenter = s.offsetLeft + s.clientWidth/2; var diff = Math.abs(sCenter - center); if(diff < bestDiff){ bestDiff = diff; closest = idx; } });
          scrollToSlide(closest);
        }
      });

      // initial center
      setTimeout(function(){ scrollToSlide(0); }, 40);
    }catch(e){ console.warn('beslock reel enhance error', e); }
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', enhanceReel);
  } else {
    enhanceReel();
  }

  // Re-run enhancer when DOM mutates
  try{
    var gallery = document.querySelector('.woocommerce div.product div.images');
    if(gallery && window.MutationObserver){
      var mo = new MutationObserver(function(){ setTimeout(enhanceReel, 120); });
      mo.observe(gallery, { childList:true, subtree:true, attributes:true });
    }
  }catch(e){}

})();
