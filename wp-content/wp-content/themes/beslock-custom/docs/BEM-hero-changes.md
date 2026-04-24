BEM changes for hero title/subtitle

What I changed

- Template: `templates/blocks/hero.php`
  - Replaced legacy classes `slide-title` and `slide-subtitle` with BEM classes `hero__title` and `hero__subtitle`.
  - File updated: lines ~119-120 (h1 and p elements).

- CSS: `assets/css/main.css`
  - Removed fallback selectors referencing `.slide-title` and `.slide-subtitle`.
  - All hero typography/animation rules now target `.hero__title` and `.hero__subtitle` exclusively.
  - Kept `.slide-content` and other layout selectors intact to avoid layout regressions.

Why

- Using BEM gives predictable specificity and makes future overrides safer without relying on `!important`.

Notes & Next steps

- JS: no references to `slide-title`/`slide-subtitle` were found in theme JS. If you have custom plugins or inline scripts, validate in staging.
- Verify in staging/local: hard-refresh (disable cache) after deploying to ensure CSS updates apply.
- If everything looks good, we can remove any remaining `slide-` comments or old assets.

Files edited

- `templates/blocks/hero.php`
- `assets/css/main.css`

If you want, I can also run a quick grep for any other `slide-` occurrences and clean them up.