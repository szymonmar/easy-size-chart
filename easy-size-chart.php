<?php
/**
 * Plugin Name: Easy Size Chart
 * Description: Easy size charts for Woocommerce product pages
 * Version: 1.2.2
 * Author: Szymon Marszałek
 * License: GPL2
 */

// Prohibit direct access
if (!defined('ABSPATH')) {
    die('No direct script access allowed!');
}

// Actions & filters initialization
function easy_size_chart_init() {
    add_action('woocommerce_process_product_meta', 'save_custom_product_field');
    add_action('wp_ajax_modify_global_table_size', 'modify_global_table_size');
    add_action('wp_ajax_nopriv_modify_global_table_size', 'modify_global_table_size');
    add_filter('woocommerce_product_tabs', 'add_custom_tab_with_field');
    wp_enqueue_style(
        'easy-size-chart-style',
        plugin_dir_url(__FILE__) . 'style.css', // CSS file path
        array(),                                // Dependencies (none)
        '1.6',                                  // Version
        'all'
    );
    wp_enqueue_script(
        'easy-size-chart-script', 
        plugin_dir_url(__FILE__) . 'script.js', // Path
        array('jquery'),                        // Dependencies
        '1.0',                                  // Version
        true                                    // Loading in footer (true / false)
    );
}
add_action('plugins_loaded', 'easy_size_chart_init');

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

$g_row_count;   // global table row count
$g_col_count;   // global table column count
$g_chart_data;  // global table data array

// Easy Size Chart options displayed on the Woocommerce edit product page
add_action('woocommerce_product_data_panels', function() {
    global $post, $g_row_count, $g_col_count, $g_chart_data;

    // Getting data from the database
    $tab_title = empty(get_post_meta($post->ID, '_easy_size_chart_tab_title', true))
                    ? 'Size chart' : get_post_meta($post->ID, '_easy_size_chart_tab_title', true);
    $unspecified_text = empty(get_post_meta($post->ID, '_easy_size_chart_unspecified_text', true)) 
                        ? 'Size chart is not specified for this product.' 
                        : get_post_meta($post->ID, '_easy_size_chart_unspecified_text', true);
    $tablepress_shortcode = get_post_meta($post->ID, '_easy_size_chart_tablepress_shortcode', true);
    $enabled = get_post_meta($post->ID, '_easy_size_chart_enabled', true);
    $image_enabled = get_post_meta($post->ID, '_easy_size_chart_image_enabled', true);
    $shortcode_enabled = get_post_meta($post->ID, '_easy_size_chart_shortcode_enabled', true);
    $image_path = get_post_meta($post->ID, '_easy_size_chart_image_path', true);
    $g_row_count = get_post_meta($post->ID, '_easy_size_chart_row_count', true) 
                    ? get_post_meta($post->ID, '_easy_size_chart_row_count', true) : 1;
    $g_col_count = get_post_meta($post->ID, '_easy_size_chart_column_count', true)
                    ? get_post_meta($post->ID, '_easy_size_chart_column_count', true) : 1;
    $g_chart_data = get_post_meta($post->ID, '_easy_size_chart_data', true) 
                    ? get_post_meta($post->ID, '_easy_size_chart_data', true) : array('R0C0' => '');

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
    echo '</p><hr><p class="form-field">';
    // Enable sizing guide image
    woocommerce_wp_checkbox(array(
        'id'          => '_easy_size_chart_image_enabled_cb',
        'label'       => __('Show sizing guide image', 'woocommerce'),
        'description' => __('Select to show sizing guide image on the product page', 'woocommerce'),
        'value'       => $image_enabled === 'yes' ? 'yes' : 'no',
    ));
    // Sizing guide image
    woocommerce_wp_text_input(array(
        'id'          => '_easy_size_chart_image_field',
        'label'       => __('Sizing guide image', 'woocommerce'),
        'description' => __('Upload an image for the size chart or enter the image URL.', 'woocommerce'),
        'desc_tip'    => true,
        'value'       => $image_path,
    ));
    echo '<p class="form-field">';
    echo '<a href="#" class="button upload_image_button">' . __('Browse / Upload', 'woocommerce') . '</a>';
    echo '</p>';
    echo '</p><hr><p class="form-field">';
    woocommerce_wp_checkbox(array(
        'id'          => '_easy_size_chart_shortcode_enabled_cb',
        'label'       => __('Use table shortcode', 'woocommerce'),
        'description' => __('Select to use table shortcode from another plugin (e.g. Tablepress) instead of Easy Size Chart table creator', 'woocommerce'),
        'value'       => $shortcode_enabled === 'yes' ? 'yes' : 'no',
    ));
    // Size chart shortcode from tablepress
    woocommerce_wp_text_input(array(
        'id'          => '_easy_size_chart_tablepress_shortcode_field',
        'label'       => __('Size chart shortcode', 'woocommerce'),
        'description' => __('Copy size chart\'s short code from TablePress and paste it here', 'woocommerce'),
        'desc_tip'    => true,
        'value'       => $tablepress_shortcode,
    ));
    echo '</p><hr><div class="invisible_field">';
    woocommerce_wp_text_input(array(
        'id'                => '_easy_size_chart_row_count_field',
        'label'             => __('Rows', 'woocommerce'),
        'desc_tip'          => false,
        'type'              => 'number',
        'value'             => $g_row_count,
        'custom_attributes' => array(
            'readonly' => 'readonly',
            'hidden' => 'hidden', 
        ),
    ));
    woocommerce_wp_text_input(array(
        'id'                => '_easy_size_chart_column_count_field',
        'label'             => __('Columns', 'woocommerce'),
        'desc_tip'          => false,
        'type'              => 'number',
        'value'             => $g_col_count,
        'custom_attributes' => array(
            'readonly' => 'readonly',
            'hidden' => 'hidden',  
        ),
    ));
    echo '</div><h3 class="size_chart_builder_toolbar">Size chart builder</h3><p class="size_chart_builder_toolbar">';
    echo '<a href="#" class="button add_row_button">' . __('Add row', 'woocommerce') . '</a>   ';
    echo '<a href="#" class="button add_column_button">' . __('Add column', 'woocommerce') . '</a>   ';
    echo '<a href="#" class="button-secondary delete delete_row_button">' . __('Delete row', 'woocommerce') . '</a>   ';
    echo '<a href="#" class="button-secondary delete delete_column_button">' . __('Delete column', 'woocommerce') . '</a>';
    echo '</p><div class="size_chart_builder_module" id="_easy_size_chart_table">';
    echo render_size_chart_panel($g_row_count, $g_col_count, $g_chart_data);
    echo '</div></div>';
});

