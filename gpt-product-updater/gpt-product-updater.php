<?php
/*
Plugin Name: GPT Product Description Updater
Description: Updates WooCommerce product descriptions using ChatGPT API.
Version: 1.0
Author: Ayda
*/

if (!defined('ABSPATH')) {
    exit;
}

// include(plugin_dir_path(__FILE__) . 'ajax-handler.php');
// include(plugin_dir_path(__FILE__) . 'cron-job.php');

add_action('admin_menu', 'gpt_pdu_add_admin_menu');
function gpt_pdu_add_admin_menu() {
    add_menu_page('GPT Product Updater', 'GPT Product Updater', 'manage_options', 'gpt-product-updater', 'gpt_pdu_settings_page');
}

function clean_html_content($content) {
    $allowed_tags = '<img><b><h1><h2><h3><p>';
    return strip_tags($content, $allowed_tags);
}

function gpt_pdu_settings_page() {
    ?>
    <div class="wrap">
        <h1>GPT Product Description Updater</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('gpt_pdu_options_group');
            do_settings_sections('gpt-product-updater');
            submit_button();
            ?>
        </form>
        <button id="update-gpt-descriptions" class="button button-primary">Update Descriptions</button>
    </div>
    <?php
}

add_action('admin_init', 'gpt_pdu_register_settings');
function gpt_pdu_register_settings() {
    register_setting('gpt_pdu_options_group', 'gpt_pdu_api_key');
    register_setting('gpt_pdu_options_group', 'gpt_pdu_auto_update');
    add_settings_section('gpt_pdu_main_section', 'Main Settings', null, 'gpt-product-updater');
    add_settings_field('gpt_pdu_api_key', 'ChatGPT API Key', 'gpt_pdu_api_key_callback', 'gpt-product-updater', 'gpt_pdu_main_section');
    add_settings_field('gpt_pdu_auto_update', 'Enable Auto Update', 'gpt_pdu_auto_update_callback', 'gpt-product-updater', 'gpt_pdu_main_section');
}

function gpt_pdu_api_key_callback() {
    $api_key = get_option('gpt_pdu_api_key');
    echo '<input type="text" name="gpt_pdu_api_key" value="' . esc_attr($api_key) . '" />';
}

function gpt_pdu_auto_update_callback() {
    $auto_update = get_option('gpt_pdu_auto_update');
    echo '<input type="radio" name="gpt_pdu_auto_update" value="yes"' . checked('yes', $auto_update, false) . '> Yes ';
    echo '<input type="radio" name="gpt_pdu_auto_update" value="no"' . checked('no', $auto_update, false) . '> No';
}

