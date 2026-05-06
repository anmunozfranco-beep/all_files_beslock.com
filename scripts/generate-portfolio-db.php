<?php
/**
 * Generate portfolio DB and JSON for products in the 'product-portfolio'.
 *
 * Creates folder `wp-content/themes/beslock-custom/repo_portfolio` and
 * writes `products.json`. Also attempts to create a SQLite DB `products.sqlite`.
 *
 * Usage (from project root):
 * php scripts/generate-portfolio-db.php
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$wp_load = __DIR__ . '/../wp-load.php';
if (!file_exists($wp_load)) {
    fwrite(STDERR, "Unable to find wp-load.php at: $wp_load\n");
    exit(2);
}

require_once $wp_load;

$theme_dir = __DIR__ . '/../wp-content/themes/beslock-custom';
$repo_dir = $theme_dir . '/repo_portfolio';

if (!is_dir($repo_dir)) {
    if (!mkdir($repo_dir, 0755, true)) {
        fwrite(STDERR, "Failed to create directory: $repo_dir\n");
        exit(3);
    }
}

// Fetch products
$args = array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
);

$posts = get_posts($args);

$out = array();

function fetch_dom_description($url) {
    // Try WP HTTP API first
    $html = false;
    if (function_exists('wp_remote_get')) {
        $resp = wp_remote_get($url, array('timeout'=>20));
        if (!is_wp_error($resp) && isset($resp['response']['code']) && $resp['response']['code']==200) {
            $html = $resp['body'];
        }
    }
    if ($html === false) {
        // fallback to file_get_contents
        $opts = stream_context_create(array('http'=>array('timeout'=>20)));
        $html = @file_get_contents($url, false, $opts);
    }
    if (!$html) return '';

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    // prefixing to handle utf-8 correctly
    @$doc->loadHTML('<?xml encoding="utf-8">' . $html);
    $xpath = new DOMXPath($doc);

    // Try common selectors for product descriptions
    $queries = array(
        "//*[contains(@class,'woocommerce-product-details__short-description')]",
        "//*[contains(@class,'short-description')]",
        "//*[contains(@class,'product-description')]",
        "//*[contains(@class,'entry-summary')]",
        "//*[contains(@class,'description')]",
        "//div[@id='product-description']",
    );

    foreach ($queries as $q) {
        $nodes = $xpath->query($q);
        if ($nodes && $nodes->length) {
            // return combined textContent of first match
            return trim($nodes->item(0)->textContent);
        }
    }

    return '';
}

function normalize_key($s) {
    if (!is_string($s)) return '';
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s);
    $s = mb_strtolower($s, 'UTF-8');
    return $s;
}

foreach ($posts as $p) {
    $id = $p->ID;
    $meta = get_post_meta($id);
    $price = isset($meta['_price'][0]) ? $meta['_price'][0] : '';
    $badge = isset($meta['beslock_badge'][0]) ? $meta['beslock_badge'][0] : '';
    $thumb = isset($meta['_thumbnail_id'][0]) ? $meta['_thumbnail_id'][0] : '';
    $gallery = get_post_meta($id, '_product_image_gallery', true);
    $gallery_arr = $gallery ? array_filter(array_map('trim', explode(',', $gallery))) : array();
    $permalink = get_permalink($id);

    $dom_description = '';
    if ($permalink) {
        $dom_description = fetch_dom_description($permalink);
    }

    $item = array(
        'ID' => $id,
        'slug' => $p->post_name,
        'title' => html_entity_decode($p->post_title),
        'excerpt' => html_entity_decode($p->post_excerpt),
        'content' => html_entity_decode($p->post_content),
        'price' => $price,
        'badge' => $badge,
        'meta' => $meta,
        'gallery_ids' => $gallery_arr,
        'thumbnail_id' => $thumb,
        'permalink' => $permalink,
        'dom_description' => $dom_description,
    );

    $out[] = $item;
}

$json_path = $repo_dir . '/products.json';

$json_path = $repo_dir . '/products.json';

// If there is an existing products.json, load and merge by title/slug (normalize keys, overwrite existing entries)
$existing = array();
if (file_exists($json_path)) {
    $raw = @file_get_contents($json_path);
    $dec = $raw ? json_decode($raw, true) : null;
    if (is_array($dec)) {
        foreach ($dec as $row) {
            $rawKey = isset($row['slug']) && $row['slug'] !== '' ? $row['slug'] : (isset($row['title']) ? $row['title'] : $row['ID']);
            $key = normalize_key($rawKey);
            $existing[$key] = $row;
        }
    }
}

// Merge/overwrite by title
foreach ($out as $row) {
    $rawKey = isset($row['slug']) && $row['slug'] !== '' ? $row['slug'] : (isset($row['title']) ? $row['title'] : $row['ID']);
    $key = normalize_key($rawKey);
    $existing[$key] = $row; // overwrite or add
}

$combined = array_values($existing);
file_put_contents($json_path, json_encode($combined, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Wrote products JSON to: $json_path\n";

// Try to write SQLite DB if PDO SQLite available
$sqlite_path = $repo_dir . '/products.sqlite';
$pdo = null;
try {
    if (class_exists('PDO')) {
        $pdo = new PDO('sqlite:' . $sqlite_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            ID INTEGER PRIMARY KEY,
            slug TEXT,
            title TEXT,
            excerpt TEXT,
            content TEXT,
            price TEXT,
            badge TEXT,
            permalink TEXT,
            dom_description TEXT,
            meta_json TEXT,
            gallery TEXT,
            thumbnail_id TEXT
        )");

        $stmt = $pdo->prepare("REPLACE INTO products (ID,slug,title,excerpt,content,price,badge,permalink,dom_description,meta_json,gallery,thumbnail_id) VALUES (:ID,:slug,:title,:excerpt,:content,:price,:badge,:permalink,:dom_description,:meta_json,:gallery,:thumbnail_id)");
        foreach ($out as $row) {
            $stmt->execute(array(
                ':ID' => $row['ID'],
                ':slug' => $row['slug'],
                ':title' => $row['title'],
                ':excerpt' => $row['excerpt'],
                ':content' => $row['content'],
                ':price' => $row['price'],
                ':badge' => $row['badge'],
                ':permalink' => $row['permalink'],
                ':dom_description' => $row['dom_description'],
                ':meta_json' => json_encode($row['meta'], JSON_UNESCAPED_UNICODE),
                ':gallery' => implode(',', $row['gallery_ids']),
                ':thumbnail_id' => $row['thumbnail_id'],
            ));
        }
        echo "Wrote SQLite DB to: $sqlite_path\n";
    } else {
        echo "PDO not available; skipping SQLite creation.\n";
    }
} catch (Exception $e) {
    fwrite(STDERR, "Failed to create/write SQLite DB: " . $e->getMessage() . "\n");
}

echo "Done. Products exported: " . count($out) . "\n";

exit(0);