// Modifies table size when add/remove row/column button is clicked
function modify_global_table_size() {
    global $g_row_count, $g_col_count;
    if (!isset($_POST['post_id'], $_POST['row_count'], $_POST['column_count'], $_POST['table_data'])) {
        wp_send_json_error(['message' => 'Brak wymaganych danych']);
    }
    $g_row_count = intval($_POST['row_count']);
    $g_col_count = intval($_POST['column_count']);
    $data = json_decode(stripslashes($_POST['table_data']), true);


    $new_table_html = render_size_chart_panel($g_row_count, $g_col_count, $data);

    wp_send_json_success(['table_html' => $new_table_html]);
}

// Renders backend builder panel
function render_size_chart_panel($rows, $cols, $data) {

    $content = '<table><tbody>';
    for($row = 0; $row < intval($rows); $row++) {
        $content .= '<tr>';
        for($col = 0; $col < intval($cols); $col++) {
            $id = 'R' . $row . 'C' . $col;
            $content .= '<td>';
            $content .= '<p class="easy_size_chart_cell ' . esc_attr($id) . '_field">';
            $content .= '<input type="text" name="' .  esc_attr($id) . '" style="width: 100%" id="' . esc_attr($id) . '" value="' . esc_attr($data[$id]) . '" /></p>';
            $content .= '</td>';
        }
        $content .= '</tr>';
    }
    $content .= '</tbody></table>';
    return $content;
}

// Renders front-end table from Easy Size Chart builder data
function render_size_chart_output() {
    global $post;
    $row_count = get_post_meta($post->ID, '_easy_size_chart_row_count', true) 
    ? get_post_meta($post->ID, '_easy_size_chart_row_count', true) : 1;
    $col_count = get_post_meta($post->ID, '_easy_size_chart_column_count', true)
    ? get_post_meta($post->ID, '_easy_size_chart_column_count', true) : 1;
    $chart_data = get_post_meta($post->ID, '_easy_size_chart_data', true) 
    ? get_post_meta($post->ID, '_easy_size_chart_data', true) : array('R0C0' => '');
    $content = '<table class="easy_size_chart_frontend_table"><tbody>';
    for($row = 0; $row < intval($row_count); $row++) {
        $content .= '<tr class="easy_size_chart_frontend_table_row">';
        for($col = 0; $col < intval($col_count); $col++) {
            $id = 'R' . $row . 'C' . $col;
            $content .= '<td class="easy_size_chart_frontend_table_cell">';
            $content .= '<p class="easy_size_chart_frontend_cell_content">';
            $content .= esc_attr($chart_data[$id]) . '</p>';
            $content .= '</td>';
        }
        $content .= '</tr>';
    }
    $content .= '</tbody></table>';
    return $content;
}

