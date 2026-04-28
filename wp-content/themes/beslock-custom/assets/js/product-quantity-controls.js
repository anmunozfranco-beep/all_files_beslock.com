/* Inject simple accessible +/- quantity controls around WooCommerce quantity inputs
 * - finds `.quantity input.qty` inside `.cart` forms and wraps with .product-quantity-wrap
 * - adds buttons with class .qty-btn and updates value respecting min/step/max
 */
(function(){
  'use strict';
  function enhance(root){
    var scope = root || document;
    var qtyInputs = Array.prototype.slice.call(scope.querySelectorAll('form.cart .quantity input.qty'));
    qtyInputs.forEach(function(input){
      if(!input) return;
      var parent = input.parentElement;
      if(parent && parent.classList.contains('product-quantity-wrap')) return; // already enhanced

      // create wrapper and buttons
      var wrap = document.createElement('div'); wrap.className = 'product-quantity-wrap';
      var btnDec = document.createElement('button'); btnDec.type='button'; btnDec.className='qty-btn qty-decrease'; btnDec.setAttribute('aria-label','Disminuir cantidad'); btnDec.textContent='−';
      var btnInc = document.createElement('button'); btnInc.type='button'; btnInc.className='qty-btn qty-increase'; btnInc.setAttribute('aria-label','Aumentar cantidad'); btnInc.textContent='+';

      // move input into wrapper
      parent.replaceChild(wrap, input);
      wrap.appendChild(btnDec);
      wrap.appendChild(input);
      wrap.appendChild(btnInc);

      function getStep(){ var s = parseFloat(input.getAttribute('step')) || 1; return s; }
      function getMin(){ var m = input.getAttribute('min'); return (m === null || m === '') ? 1 : parseFloat(m); }
      function getMax(){ var M = input.getAttribute('max'); return (M === null || M === '') ? Infinity : parseFloat(M); }

      function setVal(v){ var step = getStep(); var min = getMin(); var max = getMax(); var val = Math.round(v/step)*step; val = Math.max(min, Math.min(max, val)); input.value = val; input.dispatchEvent(new Event('change', { bubbles:true })); }

      btnDec.addEventListener('click', function(e){ e.preventDefault(); var v = parseFloat(input.value) || 0; setVal(v - getStep()); });
      btnInc.addEventListener('click', function(e){ e.preventDefault(); var v = parseFloat(input.value) || 0; setVal(v + getStep()); });

      // keyboard support on input already exists; ensure buttons are reachable
    });
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ enhance(document); }); else enhance(document);
  // expose init for dynamic content
  window.__beslock_qty_enhance = enhance;
})();
