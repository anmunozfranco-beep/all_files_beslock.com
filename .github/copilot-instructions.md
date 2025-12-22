# Copilot instructions — beslock.com.co (WordPress site)

Purpose: help AI coding agents be immediately productive when working on this WordPress codebase, while preserving architectural and design integrity.

## Repository constraints (READ FIRST)

- Parent theme: `wp-content/themes/kadence/` — **do NOT modify**.
- Active child theme: `wp-content/themes/beslock-custom/`.
  - ALL custom UI, layout, styles, JS, and behavior must live **only** inside this theme.
- NEVER edit these folders:
  - `wp-content/themes/kadence/`
  - `wp-includes/`
  - `wp-admin/`
 # Copilot instructions — beslock.com.co (WordPress site)

Purpose: help AI coding agents be immediately productive when working on this WordPress codebase, while preserving architectural and design integrity.

## Repository constraints (READ FIRST)

- Parent theme: `wp-content/themes/kadence/` — **do NOT modify**.
- Active child theme: `wp-content/themes/beslock-custom/`.
  - ALL custom UI, layout, styles, JS, and behavior must live **only** inside this theme.
- NEVER edit these folders:
  - `wp-content/themes/kadence/`
  - `wp-includes/`
  - `wp-admin/`
- Do NOT modify WooCommerce core plugin files.
  - Extend WooCommerce via hooks/filters, theme-level CSS, or safe template delegation only.

## Big picture

This repository contains the full WordPress site for **beslock.com.co**.

- Core WordPress files live at the site root (e.g. `wp-config.php`, `wp-settings.php`, `index.php`).
- Standard core directories:
  - `wp-admin/`
  - `wp-content/`
  - `wp-includes/`
- All project-specific logic and customization belongs under:
  - `wp-content/themes/beslock-custom/`

Kadence is used strictly as a **parent framework theme**.  
All branding, layout decisions, animations, and business-specific behavior are implemented in the child theme.

## Key files to inspect before changing behavior

- `beslock.com.co/wp-config.php`
  - Database constants
  - Environment flags
  - `WP_DEBUG` (currently `false`)
- `beslock.com.co/error_log` and `wp-admin/error_log`
  - Primary sources for runtime PHP errors
- `wp-content/themes/beslock-custom/functions.php`
  - Theme bootstrap
  - Hooks, filters, and feature wiring

## Project-specific conventions

- Place all custom PHP, templates, CSS, and JS in:
  - `wp-content/themes/beslock-custom/`
- Prefer **hooks and filters** (`add_action`, `add_filter`) over template overrides.
- If a WooCommerce template override is required:
  - Explain **WHY** hooks were insufficient.
  - Place the override under:
    - `wp-content/themes/beslock-custom/woocommerce/...`
- Use unique prefixes for custom functions and classes (e.g. `beslock_`) to avoid collisions.

## WooCommerce-specific guidance

- Kadence already provides WooCommerce compatibility.
  - Do NOT re-add or duplicate WooCommerce theme support unless explicitly requested.
- WooCommerce must be extended using:
  - hooks and filters
  - scoped theme CSS
  - documented template overrides (last resort)
- Avoid touching WooCommerce core templates or plugin files.
- Risks:
  - Editing core WooCommerce files or undocumented overrides can break updates and production parity.

## Frontend / UI considerations

- The homepage hero (video reel, overlays, animations) is **highly customized**.
  - Avoid refactoring, simplifying, or restructuring hero-related code unless explicitly requested.
- Global CSS changes should be scoped carefully to avoid impacting WooCommerce pages unintentionally.

## CSS and class naming conventions (STRICT)

- This project uses **strict BEM (Block__Element--Modifier)** naming conventions.
- All CSS class names must follow BEM consistently.
- Do NOT introduce ad-hoc, utility-style, or ambiguous class names.
- Do NOT mix BEM with other naming conventions (e.g. Tailwind-like utilities).
- Blocks should represent standalone components.
- Elements must belong to a single block.
- Modifiers are used only for variants or states.

Examples (conceptual):
- `hero`
- `hero__slide`
- `hero__overlay`
- `hero__overlay--enter`
- `drawer`
- `drawer__item`
- `drawer__item--active`

Rules:
- If a class name does not clearly fit BEM, stop and rethink the structure.
- Reuse existing blocks and elements where possible instead of inventing new ones.
- When adding new UI components, explain the block structure briefly before coding.

Place this section after `## Frontend / UI considerations` and before `## Runtime, debug, and local workflows`.

## Runtime, debug, and local workflows

- There is no build system or test runner; this is a PHP/WordPress site.
- To enable debugging locally, update `wp-config.php`:
  - `define('WP_DEBUG', true);`
  - `define('WP_DEBUG_LOG', true);`
  - `define('WP_DEBUG_DISPLAY', false);`
- Quick PHP syntax check:
  - `php -l path/to/file.php`
- Running locally requires:
  - A MySQL database matching `wp-config.php` credentials
  - Correct site URLs for the local environment
- Production is the **source of truth**.
  - Local changes should aim for parity, not divergence.

## Integration and cross-component points

- Database connection via constants in `wp-config.php`
- Cron / scheduled jobs:
  - `wp-cron.php`
- Async and APIs:
  - `admin-ajax.php`
  - REST API under `/wp-json/`
  - `xmlrpc.php`
- Email handling:
  - `wp-mail.php`

## Security notes

- Secrets, salts, and credentials are present in `wp-config.php`.
- Do NOT expose, log, or modify them unless explicitly instructed.
- Treat configuration changes as production-sensitive.