// Saves custom field content on product update
function save_custom_product_field($post_id) {
    $tablepress_shortcode = isset($_POST['_easy_size_chart_tablepress_shortcode_field']) ? sanitize_text_field($_POST['_easy_size_chart_tablepress_shortcode_field']) : '';
    $tab_title = isset($_POST['_easy_size_chart_tab_title_field']) ? sanitize_text_field($_POST['_easy_size_chart_tab_title_field']) : '';
    $unspecified_text = isset($_POST['_easy_size_chart_unspecified_text_field']) ? sanitize_text_field($_POST['_easy_size_chart_unspecified_text_field']) : '';
    $image_path = isset($_POST['_easy_size_chart_image_field']) ? sanitize_text_field($_POST['_easy_size_chart_image_field']) : '';
    $easy_chart_enabled = isset($_POST['_easy_size_chart_enabled_cb']) ? 'yes': 'no';
    $image_enabled = isset($_POST['_easy_size_chart_image_enabled_cb']) ? 'yes': 'no';
    $shortcode_enabled = isset($_POST['_easy_size_chart_shortcode_enabled_cb']) ? 'yes': 'no';
    $row_count = isset($_POST['_easy_size_chart_row_count_field']) ? sanitize_text_field($_POST['_easy_size_chart_row_count_field']) : 0;
    $col_count = isset($_POST['_easy_size_chart_column_count_field']) ? sanitize_text_field($_POST['_easy_size_chart_column_count_field']) : 0;
    $size_chart_data = [];
    for($row = 0; $row < $row_count; $row++) {
        for($col = 0; $col < $col_count; $col++) {
            $size_chart_data['R' . $row . 'C' . $col] = isset($_POST['R' . $row . 'C' . $col]) ? sanitize_text_field($_POST['R' . $row . 'C' . $col]) : '';
        }
    }
    update_post_meta($post_id, '_easy_size_chart_enabled', $easy_chart_enabled);
    update_post_meta($post_id, '_easy_size_chart_image_enabled', $image_enabled);
    update_post_meta($post_id, '_easy_size_chart_tab_title', $tab_title);
    update_post_meta($post_id, '_easy_size_chart_unspecified_text', $unspecified_text);
    update_post_meta($post_id, '_easy_size_chart_tablepress_shortcode', $tablepress_shortcode);
    update_post_meta($post_id, '_easy_size_chart_image_path', $image_path);
    update_post_meta($post_id, '_easy_size_chart_row_count', $row_count);
    update_post_meta($post_id, '_easy_size_chart_column_count', $col_count);
    update_post_meta($post_id, '_easy_size_chart_data', $size_chart_data);
    update_post_meta($post_id, '_easy_size_chart_shortcode_enabled', $shortcode_enabled);
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
    $image_enabled = get_post_meta($post->ID, '_easy_size_chart_image_enabled', true);
    $shortcode_enabled = get_post_meta($post->ID, '_easy_size_chart_shortcode_enabled', true);

    // if table from shortcode
    if ($shortcode_enabled == 'yes' && !empty($tablepress_shortcode)) {
        if(!empty($tab_title)) {
            echo '<h4>' . $tab_title . '</h4><br>';
        } else {
            echo '<h4>Size chart</h4><br>';
        }
        if($image_enabled == 'yes') {
            $image_path = get_post_meta($post->ID, '_easy_size_chart_image_path', true);
            echo '<div class="easy_size_chart_wrapper">';
            echo '<div class="esc_table_wrapper">';
            echo do_shortcode(wp_kses_post($tablepress_shortcode));
            echo '</div><div class="esc_guide_image_wrapper">';
            echo '<img src="' . $image_path . '"></div></div>';
        } else {
            echo '<div class="easy_size_chart_wrapper">';
            echo do_shortcode(wp_kses_post($tablepress_shortcode));
            echo '</div>';
        }
        // if table from Easy Size Chart builder
    } else if($shortcode_enabled =='no') {
        if(!empty($tab_title)) {
            echo '<h4>' . $tab_title . '</h4><br>';
        } else {
            echo '<h4>Size chart</h4><br>';
        }
        if($image_enabled == 'yes') {
            $image_path = get_post_meta($post->ID, '_easy_size_chart_image_path', true);
            echo '<div class="easy_size_chart_wrapper">';
            echo '<div class="easy_size_chart_table_wrapper">';
            echo render_size_chart_output();
            echo '</div><div class="esc_guide_image_wrapper">';
            echo '<img src="' . $image_path . '"></div></div>';
        } else {
            echo '<div class="easy_size_chart_wrapper">';
            echo render_size_chart_output();
            echo '</div>';
        }
    } else {
        if(!empty($unspecified_text)) {
            echo '<p>' . __($unspecified_text, 'woocommerce') . '</p>';
        } else {
            echo '<p>' . __('Size chart is not specified for this product.', 'woocommerce') . '</p>';
        }
    }
}