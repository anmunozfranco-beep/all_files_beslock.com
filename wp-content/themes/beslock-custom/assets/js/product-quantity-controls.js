/* Product quantity controls
 * - Inserts - and + buttons around quantity inputs on single product add-to-cart forms
 * - Buttons have same height as input, respect min/max/step, trigger change events
 */
(function(){
  'use strict';
  function init(root){
    root = root || document;
    var qtyWrappers = root.querySelectorAll('form.cart .quantity');
    if(!qtyWrappers || qtyWrappers.length===0) return;
    qtyWrappers.forEach(function(wrap){
      if(wrap.classList.contains('beslock-qty-init')) return;
      var input = wrap.querySelector('input.qty');
      if(!input) return;
      // create container
      var container = document.createElement('div');
      container.className = 'beslock-qty';

      // create buttons
      var btnMinus = document.createElement('button'); btnMinus.type='button'; btnMinus.className='beslock-qty-minus'; btnMinus.setAttribute('aria-label','Disminuir cantidad'); btnMinus.textContent='−';
      var btnPlus = document.createElement('button'); btnPlus.type='button'; btnPlus.className='beslock-qty-plus'; btnPlus.setAttribute('aria-label','Aumentar cantidad'); btnPlus.textContent='+';

      // move input into container and insert buttons
      input.parentNode.insertBefore(container, input);
      container.appendChild(btnMinus);
      container.appendChild(input);
      container.appendChild(btnPlus);

      // mark initialized
      wrap.classList.add('beslock-qty-init');

      // ensure quantity and add-to-cart button are grouped for precise centering
      try{
        var form = wrap.closest('form.cart');
        if(form){
          var existing = form.querySelector('.beslock-buy-controls');
          if(!existing){
            var container = document.createElement('div');
            container.className = 'beslock-buy-controls';
            // place container before quantity wrapper
            form.insertBefore(container, form.querySelector('.quantity'));
            // move quantity and the add-to-cart button into container
            var qtyNode = form.querySelector('.quantity');
            var btn = form.querySelector('.single_add_to_cart_button');
            if(qtyNode) container.appendChild(qtyNode);
            if(btn) container.appendChild(btn);
          }
        }
      }catch(e){}

      // helpers
      function parseVal(){ var v = parseFloat(input.value); return isNaN(v)? 0 : v; }
      function stepVal(){ var s = parseFloat(input.getAttribute('step')) || 1; return s; }
      function minVal(){ var m = input.getAttribute('min'); return (m !== null) ? parseFloat(m) : null; }
      function maxVal(){ var M = input.getAttribute('max'); return (M !== null) ? parseFloat(M) : null; }

      function setVal(v){ var s = stepVal(); v = Math.round(v / s) * s; var mn = minVal(); var mx = maxVal(); if(mn !== null) v = Math.max(v, mn); if(mx !== null) v = Math.min(v, mx); input.value = v; input.dispatchEvent(new Event('change',{bubbles:true})); }

      btnMinus.addEventListener('click', function(e){ e.preventDefault(); var v = parseVal() - stepVal(); setVal(v); input.focus(); });
      btnPlus.addEventListener('click', function(e){ e.preventDefault(); var v = parseVal() + stepVal(); setVal(v); input.focus(); });

      // improve keyboard support on input (arrow keys)
      input.addEventListener('keydown', function(e){ if(e.key==='ArrowUp'){ e.preventDefault(); btnPlus.click(); } if(e.key==='ArrowDown'){ e.preventDefault(); btnMinus.click(); } });
    });
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', function(){ init(document); }); else init(document);
  window.__beslock_qty_init = init;
})();
