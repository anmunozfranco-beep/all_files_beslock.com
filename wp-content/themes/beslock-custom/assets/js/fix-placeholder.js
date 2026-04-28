(function(){
  function isPlaceholderSrc(src){
    if(!src) return false;
    return /lupa|magnif|magnifier|magnifying|search|lens|placeholder/i.test(src);
  }

  function imageAspectIsOdd(img){
    try{
      if(!img.naturalWidth || !img.naturalHeight) return false;
      var r = img.naturalWidth / img.naturalHeight;
      return r > 5 || r < 0.2;
    }catch(e){return false}
  }

  function findReplacement(){
    // Prefer featured image in gallery (wp-post-image)
    var feat = document.querySelector('.woocommerce div.product div.images img.wp-post-image');
    if(feat && !isPlaceholderSrc(feat.src) && !imageAspectIsOdd(feat)) return feat;
    // Otherwise look in product thumbnails
    var thumbs = document.querySelectorAll('.woocommerce div.product div.images .flex-control-thumbs img');
    for(var i=0;i<thumbs.length;i++){
      var t = thumbs[i];
      if(t && t.src && !isPlaceholderSrc(t.src) && !imageAspectIsOdd(t)) return t;
    }
    return null;
  }

  function replacePlaceholders(){
    var imgs = document.querySelectorAll('.woocommerce div.product div.images .woocommerce-product-gallery__image img');
    if(!imgs || imgs.length===0) return;
    var replacement = findReplacement();
    imgs.forEach(function(img){
      var src = img.getAttribute('src') || '';
      if(isPlaceholderSrc(src) || imageAspectIsOdd(img) || img.closest('.woocommerce-product-gallery__image--placeholder')){
        if(replacement){
          // copy src and srcset if available
          img.src = replacement.src;
          var rs = replacement.getAttribute('srcset');
          if(rs) img.setAttribute('srcset', rs);
          // also try to copy sizes
          var sz = replacement.getAttribute('sizes');
          if(sz) img.setAttribute('sizes', sz);
          img.style.objectFit = 'contain';
          // If image is wrapped by an anchor (photoswipe), update its href and data attributes
          var a = img.closest('a');
          try{
            if(a){
              a.href = replacement.src;
              a.setAttribute('data-large_image', replacement.src);
              a.setAttribute('data-large_image_width', replacement.naturalWidth || '');
              a.setAttribute('data-large_image_height', replacement.naturalHeight || '');
              // some themes/plugins use data-src or data-full-size
              a.setAttribute('data-src', replacement.src);
              a.setAttribute('data-full-size', replacement.src);
            }
          }catch(e){}
        }
      }
    });
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){ setTimeout(replacePlaceholders, 250); });
  } else {
    setTimeout(replacePlaceholders, 250);
  }

  // Re-run replacement when gallery nodes change (Photoswipe may re-render thumbnails)
  try{
    var gallery = document.querySelector('.woocommerce div.product div.images .woocommerce-product-gallery');
    if(gallery && window.MutationObserver){
      var mo = new MutationObserver(function(){
        setTimeout(function(){ replacePlaceholders(); if(typeof makeAnchorsInert === 'function') try{ makeAnchorsInert(); }catch(e){} }, 100);
      });
      mo.observe(gallery, { childList: true, subtree: true });
    }
  }catch(e){}

  // Intercept clicks on gallery anchors in capture phase to update href/data-* before lightbox handlers run
  // Use a broader anchor detection and ensure the link is within the product images container
  document.addEventListener('click', function(ev){
    try{
      var a = ev.target.closest('a');
      if(!a) return;
      var imagesWrap = a.closest('.woocommerce div.product div.images');
      if(!imagesWrap) return;
      var replacement = findReplacement();
      if(replacement){
        a.href = replacement.src;
        a.setAttribute('data-large_image', replacement.src);
        a.setAttribute('data-large_image_width', replacement.naturalWidth || '');
        a.setAttribute('data-large_image_height', replacement.naturalHeight || '');
        a.setAttribute('data-src', replacement.src);
        a.setAttribute('data-full-size', replacement.src);
        var imgInA = a.querySelector('img');
        if(imgInA){
          imgInA.src = replacement.src;
          var rs = replacement.getAttribute('srcset'); if(rs) imgInA.setAttribute('srcset', rs);
        }
      }
    }catch(e){}
  }, true);

  // If the anchor navigates directly to the image file (no lightbox present),
  // prevent default navigation and open a simple fullscreen overlay that
  // closes when clicked anywhere.
  (function(){
    var overlay = null;
    function openFullscreen(src){
      closeFullscreen();
      overlay = document.createElement('div');
      overlay.className = 'beslock-fullscreen-overlay';
      overlay.style.position = 'fixed';
      overlay.style.left = 0;
      overlay.style.top = 0;
      overlay.style.width = '100%';
      overlay.style.height = '100%';
      overlay.style.background = 'rgba(8,8,8,0.95)';
      overlay.style.display = 'flex';
      overlay.style.alignItems = 'center';
      overlay.style.justifyContent = 'center';
      overlay.style.zIndex = 999999;
      overlay.style.cursor = 'zoom-out';

      var img = document.createElement('img');
      img.src = src;
      img.style.maxWidth = '100%';
      img.style.maxHeight = '100%';
      img.style.objectFit = 'contain';
      img.alt = '';

      overlay.appendChild(img);

      overlay.addEventListener('click', function(){ closeFullscreen(); });
      document.addEventListener('keydown', onKeyDown);
      document.body.appendChild(overlay);
      // prevent body scroll while open
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';
    }

    function closeFullscreen(){
      if(overlay && overlay.parentNode){
        overlay.parentNode.removeChild(overlay);
      }
      overlay = null;
      document.removeEventListener('keydown', onKeyDown);
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
    }

    function onKeyDown(e){
      if(e.key === 'Escape' || e.keyCode === 27){ closeFullscreen(); }
    }

    // Capture-phase listener to stop navigation to image files and open overlay
    // Broader detection: intercept any anchor inside the product that points to uploads
    document.addEventListener('click', function(ev){
      try{
        var a = ev.target.closest('a');
        if(!a) return;
        var inProduct = a.closest('.woocommerce div.product');
        if(!inProduct) return;
        // If link points to an uploads image (same origin) and no lightbox is attached,
        // open our fullscreen overlay instead of navigating.
        var href = a.getAttribute('href') || a.href || '';
        if(!href) return;
        // Only intercept if href points to /wp-content/uploads/ (image file)
        if(href.indexOf('/wp-content/uploads/') !== -1){
          ev.preventDefault();
          ev.stopPropagation();
          openFullscreen(href);
        }
      }catch(e){}
    }, true);
  })();

  // Remove/hide the photoswipe trigger element (magnifier) if present,
  // and remove any emoji-based magnifier images that may have been injected.
  function removeMagnifierTriggers(){
    try{
      var triggers = document.querySelectorAll('.woocommerce div.product div.images .woocommerce-product-gallery__trigger');
      triggers.forEach(function(t){
        // try to remove from DOM; if not possible, hide it
        if(t.parentNode) t.parentNode.removeChild(t);
        else t.style.display = 'none';
      });

      // Remove emoji images commonly used for magnifier (core emoji svg)
      var emojiImgs = document.querySelectorAll('.woocommerce div.product div.images img.emoji, .woocommerce div.product div.images img[src*="emoji"]');
      emojiImgs.forEach(function(e){ if(e.parentNode) e.parentNode.removeChild(e); });
    }catch(e){}
  }

  // Run once and also after gallery mutations
  try{ removeMagnifierTriggers(); }catch(e){}
  // Make anchors to uploads inert so tapping thumbnails doesn't navigate away
  function makeAnchorsInert(){
    try{
      var anchors = document.querySelectorAll('.woocommerce div.product div.images a');
      anchors.forEach(function(a){
        try{
          var href = a.getAttribute('href') || a.href || '';
          if(href && href.indexOf('/wp-content/uploads/') !== -1){
            if(a.getAttribute('data-beslock-disabled')) return;
            a.setAttribute('data-beslock-disabled', '1');
            try{ a.removeAttribute('href'); }catch(e){}
            a.style.cursor = 'default';
            a.addEventListener('click', function(ev){ ev.preventDefault(); ev.stopPropagation(); }, true );
          }
        }catch(e){}
      });
    }catch(e){}
  }
  try{ makeAnchorsInert(); }catch(e){}
  
    // Stronger: replace anchor wrappers around product images with non-clickable spans
    // This removes navigation entirely by replacing <a>...</a> with <span>...</span>
    function replaceAnchorsWithSpan(){
      try{
        var imgs = document.querySelectorAll('.woocommerce div.product div.images img');
        imgs.forEach(function(img){
          try{
            var a = img.closest && img.closest('a');
            if(!a) return;
            var href = a.getAttribute('href') || a.href || '';
            if(href.indexOf('/wp-content/uploads/') === -1) return;
            if(a.getAttribute('data-beslock-replaced')) return;
            // create span and move children
            var span = document.createElement('span');
            span.className = a.className || '';
            span.setAttribute('data-beslock-replaced','1');
            // copy inline style if exists
            if(a.getAttribute('style')) span.setAttribute('style', a.getAttribute('style'));
            while(a.firstChild){ span.appendChild(a.firstChild); }
            a.parentNode.replaceChild(span, a);
          }catch(e){}
        });
      }catch(e){}
    }
    try{ replaceAnchorsWithSpan(); }catch(e){}
  if(window.MutationObserver){
    var gallery = document.querySelector('.woocommerce div.product div.images');
    if(gallery){
      var mo2 = new MutationObserver(function(){ removeMagnifierTriggers(); });
      mo2.observe(gallery, { childList:true, subtree:true, attributes:true });
    }
  }

  // Observe product area for new anchors and make them inert
  if(window.MutationObserver){
    try{
      var productArea = document.querySelector('.woocommerce div.product');
      if(productArea){
        var mo3 = new MutationObserver(function(){ try{ makeAnchorsInert(); }catch(e){} });
        mo3.observe(productArea, { childList:true, subtree:true, attributes:true });
      }
    }catch(e){}
  }

  /* Lightbox overlay click-to-close helper:
     When a gallery lightbox/overlay element appears, attach a click handler
     to the overlay root so a click anywhere will attempt to close the lightbox.
  */
  function attemptCloseLightbox(){
    var selectors = ['.pswp__button--close', '.mfp-close', '.fancybox-button--close', '.fancybox-close', '.pswp button.pswp__button--close', '.pswp__button--close'];
    var closed = false;
    for(var i=0;i<selectors.length;i++){
      var el = document.querySelector(selectors[i]);
      if(el){ try{ el.click(); closed = true; }catch(e){} }
    }
    if(!closed){
      // dispatch ESC
      var ev = new KeyboardEvent('keydown',{key:'Escape',keyCode:27,which:27,bubbles:true});
      document.dispatchEvent(ev);
    }
    // Last resort: remove overlay nodes
    var ps = document.querySelectorAll('.pswp, .mfp-wrap, .fancybox-container');
    ps.forEach(function(p){ if(p.parentNode) p.parentNode.removeChild(p); });
  }

  function attachOverlayClose(el){
    if(!el) return;
    if(el.__beslockCloseAttached) return;
    el.__beslockCloseAttached = true;
    el.addEventListener('click', function(ev){
      // If click is on an actionable child (like next/prev controls), ignore
      var actionable = ev.target.closest('button, a, .pswp__button, .fancybox-button, .mfp-arrow');
      if(actionable) return;
      attemptCloseLightbox();
    }, { capture: true });
  }

  if(window.MutationObserver){
    var bodyMo = new MutationObserver(function(mutations){
      mutations.forEach(function(m){
        if(m.addedNodes && m.addedNodes.length){
          Array.prototype.forEach.call(m.addedNodes, function(n){
            if(!(n instanceof Element)) return;
            if(n.classList && (n.classList.contains('pswp') || n.classList.contains('mfp-wrap') || n.classList.contains('fancybox-container') || n.getAttribute && n.getAttribute('role')==='dialog')){
              attachOverlayClose(n);
            }
            // Also support descendants that act as overlays
            var overlay = n.querySelector && n.querySelector('.pswp, .mfp-wrap, .fancybox-container, [role="dialog"]');
            if(overlay) attachOverlayClose(overlay);
          });
        }
      });
    });
    bodyMo.observe(document.body, { childList:true, subtree:true });
  }
})();
