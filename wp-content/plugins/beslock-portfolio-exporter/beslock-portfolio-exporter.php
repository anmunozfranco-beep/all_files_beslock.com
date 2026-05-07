<?php
/*
Plugin Name: Beslock Portfolio Exporter
Description: Exporta productos del portfolio a JSON y SQLite. Añade página en Herramientas para ejecutar la exportación.
Version: 1.0
Author: Automated
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
        try {
            $res = beslock_run_import();
            if ( is_array( $res ) ) {
                $message = sprintf( 'Importados: %d creados, %d actualizados, %d errores. Log: %s', $res['created'], $res['updated'], $res['errors'], isset( $res['log'] ) ? esc_html( $res['log'] ) : '' );
            } else {
                $message = 'Error al importar.';
            }
        } catch ( Throwable $e ) {
            // Log to WP debug.log and show safe admin message
            $msg = sprintf( "[IMPORT_EXCEPTION]\tmsg:%s\tfile:%s\tline:%d\tstack:%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString() );
            @file_put_contents( WP_CONTENT_DIR . '/debug.log', gmdate( 'Y-m-d H:i:s' ) . "\t" . $msg . "\n", FILE_APPEND );
            $message = 'Error al importar (ver registros).';
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
    // Primary canonical source: project data/products.json at repo root when available
    $root_data_path = ABSPATH ? ABSPATH . 'data/products.json' : dirname( dirname( dirname( __FILE__ ) ) ) . '/data/products.json';
    $json_path = '';
    if ( file_exists( $root_data_path ) ) {
        $json_path = $root_data_path;
    } elseif ( file_exists( $theme_dir . '/data/products.json' ) ) {
        $json_path = $theme_dir . '/data/products.json';
    } else {
        $json_path = $repo_dir . '/products.json';
    }
    if ( ! file_exists( $json_path ) ) {
        return false;
    }

    $raw = @file_get_contents( $json_path );
    $rows = $raw ? json_decode( $raw, true ) : null;
    if ( ! is_array( $rows ) ) {
        return false;
    }

    $created = $updated = $errors = $skipped = 0;

    // normalizer helper
    function beslock_normalize_product_data( $raw ) {
        $out = array(
            'slug' => '',
            'title' => '',
            'excerpt' => '',
            'content' => '',
            'price' => '',
            'thumbnail_id' => '',
            'gallery_ids' => array(),
            'badge' => '',
            'features' => array(),
            'source' => '',
        );

        if ( ! is_array( $raw ) ) return $out;

        // detect products_backup_latest.json structure (has 'pre' and 'meta')
        if ( isset( $raw['pre'] ) || isset( $raw['meta'] ) ) {
            $out['source'] = 'backup';
            $pre = isset( $raw['pre'] ) && is_array( $raw['pre'] ) ? $raw['pre'] : array();
            $meta = isset( $raw['meta'] ) && is_array( $raw['meta'] ) ? $raw['meta'] : array();

            $out['slug'] = isset( $raw['slug'] ) ? sanitize_title( $raw['slug'] ) : ( isset( $pre['post_name'] ) ? sanitize_title( $pre['post_name'] ) : '' );
            $out['title'] = isset( $pre['post_title'] ) ? $pre['post_title'] : '';
            $out['excerpt'] = isset( $pre['post_excerpt'] ) ? $pre['post_excerpt'] : '';
            $out['content'] = isset( $pre['post_content'] ) ? $pre['post_content'] : '';
            // price from meta._regular_price[0] or meta._price[0]
            if ( isset( $meta['_regular_price'] ) && is_array( $meta['_regular_price'] ) ) $out['price'] = (string) reset( $meta['_regular_price'] );
            elseif ( isset( $meta['_price'] ) && is_array( $meta['_price'] ) ) $out['price'] = (string) reset( $meta['_price'] );
            // thumbnail
            if ( isset( $meta['_thumbnail_id'] ) && is_array( $meta['_thumbnail_id'] ) ) $out['thumbnail_id'] = (string) reset( $meta['_thumbnail_id'] );
            // gallery
            if ( isset( $meta['_product_image_gallery'] ) && is_array( $meta['_product_image_gallery'] ) ) {
                $g = (string) reset( $meta['_product_image_gallery'] );
                $out['gallery_ids'] = $g !== '' ? array_filter( array_map( 'trim', explode( ',', $g ) ) ) : array();
            }
            if ( isset( $raw['gallery_ids'] ) && is_array( $raw['gallery_ids'] ) ) $out['gallery_ids'] = $raw['gallery_ids'];
            if ( isset( $raw['thumbnail_id'] ) ) $out['thumbnail_id'] = (string) $raw['thumbnail_id'];
            if ( isset( $meta['beslock_badge'] ) && is_array( $meta['beslock_badge'] ) ) $out['badge'] = (string) reset( $meta['beslock_badge'] );
            if ( isset( $meta['beslock_features'] ) && is_array( $meta['beslock_features'] ) ) {
                $feat = maybe_unserialize( reset( $meta['beslock_features'] ) );
                if ( is_array( $feat ) ) $out['features'] = $feat;
            }
            // final normalization: ensure expected types and string values
            $out['slug'] = isset( $out['slug'] ) ? (string) $out['slug'] : '';
            $out['title'] = isset( $out['title'] ) ? (string) $out['title'] : '';
            $out['excerpt'] = isset( $out['excerpt'] ) ? (string) $out['excerpt'] : '';
            $out['content'] = isset( $out['content'] ) ? (string) $out['content'] : '';
            $out['price'] = isset( $out['price'] ) ? (string) $out['price'] : '';
            $out['thumbnail_id'] = isset( $out['thumbnail_id'] ) ? (string) $out['thumbnail_id'] : '';
            $out['gallery_ids'] = isset( $out['gallery_ids'] ) && is_array( $out['gallery_ids'] ) ? array_map( 'strval', $out['gallery_ids'] ) : array();
            return $out;
        }

        // attachment resolver moved to global scope to avoid redeclare when importer runs multiple times

        // detect products.json structure (array of simple product objects)
        $out['source'] = 'products_json';
        $out['slug'] = isset( $raw['slug'] ) ? sanitize_title( $raw['slug'] ) : ( isset( $raw['ID'] ) ? sanitize_title( (string) $raw['ID'] ) : '' );
        $out['title'] = isset( $raw['title'] ) ? $raw['title'] : ( isset( $raw['post_title'] ) ? $raw['post_title'] : '' );
        $out['excerpt'] = isset( $raw['excerpt'] ) ? $raw['excerpt'] : ( isset( $raw['post_excerpt'] ) ? $raw['post_excerpt'] : '' );
        $out['content'] = isset( $raw['content'] ) ? $raw['content'] : ( isset( $raw['post_content'] ) ? $raw['post_content'] : '' );
        $out['price'] = isset( $raw['price'] ) ? (string) $raw['price'] : '';
        if ( isset( $raw['thumbnail_id'] ) ) $out['thumbnail_id'] = (string) $raw['thumbnail_id'];
        if ( isset( $raw['gallery_ids'] ) && is_array( $raw['gallery_ids'] ) ) $out['gallery_ids'] = array_map( 'strval', $raw['gallery_ids'] );
        if ( isset( $raw['badge'] ) ) $out['badge'] = (string) $raw['badge'];
        if ( isset( $raw['meta'] ) && is_array( $raw['meta'] ) ) {
            $meta = $raw['meta'];
            if ( isset( $meta['_regular_price'] ) && is_array( $meta['_regular_price'] ) ) $out['price'] = (string) reset( $meta['_regular_price'] );
            if ( isset( $meta['_thumbnail_id'] ) && is_array( $meta['_thumbnail_id'] ) ) $out['thumbnail_id'] = (string) reset( $meta['_thumbnail_id'] );
            if ( isset( $meta['_product_image_gallery'] ) && is_array( $meta['_product_image_gallery'] ) ) {
                $g = (string) reset( $meta['_product_image_gallery'] );
                $out['gallery_ids'] = $g !== '' ? array_filter( array_map( 'trim', explode( ',', $g ) ) ) : $out['gallery_ids'];
            }
        }
        // final normalization for products_json branch
        $out['slug'] = isset( $out['slug'] ) ? (string) $out['slug'] : '';
        $out['title'] = isset( $out['title'] ) ? (string) $out['title'] : '';
        $out['excerpt'] = isset( $out['excerpt'] ) ? (string) $out['excerpt'] : '';
        $out['content'] = isset( $out['content'] ) ? (string) $out['content'] : '';
        $out['price'] = isset( $out['price'] ) ? (string) $out['price'] : '';
        $out['thumbnail_id'] = isset( $out['thumbnail_id'] ) ? (string) $out['thumbnail_id'] : '';
        $out['gallery_ids'] = isset( $out['gallery_ids'] ) && is_array( $out['gallery_ids'] ) ? array_map( 'strval', $out['gallery_ids'] ) : array();
        return $out;
    }

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
    if ( ! is_dir( $repo_dir ) ) wp_mkdir_p( $repo_dir );
    $backup_path = $repo_dir . '/products_backup_latest.json';
    $backup_data = array( 'created_at' => gmdate( 'Y-m-d H:i:s' ), 'items' => $backup );
    @file_put_contents( $backup_path, wp_json_encode( $backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

    // prepare logging
    $log_dir = WP_CONTENT_DIR . '/themes/beslock-custom/import_logs';
    if ( ! is_dir( $log_dir ) ) wp_mkdir_p( $log_dir );
    $log_file = $log_dir . '/import_' . gmdate( 'Ymd_His' ) . '.log';
    $log_lines = array();
    $log_lines[] = "Import run: " . gmdate( 'Y-m-d H:i:s' );

    // Debugging flags
    $debug_skip_images = true; // set true to skip image/meta assignment during save debugging

    // Top-level try/catch for importer runtime to avoid fatal white-screens
    try {
        foreach ( $rows as $row ) {
            // Map raw JSON excerpt early to ensure importer reads original field
            $slug_raw = '';
            if ( isset( $row['slug'] ) ) {
                $slug_raw = sanitize_title( (string) $row['slug'] );
            } elseif ( isset( $row['title'] ) ) {
                $slug_raw = sanitize_title( (string) $row['title'] );
            }
            $excerpt = '';
            if ( isset( $row['excerpt'] ) ) {
                $excerpt = trim( (string) $row['excerpt'] );
            }
            $len_raw_log = function_exists( 'mb_strlen' ) ? mb_strlen( $excerpt, 'UTF-8' ) : strlen( $excerpt );
            $log_lines[] = sprintf( "[RAW_JSON_EXCERPT]\tslug:%s\texcerpt:%s\tstrlen:%d", $slug_raw, substr( $excerpt, 0, 200 ), $len_raw_log );

            $normalized = beslock_normalize_product_data( $row );

            // detailed normalized debug
            $log_lines[] = sprintf( "[DEBUG_NORMALIZED]\tsource:%s\tslug:%s\ttitle:%s\tprice:%s\tthumbnail:%s\tgallery_count:%d", $normalized['source'], $normalized['slug'], substr( $normalized['title'], 0, 80 ), $normalized['price'], $normalized['thumbnail_id'], count( $normalized['gallery_ids'] ) );

            // validation before creating product
            if ( empty( $normalized['slug'] ) || empty( $normalized['title'] ) ) {
                $log_lines[] = sprintf( "SKIPPED\tMissing slug/title\tslug:%s\ttitle:%s\traw:%s", $normalized['slug'], substr( $normalized['title'],0,60 ), json_encode( $row ) );
                $skipped++;
                continue;
            }
            // price numeric or empty
            if ( $normalized['price'] !== '' && ! is_numeric( $normalized['price'] ) ) {
                $log_lines[] = sprintf( "SKIPPED\tInvalid price\tslug:%s\tprice:%s", $normalized['slug'], $normalized['price'] );
                $skipped++;
                continue;
            }

            $slug = $normalized['slug'];
            $title = $normalized['title'];
            $content = $normalized['content'];
            // keep $excerpt from raw JSON mapping above; do not overwrite with normalized fallback
            $price = $normalized['price'];
            $badge = $normalized['badge'];
            $thumbnail_id = $normalized['thumbnail_id'];
            $gallery = ! empty( $normalized['gallery_ids'] ) ? implode( ',', $normalized['gallery_ids'] ) : '';

            // EXCERPT diagnostics and sanitization (use raw JSON excerpt only)
            $excerpt_raw = isset( $excerpt ) ? $excerpt : '';
            $len_raw = function_exists( 'mb_strlen' ) ? mb_strlen( $excerpt_raw, 'UTF-8' ) : strlen( $excerpt_raw );
            $log_lines[] = sprintf( "[EXCERPT_INPUT]\tslug:%s\traw:%s\tlength:%d\tempty:%s", $slug, substr( $excerpt_raw, 0, 200 ), $len_raw, $excerpt_raw === '' ? 'yes' : 'no' );

            $safe_excerpt = '';
            if ( $excerpt_raw !== '' ) {
                $safe_excerpt = function_exists( 'wp_kses_post' ) ? wp_kses_post( $excerpt_raw ) : $excerpt_raw;
            }
            $len_safe = function_exists( 'mb_strlen' ) ? mb_strlen( $safe_excerpt, 'UTF-8' ) : strlen( $safe_excerpt );
            $log_lines[] = sprintf( "[SAFE_EXCERPT_FINAL]\tslug:%s\tsanitized:%s\tlength:%d", $slug, substr( $safe_excerpt, 0, 200 ), $len_safe );

            // find existing product post by slug
            $existing = get_page_by_path( $slug, OBJECT, 'product' );
            if ( ! $existing ) {
                $posts = get_posts( array( 'post_type' => 'product', 'name' => $slug, 'posts_per_page' => 1 ) );
                if ( ! empty( $posts ) ) $existing = $posts[0];
            }

            try {
                if ( $existing ) {
                    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $existing->ID ) : null;
                    if ( ! $product && class_exists( 'WC_Product_Simple' ) ) {
                        $product = new WC_Product_Simple( $existing->ID );
                    }
                    $is_new = false;
                } else {
                    // Create a minimal WP post first (preserve old import timing and behavior)
                    $post_arr = array(
                        'post_title'   => $title,
                        'post_content' => $content,
                        'post_status'  => 'publish',
                        'post_type'    => 'product',
                        'post_name'    => $slug,
                    );
                    $pid = wp_insert_post( $post_arr );
                    if ( is_wp_error( $pid ) || ! $pid ) {
                        $log_lines[] = sprintf( "FAILED\twp_insert_post failed for slug:%s", $slug );
                        $errors++;
                        continue;
                    }
                    // ensure slug
                    wp_update_post( array( 'ID' => $pid, 'post_name' => $slug ) );
                    if ( function_exists( 'clean_post_cache' ) ) {
                        clean_post_cache( $pid );
                    }

                    // Obtain WC product object for the created post
                    if ( function_exists( 'wc_get_product' ) ) {
                        $product = wc_get_product( $pid );
                    } elseif ( class_exists( 'WC_Product_Simple' ) ) {
                        $product = new WC_Product_Simple( $pid );
                    } else {
                        $product = null;
                    }
                    // Fallback: if we couldn't get WC product object, try generic constructor
                    if ( ! $product && class_exists( 'WC_Product_Simple' ) ) {
                        $product = new WC_Product_Simple();
                    }
                    $is_new = true;
                }

                if ( ! $product ) {
                    $log_lines[] = sprintf( "FAILED\tNo product object for slug:%s", $slug );
                    $errors++;
                    continue;
                }

                // For NEW products: set minimal required fields first
                if ( $is_new ) {
                    if ( method_exists( $product, 'set_name' ) ) $product->set_name( wp_strip_all_tags( $title ) );
                    if ( method_exists( $product, 'set_slug' ) ) $product->set_slug( $slug );
                    if ( method_exists( $product, 'set_status' ) ) $product->set_status( 'publish' );
                    if ( method_exists( $product, 'set_catalog_visibility' ) ) $product->set_catalog_visibility( 'visible' );
                    if ( method_exists( $product, 'set_stock_status' ) ) $product->set_stock_status( 'instock' );
                }

                // set remaining fields using WooCommerce API
                if ( method_exists( $product, 'set_name' ) ) $product->set_name( wp_strip_all_tags( $title ) );
                if ( method_exists( $product, 'set_slug' ) ) $product->set_slug( $slug );
                if ( method_exists( $product, 'set_status' ) ) $product->set_status( 'publish' );
                if ( method_exists( $product, 'set_catalog_visibility' ) ) $product->set_catalog_visibility( 'visible' );
                // NOTE: Short description will be synced via a single SQL-only update
                // at the absolute end of processing for each product. Do not use
                // WooCommerce setters for post_excerpt here.
                if ( method_exists( $product, 'set_description' ) ) $product->set_description( $content );
                if ( $price !== '' && method_exists( $product, 'set_regular_price' ) ) $product->set_regular_price( $price );
                if ( method_exists( $product, 'set_stock_status' ) ) $product->set_stock_status( 'instock' );
                if ( isset( $row['sku'] ) && method_exists( $product, 'set_sku' ) ) $product->set_sku( sanitize_text_field( $row['sku'] ) );

                // Log create vs update
                if ( $is_new ) {
                    $log_lines[] = sprintf( "[CREATE_MODE]\tslug:%s\ttitle:%s", $slug, substr( $title, 0, 80 ) );
                } else {
                    $log_lines[] = sprintf( "[UPDATE_MODE]\tslug:%s\ttitle:%s", $slug, substr( $title, 0, 80 ) );
                }

                // PRE-SAVE DEBUG: log normalized payload and key fields
                $log_lines[] = sprintf( "[PRE_SAVE_DEBUG]\tslug=%s\ttitle=%s\tprice=%s\tstatus=%s\tthumbnail_id=%s\tgallery_count=%d", $slug, substr( $title, 0, 80 ), $price, 'publish', $thumbnail_id, ! empty( $normalized['gallery_ids'] ) ? count( $normalized['gallery_ids'] ) : 0 );
                $log_lines[] = sprintf( "[PRE_SAVE_DEBUG_PAYLOAD]\t%s", json_encode( $normalized ) );

                // Hard validation before save
                $invalid = false;
                if ( empty( $slug ) ) { $log_lines[] = sprintf( "INVALID\tMissing slug\tslug:%s", json_encode( $normalized ) ); $invalid = true; }
                if ( empty( $title ) ) { $log_lines[] = sprintf( "INVALID\tMissing title\tslug:%s", json_encode( $normalized ) ); $invalid = true; }
                if ( $price !== '' && ! is_numeric( $price ) ) { $log_lines[] = sprintf( "INVALID\tNon-numeric price\tslug:%s\tprice:%s", $slug, $price ); $invalid = true; }
                // validate product object getters if available
                if ( method_exists( $product, 'get_name' ) && $product->get_name() === '' ) { $log_lines[] = sprintf( "INVALID\tProduct get_name empty\tslug:%s", $slug ); $invalid = true; }
                if ( method_exists( $product, 'get_slug' ) && $product->get_slug() === '' ) { $log_lines[] = sprintf( "INVALID\tProduct get_slug empty\tslug:%s", $slug ); $invalid = true; }
                if ( $invalid ) { $skipped++; continue; }

                // attempt save with try/catch and detailed logging
                try {
                    $new_id = $product->save();
                } catch ( Throwable $e ) {
                    $errors++;
                    $log_lines[] = sprintf( "[FAILED_SAVE]\tslug:%s\tis_new:%s\tmessage:%s", $slug, $is_new ? 'yes' : 'no', $e->getMessage() );
                    $log_lines[] = sprintf( "[FAILED_SAVE_STACK]\t%s", $e->getTraceAsString() );
                    $log_lines[] = sprintf( "[FAILED_SAVE_PAYLOAD]\t%s", json_encode( $normalized ) );

                    // Try minimal product save (name,slug,status,price) to isolate metadata/media failures
                    try {
                        $min_product = new WC_Product_Simple();
                        if ( method_exists( $min_product, 'set_name' ) ) $min_product->set_name( wp_strip_all_tags( $title ) );
                        if ( method_exists( $min_product, 'set_slug' ) ) $min_product->set_slug( $slug );
                        if ( method_exists( $min_product, 'set_status' ) ) $min_product->set_status( 'publish' );
                        if ( $price !== '' && method_exists( $min_product, 'set_regular_price' ) ) $min_product->set_regular_price( $price );
                        // If excerpt present, capture it but do not set via WC API here.
                        $excerpt_trim_min = isset( $excerpt ) ? trim( $excerpt ) : '';
                        $min_id = $min_product->save();
                        if ( $min_id ) {
                            $log_lines[] = sprintf( "[MINIMAL_SAVE_OK]\tslug:%s\tid:%d", $slug, $min_id );
                            $created++; // consider as created minimal
                            // Do not sync post_excerpt here; final SQL-only sync will
                            // run after all image/meta assignments below.
                            // continue to next item without assigning images/meta
                            continue;
                        } else {
                            $log_lines[] = sprintf( "[MINIMAL_SAVE_FAILED]\tslug:%s", $slug );
                        }
                    } catch ( Throwable $e2 ) {
                        $log_lines[] = sprintf( "[MINIMAL_SAVE_EXCEPTION]\tslug:%s\tmsg:%s\tstack:%s", $slug, $e2->getMessage(), $e2->getTraceAsString() );
                    }

                    continue;
                }

                if ( ! $new_id ) {
                    $log_lines[] = sprintf( "FAILED\tSave returned falsy for slug:%s", $slug );
                    $errors++;
                    continue;
                }

                $post_id = $new_id;
                // verify product loaded via wc_get_product
                $saved_product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
                if ( ! $saved_product ) {
                    $log_lines[] = sprintf( "LOOKUP_MISSING_AFTER_SAVE\tslug:%s\tproduct_id:%d", $slug, $post_id );
                }
                if ( function_exists( 'wp_set_object_terms' ) ) wp_set_object_terms( $post_id, 'simple', 'product_type' );
                if ( function_exists( 'wc_delete_product_transients' ) ) wc_delete_product_transients( $post_id );

                // assign thumbnail/gallery using attachment resolution (preserve reuse and filename matching)
                if ( empty( $debug_skip_images ) ) {
                    // thumbnail may be an ID, URL, or filename
                    if ( ! empty( $thumbnail_id ) ) {
                        $resolved = beslock_resolve_attachment_from_identifier( $thumbnail_id );
                        if ( $resolved ) {
                            // prefer set_post_thumbnail for WP correctness
                            set_post_thumbnail( $post_id, intval( $resolved ) );
                        } else {
                            // if raw numeric, still attempt meta write
                            if ( is_numeric( $thumbnail_id ) ) update_post_meta( $post_id, '_thumbnail_id', intval( $thumbnail_id ) );
                        }
                    }

                    if ( $gallery !== '' ) {
                        $gallery_ids = array();
                        $parts = array_filter( array_map( 'trim', explode( ',', $gallery ) ) );
                        foreach ( $parts as $p ) {
                            $res = beslock_resolve_attachment_from_identifier( $p );
                            if ( $res ) $gallery_ids[] = intval( $res );
                        }
                        if ( ! empty( $gallery_ids ) ) {
                            update_post_meta( $post_id, '_product_image_gallery', implode( ',', $gallery_ids ) );
                        }
                    }
                }

                    // FINAL_SQL_EXCERPT_SYNC: single definitive SQL-only sync of post_excerpt
                    // Use the raw JSON excerpt field as the source (captured above in $excerpt_raw)
                    $excerpt_to_write = isset( $excerpt_raw ) ? $excerpt_raw : '';
                    $log_lines[] = sprintf( "[RAW_EXCERPT_INPUT]\tslug:%s\traw:%s", $slug, substr( $excerpt_to_write, 0, 200 ) );
                    if ( $excerpt_to_write !== '' ) {
                        global $wpdb;
                        $wpdb->update( $wpdb->posts,
                            array( 'post_excerpt' => $excerpt_to_write ),
                            array( 'ID' => $post_id ),
                            array( '%s' ),
                            array( '%d' )
                        );
                        if ( function_exists( 'clean_post_cache' ) ) clean_post_cache( $post_id );
                        if ( function_exists( 'wc_delete_product_transients' ) ) wc_delete_product_transients( $post_id );
                        $verify_excerpt = $wpdb->get_var( $wpdb->prepare( "SELECT post_excerpt FROM {$wpdb->posts} WHERE ID = %d", $post_id ) );
                        $log_lines[] = sprintf( "[FINAL_SQL_EXCERPT_SYNC]\tslug:%s\tid:%d\texcerpt:%s", $slug, $post_id, substr( $verify_excerpt, 0, 200 ) );
                    }

                if ( $is_new ) {
                    $created++;
                    $log_lines[] = sprintf( "CREATED\tID:%d\tslug:%s\ttitle:%s", $post_id, $slug, $title );
                } else {
                    $updated++;
                    $log_lines[] = sprintf( "UPDATED\tID:%d\tslug:%s\ttitle:%s", $post_id, $slug, $title );
                }

            } catch ( Throwable $e ) {
                $errors++;
                $log_lines[] = sprintf( "FAILED\tException for slug:%s\tmsg:%s", $slug, $e->getMessage() );
                // per-product throwable logged, continue to next product
                continue;
            }
        }
    } catch ( Throwable $e_main ) {
        // Top-level importer exception; log to both plugin log and WP debug.log
        $err_msg = sprintf( "[FATAL_RUNTIME]\tIMPORT_EXCEPTION\tmsg:%s\tfile:%s\tline:%d\tstack:%s", $e_main->getMessage(), $e_main->getFile(), $e_main->getLine(), $e_main->getTraceAsString() );
        $log_lines[] = $err_msg;
        @file_put_contents( WP_CONTENT_DIR . '/debug.log', gmdate( 'Y-m-d H:i:s' ) . "\t" . $err_msg . "\n", FILE_APPEND );
    }

    // sync lookup tables after import
    if ( function_exists( 'wc_update_product_lookup_tables' ) ) {
        try { wc_update_product_lookup_tables(); $log_lines[] = 'Ran wc_update_product_lookup_tables()'; } catch ( Exception $e ) { $log_lines[] = 'wc_update_product_lookup_tables() failed: ' . $e->getMessage(); }
    }

    // verify lookup presence per slug
    global $wpdb;
    foreach ( $rows as $row ) {
        $slug_check = ! empty( $row['slug'] ) ? sanitize_title( $row['slug'] ) : sanitize_title( isset( $row['title'] ) ? $row['title'] : '' );
        $found = $wpdb->get_var( $wpdb->prepare( "SELECT product_id FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_name=%s LIMIT 1) LIMIT 1", $slug_check ) );
        if ( ! $found ) {
            $log_lines[] = sprintf( "LOOKUP_MISSING\tslug:%s", $slug_check );
        } else {
            $log_lines[] = sprintf( "LOOKUP_OK\tslug:%s\tproduct_id:%d", $slug_check, intval( $found ) );
        }
    }

    // write log file
    $log_lines[] = sprintf( "Summary: Created:%d Updated:%d Errors:%d", $created, $updated, $errors );
    @file_put_contents( $log_file, implode( "\n", $log_lines ) );

    return array( 'created' => $created, 'updated' => $updated, 'errors' => $errors, 'log' => $log_file );
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
            // restore using WooCommerce API for product fields
            if ( function_exists( 'wc_get_product' ) ) {
                $product = wc_get_product( $existing->ID );
            } elseif ( class_exists( 'WC_Product_Simple' ) ) {
                $product = new WC_Product_Simple( $existing->ID );
            } else {
                $errors++; continue;
            }

            if ( ! $product ) { $errors++; continue; }

            if ( method_exists( $product, 'set_name' ) ) $product->set_name( wp_strip_all_tags( $pre['post_title'] ) );
            if ( method_exists( $product, 'set_slug' ) ) $product->set_slug( $pre['post_name'] );
            if ( method_exists( $product, 'set_status' ) ) $product->set_status( $pre['post_status'] );
            if ( method_exists( $product, 'set_description' ) ) $product->set_description( $pre['post_content'] );
            if ( method_exists( $product, 'set_short_description' ) ) $product->set_short_description( $pre['post_excerpt'] );

            $post_id = $product->save();
            if ( ! $post_id ) { $errors++; continue; }
        } else {
            // product existed before but now missing -> re-create using WooCommerce API
            if ( ! class_exists( 'WC_Product_Simple' ) ) { $errors++; continue; }
            $product = new WC_Product_Simple();
            if ( method_exists( $product, 'set_name' ) ) $product->set_name( wp_strip_all_tags( $pre['post_title'] ) );
            if ( method_exists( $product, 'set_slug' ) ) $product->set_slug( $pre['post_name'] );
            if ( method_exists( $product, 'set_status' ) ) $product->set_status( $pre['post_status'] );
            if ( method_exists( $product, 'set_description' ) ) $product->set_description( $pre['post_content'] );
            if ( method_exists( $product, 'set_short_description' ) ) $product->set_short_description( $pre['post_excerpt'] );

            $post_id = $product->save();
            if ( ! $post_id ) { $errors++; continue; }
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

        // helper to import a file path into media library, reusing attachments by filename
        $import_file = function( $file_path ) use ( $upload_dir, &$errors, &$uploaded ) {
            if ( ! file_exists( $file_path ) ) return false;
            global $wpdb;
            $filename = basename( $file_path );

            // try to find existing attachment by meta _wp_attached_file ending with filename
            $like = '%' . $wpdb->esc_like( $filename );
            $existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s LIMIT 1", $like ) );
            if ( $existing_id ) {
                return intval( $existing_id );
            }

            // ensure destination path
            $dest = $upload_dir['path'] . '/' . $filename;
            if ( ! file_exists( $dest ) ) {
                if ( ! @copy( $file_path, $dest ) ) {
                    $errors++;
                    return false;
                }
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

// Helper: normalize key used by export merging
if ( ! function_exists( 'beslock_normalize_key' ) ) {
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
}

// Helper: Resolve an attachment identifier to an attachment ID.
// Accepts numeric IDs, attachment URLs, filenames, or partial filenames.
// Returns attachment ID or false.
if ( ! function_exists( 'beslock_resolve_attachment_from_identifier' ) ) {
    function beslock_resolve_attachment_from_identifier( $ident ) {
        if ( empty( $ident ) ) return false;
        // numeric id
        if ( is_numeric( $ident ) ) {
            $aid = intval( $ident );
            if ( get_post_type( $aid ) === 'attachment' ) return $aid;
        }
        // URL -> try attachment_url_to_postid
        if ( is_string( $ident ) && ( strpos( $ident, 'http://' ) === 0 || strpos( $ident, 'https://' ) === 0 ) ) {
            if ( function_exists( 'attachment_url_to_postid' ) ) {
                $found = attachment_url_to_postid( $ident );
                if ( $found ) return $found;
            }
            // fallback to basename
            $ident = wp_basename( $ident );
        }
        // filename or partial filename
        $filename = wp_basename( (string) $ident );
        if ( ! $filename ) return false;
        global $wpdb;
        $like = '%' . $wpdb->esc_like( $filename ) . '%';
        $row = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s LIMIT 1", $like ) );
        if ( $row ) return intval( $row );
        return false;
    }
}
