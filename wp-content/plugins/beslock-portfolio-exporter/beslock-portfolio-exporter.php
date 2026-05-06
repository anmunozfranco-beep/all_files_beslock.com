<?php
/**
 * Plugin Name: Beslock Portfolio Exporter
 * Description: Exporta productos del portfolio a JSON y SQLite. Añade página en Herramientas para ejecutar la exportación.
 * Version: 0.1
 * Author: Automated
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'beslock_portfolio_exporter_menu' );
function beslock_portfolio_exporter_menu() {
    add_management_page( 'Beslock Portfolio Export', 'Beslock Portfolio Export', 'manage_options', 'beslock-portfolio-export', 'beslock_portfolio_exporter_page' );
}

function beslock_portfolio_exporter_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Acceso denegado' );
    }

    $message = '';
    if ( isset( $_POST['beslock_export'] ) ) {
        check_admin_referer( 'beslock_portfolio_export_action', 'beslock_portfolio_export_nonce' );
        $res = beslock_run_export();
        if ( is_array( $res ) ) {
            $message = sprintf( 'Exportadas %d entradas. JSON: %s %s', $res['count'], esc_html( $res['json'] ), $res['sqlite'] ? ' SQLite: ' . esc_html( $res['sqlite'] ) : '' );
        } else {
            $message = 'Error al exportar.';
        }
    }
    if ( isset( $_POST['beslock_import'] ) ) {
        check_admin_referer( 'beslock_portfolio_export_action', 'beslock_portfolio_export_nonce' );
        $res = beslock_run_import();
        if ( is_array( $res ) ) {
            $message = sprintf( 'Importados: %d creados, %d actualizados, %d errores.', $res['created'], $res['updated'], $res['errors'] );
        } else {
            $message = 'Error al importar.';
        }
    }
    if ( isset( $_POST['beslock_images'] ) ) {
        check_admin_referer( 'beslock_portfolio_export_action', 'beslock_portfolio_export_nonce' );
        $res = beslock_run_images_import();
        if ( is_array( $res ) ) {
            $message = sprintf( 'Imágenes: %d subidas, %d asignadas, %d errores.', $res['uploaded'], $res['assigned'], $res['errors'] );
        } else {
            $message = 'Error al importar imágenes.';
        }
    }
    if ( isset( $_POST['beslock_undo'] ) ) {
        check_admin_referer( 'beslock_portfolio_export_action', 'beslock_portfolio_export_nonce' );
        $res = beslock_run_undo();
        if ( is_array( $res ) ) {
            $message = sprintf( 'Deshacer: %d restaurados, %d eliminados, %d errores.', $res['restored'], $res['deleted'], $res['errors'] );
        } else {
            $message = 'No se encontró respaldo para deshacer.';
        }
    }

    ?>
    <div class="wrap">
        <h1>Beslock Portfolio Export</h1>
        <?php if ( $message ) : ?>
            <div id="message" class="updated notice"><p><?php echo $message; ?></p></div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field( 'beslock_portfolio_export_action', 'beslock_portfolio_export_nonce' ); ?>
            <p>Pulsa el botón para exportar los productos del portfolio a JSON (y SQLite si está disponible).</p>
            <p>
                <input type="submit" name="beslock_export" class="button button-primary" value="Exportar productos">
                <input type="submit" name="beslock_import" class="button" value="Cargar Productos">
                <input type="submit" name="beslock_images" class="button" value="Cargar Imágenes">
                <input type="submit" name="beslock_undo" class="button" value="Deshacer cambios">
            </p>
        </form>
    </div>
    <?php
}

function beslock_run_export() {
    $theme_dir = WP_CONTENT_DIR . '/themes/beslock-custom';
    $repo_dir = $theme_dir . '/repo_portfolio';

    if ( ! is_dir( $repo_dir ) ) {
        if ( ! wp_mkdir_p( $repo_dir ) ) {
            return false;
        }
    }

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    );
    $posts = get_posts( $args );
    $out = array();

    foreach ( $posts as $p ) {
        $id = $p->ID;
        $meta = get_post_meta( $id );
        $price = isset( $meta['_price'][0] ) ? $meta['_price'][0] : '';
        $badge = isset( $meta['beslock_badge'][0] ) ? $meta['beslock_badge'][0] : '';
        $thumb = isset( $meta['_thumbnail_id'][0] ) ? $meta['_thumbnail_id'][0] : '';
        $gallery = get_post_meta( $id, '_product_image_gallery', true );
        $gallery_arr = $gallery ? array_filter( array_map( 'trim', explode( ',', $gallery ) ) ) : array();
        $permalink = get_permalink( $id );

        $dom_description = '';
        if ( $permalink ) {
            $dom_description = beslock_fetch_dom_description( $permalink );
        }

        $out[] = array(
            'ID'              => $id,
            'slug'            => $p->post_name,
            'title'           => html_entity_decode( $p->post_title ),
            'excerpt'         => html_entity_decode( $p->post_excerpt ),
            'content'         => html_entity_decode( $p->post_content ),
            'price'           => $price,
            'badge'           => $badge,
            'meta'            => $meta,
            'gallery_ids'     => $gallery_arr,
            'thumbnail_id'    => $thumb,
            'permalink'       => $permalink,
            'dom_description' => $dom_description,
        );
    }

    $json_path = $repo_dir . '/products.json';

    // Merge with existing products.json by slug/title normalized (case-insensitive)
    function beslock_normalize_key( $s ) {
        if ( ! is_string( $s ) ) return '';
        $s = trim( $s );
        $s = preg_replace( '/\s+/u', ' ', $s );
        if ( function_exists( 'mb_strtolower' ) ) {
            $s = mb_strtolower( $s, 'UTF-8' );
        } else {
            $s = strtolower( $s );
        }
        return $s;
    }

    $existing = array();
    if ( file_exists( $json_path ) ) {
        $raw = @file_get_contents( $json_path );
        $dec = $raw ? json_decode( $raw, true ) : null;
        if ( is_array( $dec ) ) {
            foreach ( $dec as $row ) {
                $rawKey = ! empty( $row['slug'] ) ? $row['slug'] : ( ! empty( $row['title'] ) ? $row['title'] : $row['ID'] );
                $existing[ beslock_normalize_key( $rawKey ) ] = $row;
            }
        }
    }
    foreach ( $out as $row ) {
        $rawKey = ! empty( $row['slug'] ) ? $row['slug'] : ( ! empty( $row['title'] ) ? $row['title'] : $row['ID'] );
        $existing[ beslock_normalize_key( $rawKey ) ] = $row; // overwrite or add
    }
    $combined = array_values( $existing );
    file_put_contents( $json_path, wp_json_encode( $combined, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

    $sqlite_path = $repo_dir . '/products.sqlite';
    $sqlite_written = '';
    if ( class_exists( 'PDO' ) ) {
        try {
            $pdo = new PDO( 'sqlite:' . $sqlite_path );
            $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $pdo->exec( "CREATE TABLE IF NOT EXISTS products (
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
            )" );
            $stmt = $pdo->prepare( "REPLACE INTO products (ID,slug,title,excerpt,content,price,badge,permalink,dom_description,meta_json,gallery,thumbnail_id) VALUES (:ID,:slug,:title,:excerpt,:content,:price,:badge,:permalink,:dom_description,:meta_json,:gallery,:thumbnail_id)" );
            foreach ( $out as $row ) {
                $stmt->execute( array(
                    ':ID' => $row['ID'],
                    ':slug' => $row['slug'],
                    ':title' => $row['title'],
                    ':excerpt' => $row['excerpt'],
                    ':content' => $row['content'],
                    ':price' => $row['price'],
                    ':badge' => $row['badge'],
                    ':permalink' => $row['permalink'],
                    ':dom_description' => $row['dom_description'],
                    ':meta_json' => wp_json_encode( $row['meta'], JSON_UNESCAPED_UNICODE ),
                    ':gallery' => implode( ',', $row['gallery_ids'] ),
                    ':thumbnail_id' => $row['thumbnail_id'],
                ) );
            }
            $sqlite_written = $sqlite_path;
        } catch ( Exception $e ) {
            // ignore sqlite errors, return without sqlite path
        }
    }

    return array( 'json' => $json_path, 'sqlite' => $sqlite_written, 'count' => count( $out ) );
}

function beslock_run_import() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return false;
    }

    $theme_dir = WP_CONTENT_DIR . '/themes/beslock-custom';
    $repo_dir = $theme_dir . '/repo_portfolio';
    $json_path = $repo_dir . '/products.json';
    if ( ! file_exists( $json_path ) ) {
        return false;
    }

    $raw = @file_get_contents( $json_path );
    $rows = $raw ? json_decode( $raw, true ) : null;
    if ( ! is_array( $rows ) ) {
        return false;
    }

    $created = $updated = $errors = 0;

    // create backup of existing products that will be affected
    $backup = array();
    foreach ( $rows as $row ) {
        $slug = ! empty( $row['slug'] ) ? $row['slug'] : sanitize_title( isset($row['title']) ? $row['title'] : '' );
        $existing = get_page_by_path( $slug, OBJECT, 'product' );
        if ( ! $existing ) {
            $posts = get_posts( array( 'post_type' => 'product', 'name' => $slug, 'posts_per_page' => 1 ) );
            if ( ! empty( $posts ) ) $existing = $posts[0];
        }
        if ( $existing ) {
            $post_id = $existing->ID;
            $post = array(
                'ID' => $post_id,
                'post_title' => $existing->post_title,
                'post_name' => $existing->post_name,
                'post_content' => $existing->post_content,
                'post_excerpt' => $existing->post_excerpt,
                'post_status' => $existing->post_status,
            );
            $meta = get_post_meta( $post_id );
            $backup[] = array( 'slug' => $slug, 'pre' => $post, 'meta' => $meta );
        } else {
            // mark as not existing before import
            $backup[] = array( 'slug' => $slug, 'pre' => null, 'meta' => null );
        }
    }
    // write backup file
    $theme_dir = WP_CONTENT_DIR . '/themes/beslock-custom';
    $repo_dir = $theme_dir . '/repo_portfolio';
    if ( ! is_dir( $repo_dir ) ) wp_mkdir_p( $repo_dir );
    $backup_path = $repo_dir . '/products_backup_latest.json';
    $backup_data = array( 'created_at' => gmdate( 'Y-m-d H:i:s' ), 'items' => $backup );
    @file_put_contents( $backup_path, wp_json_encode( $backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

    foreach ( $rows as $row ) {
        $slug = ! empty( $row['slug'] ) ? $row['slug'] : sanitize_title( isset($row['title']) ? $row['title'] : '' );
        $title = ! empty( $row['title'] ) ? $row['title'] : $slug;
        $content = isset( $row['content'] ) ? $row['content'] : '';
        $excerpt = isset( $row['excerpt'] ) ? $row['excerpt'] : '';
        $price = isset( $row['price'] ) ? $row['price'] : '';
        $badge = isset( $row['badge'] ) ? $row['badge'] : '';
        $thumbnail_id = isset( $row['thumbnail_id'] ) ? $row['thumbnail_id'] : '';
        $gallery = isset( $row['gallery_ids'] ) && is_array( $row['gallery_ids'] ) ? implode( ',', $row['gallery_ids'] ) : '';

        // Try find existing product by slug
        $existing = get_page_by_path( $slug, OBJECT, 'product' );
        if ( ! $existing ) {
            // fallback: try WP_Query by name
            $posts = get_posts( array( 'post_type' => 'product', 'name' => $slug, 'posts_per_page' => 1 ) );
            if ( ! empty( $posts ) ) $existing = $posts[0];
        }

        if ( $existing ) {
            $post_id = $existing->ID;
            $update = array(
                'ID' => $post_id,
                'post_title' => wp_strip_all_tags( $title ),
                'post_name' => $slug,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_status' => 'publish',
            );
            $res = wp_update_post( $update, true );
            if ( is_wp_error( $res ) ) {
                $errors++;
                continue;
            }
            // update meta
            if ( $price !== '' ) {
                update_post_meta( $post_id, '_price', $price );
                update_post_meta( $post_id, '_regular_price', $price );
            }
            if ( $badge !== '' ) update_post_meta( $post_id, 'beslock_badge', $badge );
            if ( $thumbnail_id !== '' ) update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );
            if ( $gallery !== '' ) update_post_meta( $post_id, '_product_image_gallery', $gallery );
            $updated++;
        } else {
            $new = array(
                'post_title' => wp_strip_all_tags( $title ),
                'post_name' => $slug,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_status' => 'publish',
                'post_type' => 'product',
            );
            $post_id = wp_insert_post( $new, true );
            if ( is_wp_error( $post_id ) || ! $post_id ) {
                $errors++;
                continue;
            }
            if ( $price !== '' ) {
                update_post_meta( $post_id, '_price', $price );
                update_post_meta( $post_id, '_regular_price', $price );
            }
            if ( $badge !== '' ) update_post_meta( $post_id, 'beslock_badge', $badge );
            if ( $thumbnail_id !== '' ) update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );
            if ( $gallery !== '' ) update_post_meta( $post_id, '_product_image_gallery', $gallery );
            $created++;
        }
    }

    return array( 'created' => $created, 'updated' => $updated, 'errors' => $errors );
}

function beslock_run_undo() {
    if ( ! current_user_can( 'manage_options' ) ) return false;
    $theme_dir = WP_CONTENT_DIR . '/themes/beslock-custom';
    $repo_dir = $theme_dir . '/repo_portfolio';
    if ( ! is_dir( $repo_dir ) ) return false;
    $backup_path = $repo_dir . '/products_backup_latest.json';
    if ( ! file_exists( $backup_path ) ) return false;
    $raw = @file_get_contents( $backup_path );
    $data = $raw ? json_decode( $raw, true ) : null;
    if ( ! is_array( $data ) || empty( $data['items'] ) ) return false;

    $restored = $deleted = $errors = 0;
    foreach ( $data['items'] as $entry ) {
        $slug = isset( $entry['slug'] ) ? $entry['slug'] : '';
        $pre = isset( $entry['pre'] ) ? $entry['pre'] : null;
        $pre_meta = isset( $entry['meta'] ) ? $entry['meta'] : array();

        // find current product by slug
        $existing = get_page_by_path( $slug, OBJECT, 'product' );
        if ( ! $existing ) {
            $posts = get_posts( array( 'post_type' => 'product', 'name' => $slug, 'posts_per_page' => 1 ) );
            if ( ! empty( $posts ) ) $existing = $posts[0];
        }

        if ( $pre === null ) {
            // product didn't exist before import -> delete if exists now
            if ( $existing ) {
                $deleted_flag = wp_delete_post( $existing->ID, true );
                if ( $deleted_flag ) $deleted++; else $errors++;
            }
            continue;
        }

        if ( $existing ) {
            // restore post fields
            $update = array(
                'ID' => $pre['ID'],
                'post_title' => $pre['post_title'],
                'post_name' => $pre['post_name'],
                'post_content' => $pre['post_content'],
                'post_excerpt' => $pre['post_excerpt'],
                'post_status' => $pre['post_status'],
            );
            $res = wp_update_post( $update, true );
            if ( is_wp_error( $res ) ) { $errors++; continue; }
            $post_id = $pre['ID'];
        } else {
            // product existed before but now missing -> re-insert using pre data
            $new = array(
                'ID' => $pre['ID'],
                'post_title' => $pre['post_title'],
                'post_name' => $pre['post_name'],
                'post_content' => $pre['post_content'],
                'post_excerpt' => $pre['post_excerpt'],
                'post_status' => $pre['post_status'],
                'post_type' => 'product',
            );
            $post_id = wp_insert_post( $new, true );
            if ( is_wp_error( $post_id ) || ! $post_id ) { $errors++; continue; }
        }

        // restore meta: set keys from pre_meta and remove keys not present
        $current_meta = get_post_meta( $post_id );
        $pre_meta = is_array( $pre_meta ) ? $pre_meta : array();
        // update/restore keys present in pre
        foreach ( $pre_meta as $mkey => $mval ) {
            // meta values are arrays; restore each
            delete_post_meta( $post_id, $mkey );
            if ( is_array( $mval ) ) {
                foreach ( $mval as $v ) update_post_meta( $post_id, $mkey, $v );
            } else {
                update_post_meta( $post_id, $mkey, $mval );
            }
        }
        // remove keys that are in current but not in pre
        foreach ( $current_meta as $mkey => $mval ) {
            if ( ! array_key_exists( $mkey, $pre_meta ) ) {
                delete_post_meta( $post_id, $mkey );
            }
        }

        $restored++;
    }

    // optionally keep or rename backup; here we rename to indicate restored
    @rename( $backup_path, $backup_path . '.restored' );

    return array( 'restored' => $restored, 'deleted' => $deleted, 'errors' => $errors );
}

function beslock_run_images_import() {
    if ( ! current_user_can( 'manage_options' ) ) return false;

    // assets directory inside theme
    $assets_dir = WP_CONTENT_DIR . '/themes/beslock-custom/assets/images';
    if ( ! is_dir( $assets_dir ) ) return false;

    // get products list
    $args = array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1 );
    $posts = get_posts( $args );

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $uploaded = $assigned = $errors = 0;

    $upload_dir = wp_upload_dir();

    foreach ( $posts as $p ) {
        $slug = $p->post_name;
        if ( ! $slug ) continue;

        // look for main image: slug_.webp
        $main_file = $assets_dir . '/' . $slug . '_.webp';
        $secondary_pattern = $assets_dir . '/' . $slug . '_*.webp';
        $found = glob( $secondary_pattern );

        $attach_ids = array();

        // helper to import a file path into media library
        $import_file = function( $file_path ) use ( $upload_dir, &$errors, &$uploaded ) {
            if ( ! file_exists( $file_path ) ) return false;
            $filename = basename( $file_path );
            $dest = $upload_dir['path'] . '/' . $filename;
            if ( ! @copy( $file_path, $dest ) ) {
                $errors++;
                return false;
            }
            $filetype = wp_check_filetype( $filename, null );
            $attachment = array(
                'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'image/webp',
                'post_title' => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment( $attachment, $dest );
            if ( ! $attach_id ) { $errors++; return false; }
            $meta = wp_generate_attachment_metadata( $attach_id, $dest );
            wp_update_attachment_metadata( $attach_id, $meta );
            $uploaded++;
            return $attach_id;
        };

        // main
        if ( file_exists( $main_file ) ) {
            $aid = $import_file( $main_file );
            if ( $aid ) $attach_ids['main'] = $aid;
        }

        // secondaries - include all matching except the main file (which also matches pattern)
        if ( $found ) {
            foreach ( $found as $f ) {
                // skip exact main
                if ( realpath( $f ) === realpath( $main_file ) ) continue;
                $aid = $import_file( $f );
                if ( $aid ) $attach_ids['gallery'][] = $aid;
            }
        }

        // assign to product
        if ( ! empty( $attach_ids ) ) {
            $post_id = $p->ID;
            if ( ! empty( $attach_ids['main'] ) ) {
                set_post_thumbnail( $post_id, $attach_ids['main'] );
                $assigned++;
            }
            if ( ! empty( $attach_ids['gallery'] ) ) {
                // build gallery string of attachment IDs
                $existing_gallery = get_post_meta( $post_id, '_product_image_gallery', true );
                $gallery_ids = is_string( $existing_gallery ) && $existing_gallery !== '' ? explode( ',', $existing_gallery ) : array();
                // merge without duplicates
                foreach ( $attach_ids['gallery'] as $gid ) if ( ! in_array( $gid, $gallery_ids ) ) $gallery_ids[] = $gid;
                update_post_meta( $post_id, '_product_image_gallery', implode( ',', $gallery_ids ) );
                $assigned += count( $attach_ids['gallery'] );
            }
        }
    }

    return array( 'uploaded' => $uploaded, 'assigned' => $assigned, 'errors' => $errors );
}

function beslock_fetch_dom_description( $url ) {
    $html = '';
    $resp = wp_remote_get( $url, array( 'timeout' => 20 ) );
    if ( ! is_wp_error( $resp ) && isset( $resp['response']['code'] ) && $resp['response']['code'] == 200 ) {
        $html = $resp['body'];
    }
    if ( ! $html ) {
        $opts = stream_context_create( array( 'http' => array( 'timeout' => 20 ) ) );
        $html = @file_get_contents( $url, false, $opts );
    }
    if ( ! $html ) {
        return '';
    }

    libxml_use_internal_errors( true );
    $doc = new DOMDocument();
    @$doc->loadHTML( '<?xml encoding="utf-8">' . $html );
    $xpath = new DOMXPath( $doc );
    $queries = array(
        "//*[contains(@class,'woocommerce-product-details__short-description')]",
        "//*[contains(@class,'short-description')]",
        "//*[contains(@class,'product-description')]",
        "//*[contains(@class,'entry-summary')]",
        "//*[contains(@class,'description')]",
        "//div[@id='product-description']",
    );
    foreach ( $queries as $q ) {
        $nodes = $xpath->query( $q );
        if ( $nodes && $nodes->length ) {
            return trim( $nodes->item(0)->textContent );
        }
    }
    return '';
}
