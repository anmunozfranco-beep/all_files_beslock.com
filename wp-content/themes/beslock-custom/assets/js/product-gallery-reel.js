(function(){
  'use strict';

  // Enhancer for server-rendered `beslock-gallery-reel` slides
  function enhanceReel(){
    try{
      var reel = document.querySelector('.beslock-gallery-reel');
      if(!reel) return;
      // Add keyboard navigation (left/right)
      if(!reel.__beslockEnhanced){
        reel.__beslockEnhanced = true;
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
        }, 60);
      }
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
