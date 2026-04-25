#!/usr/bin/env bash
set -euo pipefail

# Import portfolio/theme images into the WordPress Media Library using WP-CLI.
# Run from repo root. If you use Docker, run it inside the wordpress container:
# docker compose exec wordpress bash -lc "./scripts/import-portfolio-images.sh"

WP_CMD=${WP_CMD:-wp}
THEME_IMAGES_DIR="wp-content/themes/beslock-custom/assets/images"

if ! command -v "$WP_CMD" >/dev/null 2>&1; then
  echo "ERROR: WP-CLI not found at '$WP_CMD'. Install WP-CLI or set WP_CMD to the full path."
  exit 1
fi

if [ ! -d "$THEME_IMAGES_DIR" ]; then
  echo "ERROR: images directory not found: $THEME_IMAGES_DIR"
  exit 1
fi

shopt -s nullglob
FILES=("$THEME_IMAGES_DIR"/*.{webp,png,jpg,jpeg,gif} )
shopt -u nullglob

if [ ${#FILES[@]} -eq 0 ]; then
  echo "No image files found in $THEME_IMAGES_DIR"
  exit 0
fi

echo "Found ${#FILES[@]} files. Importing to Media Library..."

for f in "${FILES[@]}"; do
  echo "Importing: $f"
  # --porcelain prints attachment ID only (useful for automation)
  ATT_ID=$($WP_CMD media import "$f" --porcelain 2>/dev/null || true)
  if [ -n "$ATT_ID" ]; then
    echo " Imported as attachment ID: $ATT_ID"
  else
    echo " Warning: failed to import $f (maybe already imported)"
  fi
done

echo "Import complete."

echo "Next steps (optional): assign imported images to products as featured images or gallery using WP-CLI or admin UI. Example to set featured image for product with slug 'e-nova':"
cat <<'EOF'
# Find product ID by slug and set thumbnail (run in WP-CLI environment):
PRODUCT_ID=$(wp post list --post_type=product --name=e-nova --field=ID)
# Use attachment ID from import step
wp post meta add $PRODUCT_ID _thumbnail_id 123
EOF
