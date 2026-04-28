<?php
/**
 * List products that have the `beslock_badge` post meta.
 *
 * Usage (run from project root):
 * php scripts/list-badged-products.php
 */

// Ensure script is run from CLI
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$wp_load = __DIR__ . '/../wp-load.php';
if ( ! file_exists( $wp_load ) ) {
    fwrite(STDERR, "Unable to find wp-load.php at: $wp_load\n");
    exit(2);
}

require_once $wp_load;

// Use WordPress functions to fetch products with the meta key
$args = array(
    'post_type'      => 'product',
    'post_status'    => 'any',
    'meta_key'       => 'beslock_badge',
    'posts_per_page' => -1,
);

$posts = get_posts( $args );

if ( empty( $posts ) ) {
    echo "No products found with meta key 'beslock_badge'.\n";
    exit(0);
}

// Print CSV header
echo "ID\tTitle\tPermalink\tBadge Meta\n";

foreach ( $posts as $p ) {
    $id = $p->ID;
    $title = html_entity_decode( $p->post_title );
    $permalink = get_permalink( $id );
    $meta = get_post_meta( $id, 'beslock_badge', true );
    // Normalize whitespace
    $meta = is_array( $meta ) ? implode( ',', $meta ) : (string) $meta;
    $meta = preg_replace('/\s+/u', ' ', trim( $meta ));
    echo sprintf("%d\t%s\t%s\t%s\n", $id, $title, $permalink, $meta);
}

exit(0);
