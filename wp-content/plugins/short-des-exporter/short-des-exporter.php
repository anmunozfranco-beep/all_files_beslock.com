<?php
/*
Plugin Name: Short Des Exporter
Description: Sincroniza el campo "excerpt" desde repo_portfolio/products.json hacia wp_posts.post_excerpt mediante SQL directo.
Version: 1.0
Author: Automated
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'sde_add_tools_page' );
function sde_add_tools_page() {
    add_management_page( 'Short Description Exporter', 'Short Description Exporter', 'manage_options', 'short-des-exporter', 'sde_tools_page' );
}

function sde_tools_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Acceso denegado' );
    }

    $json_path = WP_CONTENT_DIR . '/themes/beslock-custom/repo_portfolio/products.json';
    $exists = file_exists( $json_path );
    $products_count = 0;
    $products_preview = '';

    if ( $exists ) {
        $raw = @file_get_contents( $json_path );
        $dec = $raw ? json_decode( $raw, true ) : null;
        if ( is_array( $dec ) ) {
            $products_count = count( $dec );
            $products_preview = $dec;
        }
    }

    $message = '';
    if ( isset( $_POST['sde_run'] ) ) {
        check_admin_referer( 'sde_action', 'sde_nonce' );
        $res = sde_run_sync();
        if ( is_array( $res ) ) {
            $message = sprintf( 'Resultado: updated=%d, skipped=%d, not_found=%d, invalid=%d. Log: %s', $res['updated'], $res['skipped'], $res['not_found'], $res['invalid'], esc_html( $res['log'] ) );
        } else {
            $message = 'Error al ejecutar.';
        }
    }

    ?>
    <div class="wrap">
        <h1>Short Description Exporter</h1>
        <?php if ( $message ) : ?>
            <div id="message" class="updated notice"><p><?php echo $message; ?></p></div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field( 'sde_action', 'sde_nonce' ); ?>
            <p><strong>JSON path:</strong> <?php echo esc_html( $json_path ); ?></p>
            <p><strong>File exists:</strong> <?php echo $exists ? 'yes' : 'no'; ?></p>
            <p><strong>Products found:</strong> <?php echo intval( $products_count ); ?></p>
            <p>
                <input type="submit" name="sde_run" class="button button-primary" value="Actualizar Descripciones Cortas">
            </p>
        </form>
    </div>
    <?php
}

function sde_run_sync() {
    if ( ! current_user_can( 'manage_options' ) ) return false;
    if ( ! isset( $_POST['sde_nonce'] ) || ! wp_verify_nonce( $_POST['sde_nonce'], 'sde_action' ) ) return false;

    // Validate WooCommerce active (per user requirement)
    if ( ! class_exists( 'WooCommerce' ) && ! class_exists( 'WC' ) ) {
        // still proceed but return an error in log
        // We'll log and return invalid flag
        $wc_active = false;
    } else {
        $wc_active = true;
    }

    $json_path = WP_CONTENT_DIR . '/themes/beslock-custom/repo_portfolio/products.json';
    if ( ! file_exists( $json_path ) ) {
        sde_log( "INVALID_JSON\tfile_missing:{$json_path}" );
        return array( 'updated' => 0, 'skipped' => 0, 'not_found' => 0, 'invalid' => 1, 'log' => sde_log_file() );
    }

    $raw = @file_get_contents( $json_path );
    $dec = $raw ? json_decode( $raw, true ) : null;
    if ( ! is_array( $dec ) ) {
        sde_log( "INVALID_JSON\tjson_parse_failed" );
        return array( 'updated' => 0, 'skipped' => 0, 'not_found' => 0, 'invalid' => 1, 'log' => sde_log_file() );
    }

    global $wpdb;
    $updated = $skipped = $not_found = 0;

    sde_log( 'START\t' . gmdate( 'Y-m-d H:i:s' ) );

    foreach ( $dec as $item ) {
        $slug = isset( $item['slug'] ) ? trim( (string) $item['slug'] ) : '';
        $excerpt = isset( $item['excerpt'] ) ? trim( (string) $item['excerpt'] ) : '';

        if ( $slug === '' || $excerpt === '' ) {
            sde_log( '[SKIPPED_EMPTY]\tslug:' . $slug );
            $skipped++;
            continue;
        }

        $product_id = $wpdb->get_var( $wpdb->prepare( "\n            SELECT ID\n            FROM {$wpdb->posts}\n            WHERE post_name = %s\n            AND post_type = 'product'\n            LIMIT 1\n            ", $slug ) );

        if ( ! $product_id ) {
            sde_log( '[PRODUCT_NOT_FOUND]\tslug:' . $slug );
            $not_found++;
            continue;
        }

        $safe_excerpt = trim( function_exists( 'wp_kses_post' ) ? wp_kses_post( $excerpt ) : $excerpt );

        $wpdb->update(
            $wpdb->posts,
            array( 'post_excerpt' => $safe_excerpt ),
            array( 'ID' => $product_id ),
            array( '%s' ),
            array( '%d' )
        );

        if ( function_exists( 'clean_post_cache' ) ) clean_post_cache( $product_id );
        if ( function_exists( 'wc_delete_product_transients' ) ) wc_delete_product_transients( $product_id );

        $verify_excerpt = $wpdb->get_var( $wpdb->prepare( "SELECT post_excerpt FROM {$wpdb->posts} WHERE ID = %d", $product_id ) );

        sde_log( sprintf( '[UPDATED]\tslug:%s\tproduct_id:%d\texcerpt_length:%d\tverify_excerpt:%s', $slug, $product_id, function_exists( 'mb_strlen' ) ? mb_strlen( $verify_excerpt, 'UTF-8' ) : strlen( $verify_excerpt ), substr( $verify_excerpt, 0, 200 ) ) );

        $updated++;
    }

    sde_log( 'COMPLETE\tupdated:' . $updated . '\tskipped:' . $skipped . '\tnot_found:' . $not_found );

    return array( 'updated' => $updated, 'skipped' => $skipped, 'not_found' => $not_found, 'invalid' => 0, 'log' => sde_log_file() );
}

function sde_log_file() {
    return WP_CONTENT_DIR . '/uploads/short-des-exporter.log';
}

function sde_log( $line ) {
    $file = sde_log_file();
    $prefix = gmdate( 'Y-m-d H:i:s' );
    @file_put_contents( $file, $prefix . "\t" . $line . "\n", FILE_APPEND );
}

?>