## PRs and editing guidelines

- Keep changes minimal and scoped to:
  - `wp-content/themes/beslock-custom/`
- Document any change affecting:
  - URLs
  - environment configuration
  - WooCommerce behavior
- When proposing template overrides, include:
  - rationale
  - hooks/filters that were considered and rejected

If any section is unclear or additional scaffolding is needed (e.g. a small plugin, a theme utility, or a debug toggle), ask before proceeding.
 (See <attachments> above for file contents. You may not need to search or read the file again.)

## Concrete examples (from `wp-content/themes/beslock-custom/functions.php`)

- Parent/child safe enqueue (ensure parent Kadence stylesheet loads from child theme):

  ```php
  if ( function_exists('is_child_theme') && is_child_theme() ) {
    wp_enqueue_style('kadence-parent-style', get_template_directory_uri() . '/style.css', [], null);
  }
  ```

- Asset versioning with `filemtime()` and `file_exists()`:

  ```php
  $ver = file_exists($path) ? filemtime($path) : null;
  wp_enqueue_style('beslock-main-style', $uri . '/assets/css/main.css', [], $ver);
  ```

- Script/style dependency and footer-loading pattern (use dependency arrays and last arg `true`):

  ```php
  wp_enqueue_script('beslock-main-js', $uri . '/assets/js/main.js', ['scrolltrigger'], $ver, true);
  ```

- CDN libraries registered as dependencies (GSAP + ScrollTrigger) so theme JS can rely on them.

- WooCommerce support & scoping: add theme support in `after_setup_theme` with priority `11`, and enqueue a scoped WC CSS only on WooCommerce pages using an enqueue priority of `20`.

  ```php
  add_action('after_setup_theme', function(){
    if (! current_theme_supports('woocommerce')) add_theme_support('woocommerce');
  }, 11);

  add_action('wp_enqueue_scripts', function(){
    if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
      wp_enqueue_style('beslock-wc-scope-fix', get_stylesheet_directory_uri() . '/assets/css/wc-scope-fix.css', [], filemtime($file));
    }
  }, 20);
  ```

- Conventions to follow: assets live in `assets/css/` and `assets/js/`; use `beslock-` handle prefixes; keep logic inside the child theme.

Use these concrete snippets as canonical patterns when adding or modifying theme assets, WooCommerce extensions, or small plugin-like features inside the child theme.

``` 
   inside the child theme at `wp-content/themes/beslock-custom/`.

 Key files to inspect before changing behavior:
 - [beslock.com.co/wp-config.php](beslock.com.co/wp-config.php) — DB constants, salts, and
   `WP_DEBUG` (currently `false`).
 - [beslock.com.co/error_log](beslock.com.co/error_log) and admin logs (there is an
   `wp-admin/error_log`) — useful for runtime failures.
 - `wp-content/themes/beslock-custom/functions.php` — theme bootstrap and custom hooks.

 Project-specific conventions:
 - Put all custom PHP, templates, CSS, and JS in `wp-content/themes/beslock-custom/`.
 - Prefer hooks and filters (`add_action`, `add_filter`) to modify behavior.
 - If you must override a WooCommerce template, explain WHY in the PR and
   place the override under `wp-content/themes/beslock-custom/woocommerce/...`.
 - Use unique function/class prefixes (e.g., `beslock_`) to avoid collisions.

 Runtime, debug, and local workflows:
 - There is no build system or test runner present; this is a PHP/WordPress site.
 - To enable debugging locally, edit `wp-config.php` and add/modify:
   - `define('WP_DEBUG', true);`
   - `define('WP_DEBUG_LOG', true);`
   - `define('WP_DEBUG_DISPLAY', false);`
 - Quick syntax checks: `php -l path/to/file.php`.
 - To run the site locally you must provide a MySQL database matching
   `wp-config.php` (or update credentials). A simple PHP dev server can be started
   with `php -S localhost:8000 -t beslock.com.co`, but WordPress requires the DB.

 WooCommerce guidance and risks:
 - WooCommerce is installed and must be extended with hooks/filters, theme CSS,
   and safe template delegation — avoid touching plugin core files.
 - Prefer `add_filter`/`add_action` to change pricing, cart, checkout behavior.
 - Template overrides are allowed only under `wp-content/themes/beslock-custom/woocommerce/`
   and only after documenting why hooks were insufficient.
 - Risks: modifying core templates or plugin files can break updates and production parity.

 Integration and cross-component points:
 - Database via constants in `wp-config.php` (DB_NAME, DB_USER, DB_PASSWORD, DB_HOST).
 - Cron / scheduled jobs: `wp-cron.php`.
 - AJAX/async endpoints: `admin-ajax.php`, REST endpoints under `/wp-json/`, and `xmlrpc.php`.
 - Mail-related handling: `wp-mail.php`.

 Security notes discovered in repo:
 - Secrets and salts are present in `wp-config.php`; do not expose or alter them
   in commits unless instructed. Treat `wp-config.php` carefully.

 PRs and editing guidelines:
 - Keep changes minimal and scoped to `wp-content/themes/beslock-custom/` unless core edits
   are explicitly required and justified.
 - Document any change to production-like configuration (DB, salts, URLs).
 - When proposing template overrides, include a short rationale and pointer to the hook
   that was considered but rejected.

 If anything here is unclear or you'd like a small plugin/theme scaffold or a
 debug-toggle patch, tell me which and I will prepare it under `wp-content/themes/beslock-custom/`.
