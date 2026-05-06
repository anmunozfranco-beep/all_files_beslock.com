<?php
/**
 * WP-CLI / eval-file helper
 * Usage (dry-run):
 *   wp eval-file wp-content/themes/beslock-custom/scripts/fix-placeholder-images.php -- --dry-run
 * Usage (apply):
 *   wp eval-file wp-content/themes/beslock-custom/scripts/fix-placeholder-images.php
 *
 * What it does:
 * - Scans all products for thumbnail/gallery attachments whose filenames match a "placeholder" pattern
 * - Writes a JSON backup of original post meta to uploads/beslock-backups/
 * - Optionally (when not --dry-run) replaces bad attachments with a sane replacement:
 *     1) a non-suspicious attached image for the same product
 *     2) a theme asset at assets/images/products/<slug>.(webp|png|jpg)
 *
 * NOTE: Run this from your WP install root with WP-CLI. Review the produced backup file before running without --dry-run.
 */

$argv = isset( $GLOBALS['argv'] ) ? $GLOBALS['argv'] : array();
$dry_run = in_array('--dry-run', $argv, true) || in_array('--dryrun', $argv, true) || in_array('-n', $argv, true);
// allow skipping backup when explicitly requested
$no_backup = in_array('--no-backup', $argv, true) || in_array('--nobackup', $argv, true) || in_array('-B', $argv, true);

// If run via plain PHP (no WP-CLI), attempt to bootstrap WordPress by requiring wp-load.php
if ( ! function_exists( 'get_posts' ) ) {
    $maybe = realpath( __DIR__ . '/../../../../wp-load.php' );
    if ( $maybe && file_exists( $maybe ) ) {
        require_once $maybe;
    }
}
$pattern = '/lupa|magnif|magnifier|magnifying|search|lens|placeholder/i';

if ( ! function_exists( 'get_posts' ) ) {
    echo "This script must be run with WP-CLI (wp eval-file) or PHP from the WordPress root. wp-load.php could not be located.\n";
    exit(1);
}

$upload = wp_upload_dir();
$backup_dir = trailingslashit( $upload['basedir'] ) . 'beslock-backups';
$backup_file = $backup_dir . '/placeholder-backup-' . date('Ymd-His') . '.json';
$backup = array( 'meta' => array(), 'actions' => array() );
$affected_products = array();

$products = get_posts( array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => array('publish','draft','private'),
) );

