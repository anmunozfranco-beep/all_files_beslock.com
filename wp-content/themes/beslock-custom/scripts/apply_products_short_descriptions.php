<?php
// Apply short_description from data/products.json into WP product post_excerpt
// Usage: php apply_products_short_descriptions.php [--apply]

$apply = in_array('--apply', $argv, true);

// Locate wp-load.php by walking upwards
$dir = __DIR__;
$wp = '';
for ($i = 0; $i < 6; $i++) {
    $candidate = realpath($dir . str_repeat('/..', $i) . '/wp-load.php');
    if ($candidate && file_exists($candidate)) { $wp = $candidate; break; }
}
if (!$wp) {
    fwrite(STDERR, "Cannot locate wp-load.php by walking up from " . __DIR__ . "\n");
    exit(1);
}
require_once $wp;

$data_file = get_stylesheet_directory() . '/data/products.json';
if (!file_exists($data_file)) {
    fwrite(STDERR, "products.json not found: {$data_file}\n");
    exit(1);
}

$json = file_get_contents($data_file);
$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, "Invalid JSON: " . json_last_error_msg() . "\n");
    exit(1);
}

$report = [];
foreach ($data as $prod) {
    if (empty($prod['slug']) && empty($prod['title'])) continue;
    $slug = !empty($prod['slug']) ? sanitize_title($prod['slug']) : sanitize_title($prod['title']);
    // try mapping option first
    $mapping = get_option('beslock_portfolio_mapping', array());
    $found = null;
    if (!empty($mapping) && isset($mapping[$slug])) {
        $pid = intval($mapping[$slug]);
        $p = get_post($pid);
        if ($p && $p->post_type === 'product') $found = $p;
    }
    if (!$found) {
        $posts = get_posts(array('post_type' => 'product', 'name' => $slug, 'posts_per_page' => 1));
        if (!empty($posts)) $found = $posts[0];
    }
    if (!$found && !empty($prod['title'])) {
        $posts = get_posts(array('post_type' => 'product', 's' => $prod['title'], 'posts_per_page' => 1));
        if (!empty($posts)) $found = $posts[0];
    }
    if (!$found) {
        $report[] = "Not found: {$slug}";
        continue;
    }
    $pid = $found->ID;
    $new_excerpt = isset($prod['short_description']) ? trim(preg_replace('/\s+/', ' ', $prod['short_description'])) : '';
    $old_excerpt = trim(preg_replace('/\s+/', ' ', get_post_field('post_excerpt', $pid) ?: ''));
    if ($new_excerpt === $old_excerpt) {
        $report[] = "Unchanged: {$slug} (ID: {$pid})";
        continue;
    }
    if ($apply) {
        wp_update_post(array('ID' => $pid, 'post_excerpt' => $new_excerpt));
        $report[] = "Updated: {$slug} (ID: {$pid})";
    } else {
        $report[] = "Would update: {$slug} (ID: {$pid})\n  from: " . ($old_excerpt === '' ? '(empty)' : $old_excerpt) . "\n  to: " . ($new_excerpt === '' ? '(empty)' : $new_excerpt);
    }
}

echo implode("\n", $report) . "\n";

if ($apply) {
    // try to persist a short log option
    try { update_option('beslock_last_excerpt_sync', 'Applied short_description -> post_excerpt on ' . date('c')); } catch (Exception $e) { }
}

?>
