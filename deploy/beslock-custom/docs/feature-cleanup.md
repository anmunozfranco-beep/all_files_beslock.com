Feature block cleanup

What I changed

- Removed `!important` from all rules that target the feature block subtree (`.hero-slide .features-wrapper...`) in `assets/css/main.css`.
- Added high-specificity overrides at the end of `main.css` earlier so visual behavior is preserved without needing `!important`.

Why

- `!important` made it hard to override styles safely and required more `!important` elsewhere. Using BEM and higher-specificity, ordered rules preserves the same visuals while allowing future CSS to override normally.

Files modified

- `assets/css/main.css` — removed `!important` in feature-related blocks and relied on appended overrides.
- `docs/feature-cleanup.md` — this document.

Notes

- I left `!important` in other unrelated files (e.g. `models-mobile.css`, `wc-scope-fix.css`) untouched.
- After deploying, verify in staging and then we can remove the appended high-specificity overrides if desired and rely on normal specificity.

How to verify locally

1. Hard refresh the page (Ctrl/Cmd+Shift+R) to ensure CSS is reloaded.
2. Inspect a feature icon and its computed styles — sizes should be 50px on desktop and 36px on mobile; text sizes as adjusted.
3. Confirm no `!important` flags remain in the feature rules (DevTools -> matched rules).
