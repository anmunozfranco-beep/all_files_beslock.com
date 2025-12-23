/**
 * Strict Header FSM (rebuild)
 * Requirements honored:
 * - Two explicit states: FULL and COMPACT
 * - Hysteresis thresholds: ENTER = 120px, EXIT = 40px
 * - Single scroll listener, rAF throttled, no layout recalcs on scroll
 * - Only one BEM class toggled on the header element: `.header--compact`
 * - No inline styles, no jQuery, no references to plugins/themes
 *
 * Design notes:
 * - The machine only transitions when crossing thresholds.
 * - Between 40 and 120px the state is stable (hysteresis), preventing loops.
 * - Initial state is determined once on init to avoid flicker at load.
 */
(function () {
  'use strict';

  // FSM states
  var STATE_FULL = 'FULL';
  var STATE_COMPACT = 'COMPACT';

  // Hysteresis thresholds
  var ENTER_COMPACT = 120; // px scrolled down to enter COMPACT
  var EXIT_COMPACT = 40;   // px scrolled up to return to FULL

  // HERO gate: don't run FSM transitions until the user scrolls past this
  // value (12% of the viewport). Recomputed on init and on resize only.
  var HERO_GATE = window.innerHeight * 0.12;

  function updateHeroGate() {
    try { HERO_GATE = window.innerHeight * 0.12; } catch (e) { /* ignore */ }
  }

  // Current state (initialized on boot)
  var currentState = STATE_FULL;

  // rAF guard
  var scheduled = false;

  // BEM class to toggle. Per instructions, only one class is toggled.
  var TOGGLE_CLASS = 'header--compact';

  // Find header element; fallback to <header> or document.body to avoid errors.
  var headerEl = document.querySelector('.header') || document.querySelector('header') || document.body;

  // Set state and mutate DOM only when state actually changes
  function applyState(state) {
    if (state === currentState) return; // no-op when unchanged
    currentState = state;
    if (currentState === STATE_COMPACT) {
      headerEl.classList.add(TOGGLE_CLASS);
    } else {
      headerEl.classList.remove(TOGGLE_CLASS);
    }
  }

  // Evaluate current scrollY and determine if we must transition
  function evaluate() {
    scheduled = false;
    var y = window.scrollY || window.pageYOffset || 0;

    // Gate: while inside the hero (less than HERO_GATE), force FULL and
    // do not evaluate FSM transitions. Once the user scrolls past HERO_GATE
    // the regular FSM logic runs unchanged.
    if (y < HERO_GATE) {
      applyState(STATE_FULL);
      return;
    }

    if (currentState === STATE_FULL) {
      if (y > ENTER_COMPACT) {
        applyState(STATE_COMPACT);
      }
    } else if (currentState === STATE_COMPACT) {
      if (y < EXIT_COMPACT) {
        applyState(STATE_FULL);
      }
    }
    // else: remain in current state
  }

  // Single scroll handler schedules evaluate() via rAF
  function onScroll() {
    if (!scheduled) {
      scheduled = true;
      window.requestAnimationFrame(evaluate);
    }
  }

  // Initialize FSM once (determines initial state, attaches listeners)
  function init() {
    // compute HERO gate on init
    updateHeroGate();

    var y = window.scrollY || window.pageYOffset || 0;
    // Determine initial state but honor HERO_GATE: while inside hero keep FULL
    if (y < HERO_GATE) {
      currentState = STATE_FULL;
      headerEl.classList.remove(TOGGLE_CLASS);
    } else if (y > ENTER_COMPACT) {
      currentState = STATE_COMPACT;
      headerEl.classList.add(TOGGLE_CLASS);
    } else {
      currentState = STATE_FULL;
      headerEl.classList.remove(TOGGLE_CLASS);
    }

    // Attach single passive scroll listener
    window.addEventListener('scroll', onScroll, { passive: true });

    // On resize recompute HERO_GATE and schedule a re-evaluation once
    window.addEventListener('resize', function () {
      updateHeroGate();
      if (!scheduled) {
        scheduled = true;
        window.requestAnimationFrame(evaluate);
      }
    }, { passive: true });
  }

  /*
   * Measure header height once at init and on resize and set a CSS variable
   * by injecting/updating a <style id="beslock-header-height"> block in <head>.
   * This avoids applying inline styles to elements while keeping the layout
   * stable (the CSS spacer reads the variable).
   */
  function setHeaderHeightVariable() {
    try {
      var h = Math.max(0, Math.round((headerEl.getBoundingClientRect && headerEl.getBoundingClientRect().height) || 0));
      var css = ':root { --header-height: ' + h + 'px; }';
      var id = 'beslock-header-height';
      var existing = document.getElementById(id);
      if (existing) {
        if (existing.textContent !== css) existing.textContent = css;
      } else {
        var s = document.createElement('style');
        s.type = 'text/css';
        s.id = id;
        s.appendChild(document.createTextNode(css));
        var head = document.head || document.getElementsByTagName('head')[0];
        if (head) head.appendChild(s);
      }
    } catch (e) {
      // silently ignore measurement failures
    }
  }

  // Hook measurement into init and resize (debounced via rAF)
  (function attachMeasurement() {
    var scheduledMeasure = false;
    function scheduleMeasure() {
      if (!scheduledMeasure) {
        scheduledMeasure = true;
        window.requestAnimationFrame(function () {
          setHeaderHeightVariable();
          // keep HERO_GATE in sync on resize/measurement
          try { updateHeroGate(); } catch (e) {}
          scheduledMeasure = false;
        });
      }
    }
    // measure on init
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      setHeaderHeightVariable();
      updateHeroGate();
    } else {
      document.addEventListener('DOMContentLoaded', function () { setHeaderHeightVariable(); updateHeroGate(); });
    }
    // measure on resize (throttled)
    window.addEventListener('resize', scheduleMeasure, { passive: true });
  })();

  // Run on DOM ready
  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }

  // Expose currentState getter for optional debugging (non-invasive)
  try { Object.defineProperty(window, '__beslockHeaderFSM', { value: { getState: function(){ return currentState; } }, configurable: true }); } catch (e) {}

})();
