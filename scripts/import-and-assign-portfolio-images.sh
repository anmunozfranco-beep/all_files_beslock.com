#!/usr/bin/env bash
set -euo pipefail

# Combined: import portfolio images into WP Media Library then run theme assignment
# Run from repo root. Inside Docker container run:
# docker compose exec wordpress bash -lc "cd /var/www/html && ./scripts/import-and-assign-portfolio-images.sh"

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

IMPORTED_IDS=()

for f in "${FILES[@]}"; do
  echo "Importing: $f"
  ATT_ID=$($WP_CMD media import "$f" --porcelain 2>/dev/null || true)
  if [ -n "$ATT_ID" ]; then
    echo " Imported as attachment ID: $ATT_ID"
    IMPORTED_IDS+=("$ATT_ID")
  else
    echo " Warning: failed to import $f (maybe already imported)"
  fi
done

echo "Import complete. Imported count: ${#IMPORTED_IDS[@]}"

echo "Running assignment routine (theme function beslock_assign_images_to_products)..."

# Call the theme's assignment function if available. It returns an array with assigned/skipped count.
ASSIGN_JSON=$($WP_CMD eval 'if ( function_exists("beslock_assign_images_to_products") ) { $r = beslock_assign_images_to_products(); echo json_encode($r); } else { echo json_encode(array("error"=>"missing_function")); }' 2>/dev/null || true)

if [ -z "$ASSIGN_JSON" ]; then
  echo "Assignment step produced no output; check WP-CLI and theme availability."
  exit 1
fi

echo "Assignment result: $ASSIGN_JSON"

echo "Done."
