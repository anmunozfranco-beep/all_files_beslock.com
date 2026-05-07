<?php
/*
Plugin Name: Portfolio Product Sync
Description: Sincroniza productos desde repo_portfolio/products.json a posts de tipo product (create/update) usando solo WP functions y meta SQL.
Version: 1.0
Author: Automated
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'pps_add_tools_page' );
function pps_add_tools_page() {
    add_management_page( 'Portfolio Product Sync', 'Portfolio Product Sync', 'manage_options', 'portfolio-product-sync', 'pps_tools_page' );
}

function pps_tools_page() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Acceso denegado' );

    $json_path = WP_CONTENT_DIR . '/themes/beslock-custom/repo_portfolio/products.json';
    $exists = file_exists( $json_path );
    $count = 0;
    if ( $exists ) {
        $raw = @file_get_contents( $json_path );
        $dec = $raw ? json_decode( $raw, true ) : null;
        if ( is_array( $dec ) ) $count = count( $dec );
    }

    $message = '';
    if ( isset( $_POST['pps_run'] ) ) {
        check_admin_referer( 'pps_action', 'pps_nonce' );
        $res = pps_run_sync();
        if ( is_array( $res ) ) {
            $message = sprintf( 'Resultado: created=%d, updated=%d, skipped=%d, invalid=%d. Log: %s', $res['created'], $res['updated'], $res['skipped'], $res['invalid'], esc_html( $res['log'] ) );
        } else {
            $message = 'Error al ejecutar sincronización.';
        }
    }

    ?>
    <div class="wrap">
        <h1>Portfolio Product Sync</h1>
        <?php if ( $message ) : ?>
            <div id="message" class="updated notice"><p><?php echo $message; ?></p></div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field( 'pps_action', 'pps_nonce' ); ?>
            <p><strong>JSON path:</strong> <?php echo esc_html( $json_path ); ?></p>
            <p><strong>File exists:</strong> <?php echo $exists ? 'yes' : 'no'; ?></p>
            <p><strong>Products found:</strong> <?php echo intval( $count ); ?></p>
            <p>
                <input type="submit" name="pps_run" class="button button-primary" value="Sincronizar Productos">
            </p>
        </form>
    </div>
    <?php
}

function pps_log_file() {
    return WP_CONTENT_DIR . '/uploads/portfolio-product-sync.log';
}

function pps_log( $line ) {
    $file = pps_log_file();
    $prefix = gmdate( 'Y-m-d H:i:s' );
    @file_put_contents( $file, $prefix . "\t" . $line . "\n", FILE_APPEND );
}

// Resolve existing attachment by filename or import it into media library
if ( ! function_exists( 'pps_resolve_or_import_attachment' ) ) {
    function pps_resolve_or_import_attachment( $file_path ) {
        if ( ! file_exists( $file_path ) ) return false;
        $filename = wp_basename( $file_path );
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        if ( $ext !== 'webp' ) return false;

        global $wpdb;
        $like = '%' . $wpdb->esc_like( $filename ) . '%';
        $existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s LIMIT 1", $like ) );
        if ( $existing_id ) {
            pps_log( sprintf( '[IMAGE_REUSED]\tfilename:%s\tattachment_id:%d', $filename, intval( $existing_id ) ) );
            return intval( $existing_id );
        }

        // import
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload_dir = wp_upload_dir();
        $dest = $upload_dir['path'] . '/' . $filename;
        if ( ! file_exists( $dest ) ) {
            if ( ! @copy( $file_path, $dest ) ) {
                pps_log( sprintf( '[IMAGE_NOT_FOUND]\t%s', $filename ) );
                return false;
            }
        }

        $filetype = wp_check_filetype( $filename, null );
        if ( empty( $filetype['type'] ) || strpos( $filetype['type'], 'image/' ) !== 0 ) {
            return false;
        }

        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $attach_id = wp_insert_attachment( $attachment, $dest );
        if ( ! $attach_id ) {
            return false;
        }
        $meta = wp_generate_attachment_metadata( $attach_id, $dest );
        wp_update_attachment_metadata( $attach_id, $meta );

        pps_log( sprintf( '[IMAGE_IMPORTED]\tfilename:%s\tattachment_id:%d', $filename, intval( $attach_id ) ) );
        return intval( $attach_id );
    }
}

function pps_run_sync() {
    if ( ! current_user_can( 'manage_options' ) ) return false;
    if ( ! isset( $_POST['pps_nonce'] ) || ! wp_verify_nonce( $_POST['pps_nonce'], 'pps_action' ) ) return false;

    // Verify WooCommerce active
    if ( ! class_exists( 'WooCommerce' ) && ! class_exists( 'WC' ) ) {
        pps_log( 'INVALID\twoocommerce_inactive' );
        return array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'invalid' => 1, 'log' => pps_log_file() );
    }

    $json_path = WP_CONTENT_DIR . '/themes/beslock-custom/repo_portfolio/products.json';
    if ( ! file_exists( $json_path ) ) {
        pps_log( 'INVALID_JSON\tfile_missing:' . $json_path );
        return array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'invalid' => 1, 'log' => pps_log_file() );
    }

    $raw = @file_get_contents( $json_path );
    $dec = $raw ? json_decode( $raw, true ) : null;
    if ( ! is_array( $dec ) ) {
        pps_log( 'INVALID_JSON\tjson_parse_failed' );
        return array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'invalid' => 1, 'log' => pps_log_file() );
    }

    global $wpdb;
    $created = $updated = $skipped = 0;

    pps_log( 'START\t' . gmdate( 'Y-m-d H:i:s' ) );

    foreach ( $dec as $item ) {
        $slug = isset( $item['slug'] ) ? trim( (string) $item['slug'] ) : '';
        $title = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';
        $content = isset( $item['content'] ) ? trim( (string) $item['content'] ) : '';
        $excerpt = isset( $item['excerpt'] ) ? trim( (string) $item['excerpt'] ) : '';
        $price = isset( $item['price'] ) ? trim( (string) $item['price'] ) : '';
        $badge = isset( $item['badge'] ) ? trim( (string) $item['badge'] ) : '';
        $features = null;
        if ( isset( $item['meta'] ) && is_array( $item['meta'] ) && isset( $item['meta']['beslock_features'] ) && is_array( $item['meta']['beslock_features'] ) && isset( $item['meta']['beslock_features'][0] ) ) {
            $features = $item['meta']['beslock_features'][0];
        }

        if ( $slug === '' ) {
            pps_log( '[SKIPPED_EMPTY]\tslug_empty' );
            $skipped++;
            continue;
        }

        // Find existing product by slug
        $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'product' LIMIT 1", $slug ) );

        if ( ! $post_id ) {
            // Create
            $post_arr = array(
                'post_title'   => $title,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_status'  => 'publish',
                'post_type'    => 'product',
                'post_name'    => $slug,
            );
            $new_id = wp_insert_post( $post_arr );
            if ( is_wp_error( $new_id ) || ! $new_id ) {
                pps_log( '[PRODUCT_NOT_FOUND]\tcreate_failed\tslug:' . $slug );
                continue;
            }

            // metas
            if ( $price !== '' ) {
                update_post_meta( $new_id, '_price', $price );
                update_post_meta( $new_id, '_regular_price', $price );
            }
            update_post_meta( $new_id, '_stock_status', 'instock' );
            update_post_meta( $new_id, '_manage_stock', 'no' );
            if ( $badge !== '' ) update_post_meta( $new_id, 'beslock_badge', $badge );
            if ( $features !== null ) update_post_meta( $new_id, 'beslock_features', $features );

            $post_id = $new_id;
            $action = 'created';
            $created++;
        } else {
            // Update existing
            $update_arr = array( 'ID' => $post_id );
            $need_update = false;
            if ( $title !== '' ) { $update_arr['post_title'] = $title; $need_update = true; }
            if ( $content !== '' ) { $update_arr['post_content'] = $content; $need_update = true; }
            if ( $excerpt !== '' ) { $update_arr['post_excerpt'] = $excerpt; $need_update = true; }
            if ( $need_update ) {
                wp_update_post( $update_arr );
            }

            if ( $price !== '' ) {
                update_post_meta( $post_id, '_price', $price );
                update_post_meta( $post_id, '_regular_price', $price );
            }
            if ( $badge !== '' ) update_post_meta( $post_id, 'beslock_badge', $badge );
            if ( $features !== null ) update_post_meta( $post_id, 'beslock_features', $features );

            $action = 'updated';
            $updated++;
        }

        // --- Image sync module (runs after create/update) ---
        $assets_dir = WP_CONTENT_DIR . '/themes/beslock-custom/assets/images';
        $gallery_ids = array();
        // Featured
        $featured_file = $assets_dir . '/' . $slug . '_.webp';
        if ( file_exists( $featured_file ) ) {
            $aid = pps_resolve_or_import_attachment( $featured_file );
            if ( $aid ) {
                update_post_meta( $post_id, '_thumbnail_id', $aid );
                pps_log( sprintf( '[FEATURED_ASSIGNED]\tslug:%s\tattachment_id:%d\tfilename:%s', $slug, $aid, wp_basename( $featured_file ) ) );
            } else {
                pps_log( sprintf( '[IMAGE_NOT_FOUND]\t%s', wp_basename( $featured_file ) ) );
            }
        } else {
            pps_log( sprintf( '[IMAGE_NOT_FOUND]\t%s', wp_basename( $featured_file ) ) );
        }

        // Gallery: glob pattern
        $pattern = $assets_dir . '/' . $slug . '_*.webp';
        $files = glob( $pattern );
        if ( $files && is_array( $files ) ) {
            foreach ( $files as $f ) {
                if ( wp_basename( $f ) === wp_basename( $featured_file ) ) continue; // exclude featured
                if ( ! file_exists( $f ) ) { pps_log( sprintf( '[IMAGE_NOT_FOUND]\t%s', wp_basename( $f ) ) ); continue; }
                $aid2 = pps_resolve_or_import_attachment( $f );
                if ( $aid2 ) $gallery_ids[] = $aid2;
            }
        }

        if ( ! empty( $gallery_ids ) ) {
            update_post_meta( $post_id, '_product_image_gallery', implode( ',', $gallery_ids ) );
            pps_log( sprintf( '[GALLERY_ASSIGNED]\tslug:%s\tgallery_count:%d', $slug, count( $gallery_ids ) ) );
        }

        // Final per-item action log
        if ( isset( $action ) && $action === 'created' ) {
            pps_log( sprintf( '[CREATED]\tslug:%s\tpost_id:%d', $slug, $post_id ) );
        } elseif ( isset( $action ) && $action === 'updated' ) {
            pps_log( sprintf( '[UPDATED]\tslug:%s\tpost_id:%d', $slug, $post_id ) );
        }
    }

    pps_log( 'COMPLETE\tcreated:' . $created . '\tupdated:' . $updated . '\tskipped:' . $skipped );

    return array( 'created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'invalid' => 0, 'log' => pps_log_file() );
}

?>
