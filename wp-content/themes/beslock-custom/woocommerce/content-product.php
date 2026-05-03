<?php
/**
 * Override WooCommerce product loop item
 */

defined('ABSPATH') || exit;

global $product;

if (empty($product) || !$product->is_visible()) {
    return;
}

// Detect context
$is_front = is_front_page();
$is_cart_empty = is_cart() && WC()->cart && WC()->cart->is_empty();

// Pass context as variable
set_query_var('beslock_context', [
    'show_description' => $is_front,
]);

get_template_part('template-parts/product-card');

?>
