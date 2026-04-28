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
        setTimeout(replacePlaceholders, 100);
      });
      mo.observe(gallery, { childList: true, subtree: true });
    }
  }catch(e){}

  // Intercept clicks on gallery anchors in capture phase to update href/data-* before lightbox handlers run
  document.addEventListener('click', function(ev){
    try{
      var a = ev.target.closest('.woocommerce div.product div.images .woocommerce-product-gallery__image a');
      if(!a) return;
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
  if(window.MutationObserver){
    var gallery = document.querySelector('.woocommerce div.product div.images');
    if(gallery){
      var mo2 = new MutationObserver(function(){ removeMagnifierTriggers(); });
      mo2.observe(gallery, { childList:true, subtree:true, attributes:true });
    }
  }
})();