add_action('admin_enqueue_scripts', 'gpt_pdu_enqueue_admin_scripts');
function gpt_pdu_enqueue_admin_scripts() {
    wp_enqueue_script('gpt-pdu-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0', true);
    wp_localize_script('gpt-pdu-admin', 'gptPdu', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('woocommerce_product_options_general_product_data', 'gpt_pdu_add_custom_fields');
function gpt_pdu_add_custom_fields() {
    woocommerce_wp_text_input(
        array(
            'id' => '_gpt_status',
            'label' => __('GPT Status', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('Status of the GPT update', 'woocommerce')
        )
    );
}

add_action('woocommerce_process_product_meta', 'gpt_pdu_save_custom_fields');
function gpt_pdu_save_custom_fields($post_id) {
    $gpt_status = isset($_POST['_gpt_status']) ? sanitize_text_field($_POST['_gpt_status']) : '';
    update_post_meta($post_id, '_gpt_status', $gpt_status);
}

add_filter('manage_edit-product_columns', 'gpt_pdu_add_product_columns');
function gpt_pdu_add_product_columns($columns) {
    $columns['gpt_status'] = __('GPT Status', 'woocommerce');
    return $columns;
}

add_action('manage_product_posts_custom_column', 'gpt_pdu_render_product_columns', 10, 2);
function gpt_pdu_render_product_columns($column, $post_id) {
    if ($column == 'gpt_status') {
        $gpt_status = get_post_meta($post_id, '_gpt_status', true);
        echo $gpt_status ? esc_html($gpt_status) : __('Not updated', 'woocommerce');
    }
}

add_action('add_meta_boxes', 'gpt_pdu_add_meta_box');
function gpt_pdu_add_meta_box() {
    add_meta_box(
        'gpt_pdu_meta_box',
        __('GPT Product Description Updater', 'woocommerce'),
        'gpt_pdu_meta_box_callback',
        'product',
        'side'
    );
}

function gpt_pdu_meta_box_callback($post) {
    echo '<button id="gpt-update-current-product" class="button button-primary" data-product-id="' . $post->ID . '">' . __('Update Current Product Description', 'woocommerce') . '</button>';
}

// Add tag to updated products
function gpt_pdu_add_tag_to_product($product_id, $tag_name) {
    $product = wc_get_product($product_id);
    if (!$product) {
        error_log('Product not found with ID: ' . $product_id);
        return;
    }
    wp_set_post_terms($product_id, array($tag_name), 'product_tag', true);
}

// Update current product description and add tag
function gpt_pdu_update_current_product($product_id) {
    if (defined('DOING_CRON') && DOING_CRON) {
        $product_id = intval($product_id);
        if (!$product_id) {
            error_log('Invalid product ID: ' . $product_id);
            return;
        }

        $api_key = get_option('gpt_pdu_api_key');
        if (empty($api_key)) {
            error_log('API key is not set');
            return;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            error_log('Product not found with ID: ' . $product_id);
            return;
        }

        $description = $product->get_description();
        $product_title = $product->get_title();
        $new_description_result = gpt_pdu_get_new_description($api_key, $description, $product_title);

        if ($new_description_result['status']) {
            $new_description = $new_description_result['message'];
            $product->set_description($new_description);
            update_post_meta($product_id, '_gpt_status', 'Updated');
            $product->save();
            gpt_pdu_add_tag_to_product($product_id, 'gpt-updated');
            error_log('Product description updated for product ID: ' . $product_id);
        } else {
            error_log('Failed to update product description for product ID: ' . $product_id . '. Error: ' . $new_description_result['message']);
        }
    } else {
        error_log('gpt_pdu_update_current_product function should only be called during cron jobs.');
    }
}

// Automated description update
function gpt_pdu_update_descriptions_automated() {
    $api_key = get_option('gpt_pdu_api_key');
    if (empty($api_key)) {
        error_log('API key is missing. Exiting.');
        return;
    }

    $processed_tag = 'gpt-updated';

    // Fetch products that haven't been updated or are not tagged as processed
    $products = wc_get_products(array(
        'limit' => 1,
        'orderby' => 'date',
        'order' => 'ASC',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => $processed_tag,
                'operator' => 'NOT IN' // Exclude products with this tag
            )
        ),
        'meta_query' => array(
            array(
                'relation' => 'OR',
                array(
                    'key' => '_gpt_status',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_gpt_status',
                    'value' => 'Updated',
                    'compare' => '!='
                )
            )
        )
    ));

    if (empty($products)) {
        error_log('No products to update.');
        return;
    }

    foreach ($products as $product) {
        gpt_pdu_update_current_product($product->get_id());
        error_log('Updated description for product ID: ' . $product->get_id());
    }
}

// Fetch new description from ChatGPT API
function gpt_pdu_get_new_description($api_key, $description, $product_title) {
    $cleaned_description = clean_html_content($description);

    $url = 'https://api.openai.com/v1/chat/completions';
    $data = array(
        'model' => 'gpt-3.5-turbo',
        'messages' => array(
            array(
                'role' => 'user',
                'content' => 'Rewrite the following product description for clarity and engagement. Product title: ' . $product_title . '. Description: ' . $cleaned_description
            )
        ),
        'temperature' => 0.7,
        'max_tokens' => 150
    );

    $args = array(
        'body'        => json_encode($data),
        'headers'     => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'method'      => 'POST',
        'data_format' => 'body'
    );

    $response = wp_remote_post($url, $args);
    $response_body = wp_remote_retrieve_body($response);

    if (is_wp_error($response)) {
        return array('status' => false, 'message' => $response->get_error_message());
    }

    $response_data = json_decode($response_body, true);
    if (isset($response_data['choices'][0]['message']['content'])) {
        return array('status' => true, 'message' => $response_data['choices'][0]['message']['content']);
    }

    return array('status' => false, 'message' => 'Failed to retrieve description.');
}

add_action('wp_ajax_gpt_pdu_update_current_product', 'gpt_pdu_update_current_product_ajax');



// Add custom cron schedule
add_filter('cron_schedules', 'add_custom_cron_interval');

function add_custom_cron_interval($schedules) {
    $schedules['per_minute'] = array(
        'interval' => 60, // 60 seconds
        'display'  => __('Every Minute')
    );
    return $schedules;
}

function gpt_pdu_update_current_product_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        return;
    }

    if (isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        gpt_pdu_update_current_product($product_id);
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => 'Product ID is missing.'));
    }
}
// Schedule the cron job
// Schedule the cron job
if (!wp_next_scheduled('gpt_pdu_cron_hoook')) {
    wp_schedule_event(time(), 'per_minute', 'gpt_pdu_cron_hoook');
}

// Hook into the cron event
add_action('gpt_pdu_cron_hoook', 'gpt_pdu_update_descriptions_automated');
