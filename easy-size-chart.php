<?php
/**
 * Plugin Name: Easy Size Chart
 * Description: Easy size charts for Woocommerce product pages
 * Version: 1.1
 * Author: Szymon Marszałek
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Actions & filters initialization
function moja_wtyczka_init() {
    add_action('woocommerce_process_product_meta', 'save_custom_product_field');
    add_filter('woocommerce_product_tabs', 'add_custom_tab_with_field');
}
add_action('plugins_loaded', 'moja_wtyczka_init');

// Adds Easy Size Chart options on the Woocommerce edit product page
add_filter('woocommerce_product_data_tabs', function($tabs) {
    $tabs['easy_size_chart'] = array(
        'label'    => __('Easy Size Chart', 'woocommerce'),
        'target'   => 'easy_size_chart_options',
        'class'    => array('show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external'),
        'priority' => 25,
    );
    return $tabs;
});

// Easy Size Chart options displayed on the Woocommerce edit product page
add_action('woocommerce_product_data_panels', function() {
    global $post;

    // Getting data from the database
    $tab_title = empty(get_post_meta($post->ID, '_easy_size_chart_tab_title', true))
                    ? 'Size chart' : get_post_meta($post->ID, '_easy_size_chart_tab_title', true);
    $unspecified_text = empty(get_post_meta($post->ID, '_easy_size_chart_unspecified_text', true)) 
                        ? 'Size chart is not specified for this product.' 
                        : get_post_meta($post->ID, '_easy_size_chart_unspecified_text', true);
    $tablepress_shortcode = get_post_meta($post->ID, '_easy_size_chart_tablepress_shortcode', true);
    $enabled = get_post_meta($post->ID, '_easy_size_chart_enabled', true);


    echo '<div id="easy_size_chart_options" class="panel woocommerce_options_panel hidden">';
    echo '<p class="form-field">';
    // Enable size chart checkbox
    woocommerce_wp_checkbox(array(
        'id'          => '_easy_size_chart_enabled_cb',
        'label'       => __('Activate size chart', 'woocommerce'),
        'description' => __('Select to activate size chart for this product', 'woocommerce'),
        'value'       => $enabled === 'yes' ? 'yes' : 'no',
    ));
    // Size chart tab title
    woocommerce_wp_text_input(array(
        'id'          => '_easy_size_chart_tab_title_field',
        'label'       => __('Tab title', 'woocommerce'),
        'description' => __('Enter title of size chart tab on the product site', 'woocommerce'),
        'desc_tip'    => true,
        'value'       => $tab_title,
    ));
    // Size chart unspecified display text
    woocommerce_wp_text_input(array(
        'id'          => '_easy_size_chart_unspecified_text_field',
        'label'       => __('"Size chart unspecified" text', 'woocommerce'),
        'description' => __('Enter text You want to display in the size chart tab when size chart is not specified.', 'woocommerce'),
        'desc_tip'    => true,
        'value'       => $unspecified_text,
    ));
    // Size chart shortcode from tablepress
    woocommerce_wp_text_input(array(
        'id'          => '_easy_size_chart_tablepress_shortcode_field',
        'label'       => __('Size chart shortcode', 'woocommerce'),
        'description' => __('Copy size chart\'s short code from TablePress and paste it here', 'woocommerce'),
        'desc_tip'    => true,
        'value'       => $tablepress_shortcode,
    ));
    echo '</p>';
    echo '</div>';
});


// Saves custom field content on product update
function save_custom_product_field($post_id) {
    $tablepress_shortcode = isset($_POST['_easy_size_chart_tablepress_shortcode_field']) ? sanitize_text_field($_POST['_easy_size_chart_tablepress_shortcode_field']) : '';
    $tab_title = isset($_POST['_easy_size_chart_tab_title_field']) ? sanitize_text_field($_POST['_easy_size_chart_tab_title_field']) : '';
    $unspecified_text = isset($_POST['_easy_size_chart_unspecified_text_field']) ? sanitize_text_field($_POST['_easy_size_chart_unspecified_text_field']) : '';
    $easy_chart_enabled = isset($_POST['_easy_size_chart_enabled_cb']) ? 'yes': 'no';
    update_post_meta($post_id, '_easy_size_chart_enabled', $easy_chart_enabled);
    update_post_meta($post_id, '_easy_size_chart_tab_title', $tab_title);
    update_post_meta($post_id, '_easy_size_chart_unspecified_text', $unspecified_text);
    update_post_meta($post_id, '_easy_size_chart_tablepress_shortcode', $tablepress_shortcode);
}


// Adds a new tab on the product site - size chart tab
function add_custom_tab_with_field($tabs) {
    global $post;
    $easy_chart_enabled = get_post_meta($post->ID, '_easy_size_chart_enabled', true);
    $tab_title = get_post_meta($post->ID, '_easy_size_chart_tab_title', true);
    if($easy_chart_enabled == 'yes') {
        // Adding a new tab
        if(!empty($tab_title)) {
            $tabs['custom_tab'] = array(
                'title'    => __($tab_title, 'woocommerce'),        // Tab title
                'priority' => 50,                                   // Tab position
                'callback' => 'easy_size_chart_tab_callback'        // Callback function
            );
        } else {
            $tabs['custom_tab'] = array(
                'title'    => __('Size chart', 'woocommerce'),      // Tab title
                'priority' => 50,                                   // Tab position
                'callback' => 'easy_size_chart_tab_callback'        // Callback function
            );
        }
        
    }
    return $tabs;
}


// Displays size chart on the product page in the size chart tab
function easy_size_chart_tab_callback() {
    global $post;

    // Gets data from the db
    $tablepress_shortcode = get_post_meta($post->ID, '_easy_size_chart_tablepress_shortcode', true);
    $unspecified_text = get_post_meta($post->ID, '_easy_size_chart_unspecified_text', true);
    $tab_title = get_post_meta($post->ID, '_easy_size_chart_tab_title', true);

    if (!empty($tablepress_shortcode)) {
        if(!empty($tab_title)) {
            echo '<h4>' . $tab_title . '</h4><br>';
        } else {
            echo '<h4>Size chart</h4><br>';
        }
        echo do_shortcode(wp_kses_post($tablepress_shortcode));
    } else {
        if(!empty($unspecified_text)) {
            echo '<p>' . __($unspecified_text, 'woocommerce') . '</p>';
        } else {
            echo '<p>' . __('Size chart is not specified for this product.', 'woocommerce') . '</p>';
        }
    }
}