WP-CLI & PHP CLI via Docker (local developer helper)
===============================================

This repository includes a lightweight Docker Compose setup you can use
to run PHP and WP-CLI locally without installing system packages.

Files added:
- `docker-compose.wp.yml` — services: `db` (MySQL), `wordpress` (PHP/Apache), `wpcli` (WP-CLI)
- `scripts/wpcli.sh` — helper to run `wp` commands inside the `wpcli` service

Quick start (macOS / Linux):

1. Ensure Docker and Docker Compose are installed and running.
2. From the repo root run:

```bash
docker compose -f docker-compose.wp.yml up -d db wordpress
```

3. Visit the local site at: http://localhost:8000 (the site files are mounted from `./beslock.com.co`).

4. Run WP-CLI commands against the mounted site using the helper script. Examples:

```bash
./scripts/wpcli.sh core version
./scripts/wpcli.sh plugin list
./scripts/wpcli.sh eval-file wp-content/themes/beslock-custom/tools/import-products-wc.php
```

Notes & safety:
- The compose file uses a simple MySQL password for local development (`rootpass` / `wordpress`). Change for your environment.
- The `import-products-wc.php` script is included in the theme under `wp-content/themes/beslock-custom/tools/` and by default in the repo it was set to run real imports. You can run it first in dry-run mode by editing `$DO_IMPORT = false` before running.
- The WordPress files are mounted from `./beslock.com.co`. Ensure `wp-config.php` in that folder is configured for the DB settings above or edit environment variables in `docker-compose.wp.yml`.

If you prefer Homebrew-native installation on macOS instead of Docker, run:

```bash
brew install php
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
wp --info
```

After this, run WP-CLI from the repo root:

```bash
wp --path=beslock.com.co eval-file wp-content/themes/beslock-custom/tools/import-products-wc.php
```
