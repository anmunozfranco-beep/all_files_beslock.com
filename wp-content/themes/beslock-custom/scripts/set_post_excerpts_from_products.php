<?php
// Sync post_excerpt from existing WP products into theme data/products.json
// Usage: php set_post_excerpts_from_products.php [--apply]

$apply = in_array('--apply', $argv, true);

// locate wp-load.php by walking up the directory tree
$dir = __DIR__;
$wp = '';
for ( $i = 0; $i < 6; $i++ ) {
    $candidate = $dir . str_repeat( '/..', $i ) . '/wp-load.php';
    $candidate = realpath( $candidate );
    if ( $candidate && file_exists( $candidate ) ) {
        $wp = $candidate;
        break;
    }
}
if ( ! $wp ) {
    fwrite( STDERR, "Cannot locate wp-load.php by walking up from " . __DIR__ . "\n" );
    exit(1);
}
require_once $wp;

$data_file = get_stylesheet_directory() . '/data/products.json';
if ( ! file_exists( $data_file ) ) {
    fwrite( STDERR, "products.json not found: {$data_file}\n" );
    exit(1);
}

$json = file_get_contents( $data_file );
$data = json_decode( $json, true );
if ( json_last_error() !== JSON_ERROR_NONE ) {
    fwrite( STDERR, "Invalid JSON in products.json: " . json_last_error_msg() . "\n" );
    exit(1);
}

$changed = array();
foreach ( $data as $i => $prod ) {
    if ( empty( $prod['slug'] ) ) continue;
    $slug = sanitize_title( $prod['slug'] );
    $post = get_page_by_path( $slug, OBJECT, 'product' );
    if ( ! $post ) continue;
    $excerpt = get_post_field( 'post_excerpt', $post->ID );
    if ( $excerpt === null ) $excerpt = '';
    // normalize whitespace
    $excerpt = trim( preg_replace('/\s+/', ' ', $excerpt) );
    $current = isset( $prod['short_description'] ) ? trim( preg_replace('/\s+/', ' ', $prod['short_description'] ) ) : '';
    if ( $excerpt !== $current ) {
        $data[ $i ]['short_description' ] = $excerpt;
        $changed[] = array( 'slug' => $slug, 'from' => $current, 'to' => $excerpt );
    }
}

if ( empty( $changed ) ) {
    echo "No changes needed; products.json already matches post_excerpt values.\n";
    exit(0);
}

if ( $apply ) {
    // overwrite original
    file_put_contents( $data_file, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
    echo "Applied changes to products.json\n";
    // attempt to git add & commit
    $msg = 'Sync: restore short_description from post_excerpt';
    exec('git add ' . escapeshellarg( $data_file ) . ' && git commit -m ' . escapeshellarg( $msg ) . ' 2>&1', $out, $rc );
    echo implode("\n", $out) . "\n";
    if ( $rc === 0 ) {
        echo "Committed changes to git.\n";
    } else {
        echo "Git commit returned code {$rc}. Check git status.\n";
    }
}

echo "Summary: changed " . count( $changed ) . " products.\n";
foreach ( $changed as $c ) {
    $from = $c['from'] === '' ? '(empty)' : $c['from'];
    $to = $c['to'] === '' ? '(empty)' : $c['to'];
    printf("- %s: %s -> %s\n", $c['slug'], $from, $to);
}

?>
