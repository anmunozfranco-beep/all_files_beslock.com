Local Docker development for beslock.com.co

This is a minimal docker-compose setup for local WordPress development.

Files added:
- `docker-compose.yml` — WordPress + MariaDB services
- `.env.example` — local environment variables (copy to `.env` and edit)

Principles / notes
- This is for LOCAL development only. Do NOT use these files for production.
- Parent theme (`wp-content/themes/kadence/`) and WooCommerce core must not be modified.
- The repository `beslock.com.co/` is mounted into the container so files are editable.
  Be careful editing WordPress core; prefer editing child theme under `wp-content/themes/beslock-custom/`.

Quick start
1. Copy the example env and edit if you want custom credentials:

```bash
cp .env.example .env
# (edit .env if needed)
```

2. Start services:

```bash
docker-compose up -d
```

3. Open the site:

http://localhost:8000/

4. Access admin:

http://localhost:8000/wp-admin

Stopping and cleanup
- Stop containers:

```bash
docker-compose down
```

- Stop and remove containers, networks and named volumes (destroys DB and uploads):

```bash
docker-compose down -v
```

Notes & troubleshooting
- If file permission issues occur (e.g., uploads writable), run on the host:

```bash
# change ownership so the container (www-data) can write when necessary
sudo chown -R $USER:staff beslock.com.co/wp-content/uploads
# or, if needed inside the container
docker-compose exec wordpress bash -c "chown -R www-data:www-data wp-content/uploads"
```

- Importing production data: export your SQL and import into the `db` volume, or use WP-CLI inside the `wordpress` container.

- To run WP-CLI commands:

```bash
docker-compose exec wordpress wp --info
docker-compose exec wordpress wp plugin list
```

If you'd like, I can add a small helper `Makefile` for common commands, or a
`docker-compose.override.yml` for more advanced local-only services.
