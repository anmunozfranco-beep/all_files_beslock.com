/* Accessible tabs for product page (mobile-first)
 * - toggles two panels: Especificaciones / Reviews
 * - supports click and keyboard (ArrowLeft/ArrowRight/Home/End)
 */
(function(){
  'use strict';
  function init(context){
    var root = context || document;
    var blocks = root.querySelectorAll('.product-tabs');
    if(!blocks || blocks.length===0) return;
    blocks.forEach(function(block){
      var tabs = Array.prototype.slice.call(block.querySelectorAll('[role="tab"]'));
      var panels = Array.prototype.slice.call(block.querySelectorAll('[role="tabpanel"]'));

      function activateTab(tab, focusPanel){
        tabs.forEach(function(t){
          var selected = t === tab;
          t.classList.toggle('product-tabs__tab--active', selected);
          t.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
        panels.forEach(function(p){
          var show = p.id === tab.getAttribute('aria-controls');
          if(show){
            p.removeAttribute('hidden');
            p.setAttribute('tabindex','0');
          } else {
            p.setAttribute('hidden','');
            p.removeAttribute('tabindex');
          }
        });
        // move focus into panel for keyboard users when requested by caller
        try{
          if (focusPanel) {
            var panel = document.getElementById(tab.getAttribute('aria-controls'));
            if(panel) panel.focus();
          }
        }catch(e){}
      }

      tabs.forEach(function(tab, idx){
        tab.addEventListener('click', function(e){ e.preventDefault(); activateTab(tab); });
        tab.addEventListener('keydown', function(e){
          var key = e.key || e.keyCode;
          if(key === 'ArrowRight' || key === 'Right' || key === 39){ e.preventDefault(); var next = tabs[(idx+1)%tabs.length]; next.focus(); activateTab(next, true); }
          if(key === 'ArrowLeft' || key === 'Left' || key === 37){ e.preventDefault(); var prev = tabs[(idx-1+tabs.length)%tabs.length]; prev.focus(); activateTab(prev, true); }
          if(key === 'Home' || key === 'Home' || key === 36){ e.preventDefault(); tabs[0].focus(); activateTab(tabs[0], true); }
          if(key === 'End' || key === 'End' || key === 35){ e.preventDefault(); tabs[tabs.length-1].focus(); activateTab(tabs[tabs.length-1], true); }
        });
      });

      // ensure initial state
      var active = block.querySelector('[role="tab"][aria-selected="true"]') || tabs[0];
      if(active) activateTab(active, false);
    });
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ init(document); }); else init(document);
  // expose for partial re-initialization
  window.__beslock_product_tabs_init = init;
})();