foreach ( $products as $p ) {
    $pid = intval( $p->ID );
    $thumbnail_id = intval( get_post_thumbnail_id( $pid ) );
    $gallery_meta = get_post_meta( $pid, '_product_image_gallery', true );
    $gallery_ids = $gallery_meta ? array_filter( array_map( 'intval', explode( ',', $gallery_meta ) ) ) : array();

    $orig = array( 'thumbnail_id' => $thumbnail_id, 'gallery' => $gallery_meta );
    $bad_ids = array();

    // helper to test an attachment id
    $test_attachment = function( $aid ) use ( $pattern ) {
        if ( empty( $aid ) ) return false;
        $file = get_attached_file( $aid );
        if ( $file && is_string( $file ) ) {
            $base = basename( $file );
            if ( preg_match( $pattern, $base ) ) return true;
            // image size heuristics
            if ( file_exists( $file ) ) {
                $size = @getimagesize( $file );
                if ( $size && isset( $size[0], $size[1] ) ) {
                    list( $w, $h ) = $size;
                    if ( $w && $h && ( $w / $h > 5 || $h / $w > 5 ) ) return true;
                }
            }
        } else {
            // fallback: check URL
            $url = wp_get_attachment_url( $aid );
            if ( $url ) {
                $base = basename( $url );
                if ( preg_match( $pattern, $base ) ) return true;
            }
        }
        return false;
    };

    if ( $thumbnail_id && $test_attachment( $thumbnail_id ) ) {
        $bad_ids[] = $thumbnail_id;
    }
    foreach ( $gallery_ids as $gid ) {
        if ( $test_attachment( $gid ) ) $bad_ids[] = $gid;
    }
    $bad_ids = array_values( array_unique( $bad_ids ) );

    if ( ! empty( $bad_ids ) ) {
        $affected_products[] = $pid;
        if ( ! $no_backup ) {
            $backup['meta'][ $pid ] = $orig;
        }

        if ( $dry_run ) {
            echo "[DRY] Product {$pid} has suspicious attachments: " . implode(',', $bad_ids) . "\n";
            continue;
        }

        // Attempt replacement: find non-suspicious attached image
        $replacement_id = 0;
        $children = get_children( array(
            'post_parent' => $pid,
            'post_type'   => 'attachment',
            'post_mime_type' => 'image',
            'orderby' => 'menu_order ID',
            'numberposts' => -1,
        ) );
        if ( ! empty( $children ) ) {
            foreach ( $children as $att ) {
                $aid = intval( $att->ID );
                if ( in_array( $aid, $bad_ids, true ) ) continue;
                // ensure not suspicious
                $file = get_attached_file( $aid );
                $base = $file ? basename( $file ) : basename( wp_get_attachment_url( $aid ) );
                if ( ! preg_match( $pattern, $base ) ) {
                    $replacement_id = $aid;
                    break;
                }
            }
        }

        // If no replacement attached, try theme asset by slug (prefer primary/underscore .webp)
        if ( ! $replacement_id ) {
            $slug = $p->post_name;
            $theme_dir = get_stylesheet_directory();
            $theme_uri = get_stylesheet_directory_uri();

            // prefer primary pattern: slug_.webp, then plain slug.webp, then any slug_*.webp
            $candidates = array();
            $primary = $theme_dir . '/assets/images/products/' . $slug . '_.webp';
            $fallback_plain = $theme_dir . '/assets/images/products/' . $slug . '.webp';
            if ( file_exists( $primary ) ) {
                $candidates[] = array( 'path' => $primary, 'url' => $theme_uri . '/assets/images/products/' . $slug . '_.webp' );
            }
            if ( file_exists( $fallback_plain ) ) {
                $candidates[] = array( 'path' => $fallback_plain, 'url' => $theme_uri . '/assets/images/products/' . $slug . '.webp' );
            }
            // glob secondary variants (slug_*.webp)
            $glob_pattern = $theme_dir . '/assets/images/products/' . $slug . '_*.webp';
            foreach ( glob( $glob_pattern ) as $g ) {
                $rel = str_replace( $theme_dir, '', $g );
                $rel = ltrim( $rel, '/' );
                $candidates[] = array( 'path' => $g, 'url' => $theme_uri . '/' . $rel );
            }

            // fallback to assets/images/ (non-products folder)
            $primary2 = $theme_dir . '/assets/images/' . $slug . '_.webp';
            $fallback_plain2 = $theme_dir . '/assets/images/' . $slug . '.webp';
            if ( file_exists( $primary2 ) ) {
                $candidates[] = array( 'path' => $primary2, 'url' => $theme_uri . '/assets/images/' . $slug . '_.webp' );
            }
            if ( file_exists( $fallback_plain2 ) ) {
                $candidates[] = array( 'path' => $fallback_plain2, 'url' => $theme_uri . '/assets/images/' . $slug . '.webp' );
            }
            $glob_pattern2 = $theme_dir . '/assets/images/' . $slug . '_*.webp';
            foreach ( glob( $glob_pattern2 ) as $g ) {
                $rel = str_replace( $theme_dir, '', $g );
                $rel = ltrim( $rel, '/' );
                $candidates[] = array( 'path' => $g, 'url' => $theme_uri . '/' . $rel );
            }

            // if still no candidate, fall back to older extensions
            if ( empty( $candidates ) ) {
                $exts = array( '.png', '.jpg', '.jpeg' );
                foreach ( $exts as $ext ) {
                    $candidate = $theme_dir . '/assets/images/products/' . $slug . $ext;
                    if ( file_exists( $candidate ) ) {
                        $candidates[] = array( 'path' => $candidate, 'url' => $theme_uri . '/assets/images/products/' . $slug . $ext );
                        break;
                    }
                    $candidate2 = $theme_dir . '/assets/images/' . $slug . $ext;
                    if ( file_exists( $candidate2 ) ) {
                        $candidates[] = array( 'path' => $candidate2, 'url' => $theme_uri . '/assets/images/' . $slug . $ext );
                        break;
                    }
                }
            }

            if ( ! empty( $candidates ) ) {
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
                foreach ( $candidates as $cand ) {
                    $file_url = $cand['url'];
                    $maybe_id = media_sideload_image( $file_url, $pid, null, 'id' );
                    if ( ! is_wp_error( $maybe_id ) && intval( $maybe_id ) ) {
                        $replacement_id = intval( $maybe_id );
                        break;
                    }
                }
            }
        }

        // Apply replacement if found
        $actions = array();
        if ( $replacement_id ) {
            if ( $thumbnail_id && in_array( $thumbnail_id, $bad_ids, true ) ) {
                set_post_thumbnail( $pid, $replacement_id );
                $actions[] = "thumbnail:{$thumbnail_id}->{$replacement_id}";
            }
            if ( ! empty( $gallery_ids ) ) {
                $new_gallery = $gallery_ids;
                $changed = false;
                foreach ( $new_gallery as $i => $gid ) {
                    if ( in_array( $gid, $bad_ids, true ) ) {
                        $new_gallery[ $i ] = $replacement_id;
                        $changed = true;
                    }
                }
                // dedupe
                $new_gallery = array_values( array_unique( $new_gallery ) );
                if ( $changed ) {
                    update_post_meta( $pid, '_product_image_gallery', implode( ',', $new_gallery ) );
                    $actions[] = 'gallery_replaced_with_' . $replacement_id;
                }
            }
        } else {
            $actions[] = 'no_replacement_found';
        }

        if ( ! $no_backup ) {
            $backup['actions'][ $pid ] = $actions;
        }
        echo "[APPLY] Product {$pid}: " . implode( ';', $actions ) . "\n";
    }
}

// write backup
if ( ! $no_backup ) {
    if ( ! file_exists( $backup_dir ) ) {
        wp_mkdir_p( $backup_dir );
    }
    file_put_contents( $backup_file, wp_json_encode( $backup, JSON_PRETTY_PRINT ) );
    echo "\nBackup written to: {$backup_file}\n";
} else {
    echo "\n--no-backup specified: skipping backup file write.\n";
}
echo "Products scanned: " . count( $products ) . "\n";
echo "Affected products: " . count( $affected_products ) . "\n";

if ( $dry_run ) {
    echo "Dry-run complete. Review the affected products above. To apply changes, run without --dry-run.\n";
} else {
    echo "Apply complete. Review backup file before making further changes.\n";
}

return;
