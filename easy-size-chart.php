<?php
/**
 * Plugin Name: Easy Size Chart
 * Description: Easy size charts for Woocommerce product pages
 * Version: 1.0
 * Author: Szymon Marszałek
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Rejestrowanie funkcji wtyczki
function moja_wtyczka_init() {
    //add_action('woocommerce_product_options_general_product_data', 'add_custom_product_field');
    add_action('woocommerce_process_product_meta', 'save_custom_product_field');
    add_filter('woocommerce_product_tabs', 'add_custom_tab_with_field');
    add_action('woocommerce_admin_process_product_object', function($product) {
        $product->update_meta_data('_easy_size_chart_enabled', isset($_POST['_easy_size_chart_enabled']) ? 'yes' : 'no');
    });
}
add_action('plugins_loaded', 'moja_wtyczka_init');

add_filter('woocommerce_product_data_tabs', function($tabs) {
    $tabs['easy_size_chart'] = array(
        'label'    => __('Easy Size Chart', 'woocommerce'),
        'target'   => 'easy_size_chart_options',
        'class'    => array('show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external'),
        'priority' => 25, // Decyduje o kolejności zakładek
    );
    return $tabs;
});


add_action('woocommerce_product_data_panels', function() {
    echo '<div id="easy_size_chart_options" class="panel woocommerce_options_panel hidden">';
    echo '<p class="form-field">';
    woocommerce_wp_checkbox(array(
        'id'          => '_easy_size_chart_enabled',
        'label'       => __('Włącz tabelę rozmiarów', 'woocommerce'),
        'description' => __('Zaznacz, aby aktywować tabelę rozmiarów dla tego produktu', 'woocommerce'),
    ));
    woocommerce_wp_text_input(array(
        'id'          => '_custom_field',
        'label'       => __('Kod tabeli wymiarów', 'woocommerce'),
        'description' => __('Skopiuj shortcode tabeli wymiarów tego produktu z TablePress i wklej go tutaj.', 'woocommerce'),
        'desc_tip'    => true,
    ));
    echo '</p>';
    echo '</div>';
});


// Zapisz wartość custom field po zapisaniu produktu
function save_custom_product_field($post_id) {
    $custom_field_value = isset($_POST['_custom_field']) ? sanitize_text_field($_POST['_custom_field']) : '';
    $easy_chart_enabled = isset($_POST['__easy_size_chart_enabled']) ? 'yes': 'no';
    update_post_meta($post_id, '_easy_size_chart_enabled', $easy_chart_enabled);
    update_post_meta($post_id, '_custom_field', $custom_field_value);
}


// Dodaj nową zakładkę na stronie produktu - zakładka z tabelą wymiarów
function add_custom_tab_with_field($tabs) {
    global $post;
    $easy_chart_enabled = get_post_meta($post->ID, '_easy_size_chart_enabled', true);
    if($easy_chart_enabled == 'yes') {
        // Dodajemy nową zakładkę
        $tabs['custom_tab'] = array(
            'title'    => __('Tabela wymiarów', 'woocommerce'),  // Tytuł zakładki
            'priority' => 50,                                         // Pozycja zakładki
            'callback' => 'custom_product_tab_content_with_field'     // Funkcja wywołująca zawartość
        );
    }
    return $tabs;
}


// Zawartość zakładki – wyświetlanie tabeli rozmiarów
function custom_product_tab_content_with_field() {
    global $post;

    // Pobieramy wartość custom field
    $custom_content = get_post_meta($post->ID, '_custom_field', true);

    if (!empty($custom_content)) {
        echo '<h4>Tabela wymiarów</h4>';
        echo do_shortcode(wp_kses_post($custom_content)); // Wyświetl zawartość, zezwalając na podstawowe HTML
    } else {
        echo '<p>' . __('Nie podano wymiarów.', 'woocommerce') . '</p>';
    }
}