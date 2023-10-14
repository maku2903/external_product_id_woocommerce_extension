<?php
/*
Plugin Name: WooCommerce Symfonia Product ID
Description: Adds a unique SymfoniaProductId field to WooCommerce products.
Version: 1.0.0
Author: Maciej Pondo
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Add Custom Field in Product Edit Page
function symfonia_product_field() {
    global $post;
    $value = get_post_meta($post->ID, 'symfonia_product_id', true);
    woocommerce_wp_text_input(
        array(
            'id' => 'symfonia_product_id',
            'label' => __('Symfonia Product ID', 'woocommerce'),
            'type' => 'number',
            'value' => $value,  // Add this line to populate the value
            'custom_attributes' => array(
                'step' => '1',
                'min'  => '0'
            )
        )
    );
}
add_action('woocommerce_product_options_general_product_data', 'symfonia_product_field');

// Save Custom Field Value and Ensure Uniqueness
function save_symfonia_product_field($post_id) {
    $symfonia_id = $_POST['symfonia_product_id'];
    
    // Check for duplicate value
    $existing_ids = get_posts(array(
        'post_type' => 'product',
        'meta_key' => 'symfonia_product_id',
        'meta_value' => $symfonia_id,
        'fields' => 'ids'
    ));

    if(empty($symfonia_id) || (!empty($existing_ids) && !in_array($post_id, $existing_ids))) {
        return; // Return without saving if value already exists for another product
    }

    update_post_meta($post_id, 'symfonia_product_id', esc_attr($symfonia_id));
}
add_action('woocommerce_process_product_meta', 'save_symfonia_product_field');

// Display in REST API and Allow Update/Set
function add_symfonia_id_to_rest_api() {
    register_rest_field('product', 'symfonia_product_id', array(
        'get_callback' => function($product) {
            return get_post_meta($product['id'], 'symfonia_product_id', true);
        },
        'update_callback' => function($value, $product) {
            update_post_meta($product->get_id(), 'symfonia_product_id', absint($value));
        },
        'schema' => array(
            'description' => 'Symfonia Product ID',
            'type' => 'integer'
        ),
    ));
}
add_action('rest_api_init', 'add_symfonia_id_to_rest_api');

// Filter by SymfoniaProductId in REST API
function filter_products_by_symfonia_id($args, $request) {
    $symfonia_id = $request->get_param('symfonia_product_id');
    if (!empty($symfonia_id)) {
        $args['meta_query'] = array(
            array(
                'key' => 'symfonia_product_id',
                'value' => $symfonia_id,
            )
        );
    }
    return $args;
}
add_filter('woocommerce_rest_product_object_query', 'filter_products_by_symfonia_id', 10, 2);

// Activation and Deactivation Hooks
register_activation_hook(__FILE__, 'flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
