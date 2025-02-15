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
    add_action('woocommerce_product_options_general_product_data', 'add_custom_product_field');
    add_action('woocommerce_process_product_meta', 'save_custom_product_field');
    add_filter('woocommerce_product_tabs', 'add_custom_tab_with_field');
}
add_action('plugins_loaded', 'moja_wtyczka_init');

// Dodaj custom field do strony edycji produktu
function add_custom_product_field() {
    woocommerce_wp_textarea_input(
        array(
            'id'          => '_custom_tab_content',  // ID pola
            'label'       => __('Kod tabeli wymiarów', 'woocommerce'),
            'placeholder' => __('Podaj shortcode tabeli wymiarów z TablePress', 'woocommerce'),
            'desc_tip'    => 'true',
            'description' => __('Skopiuj shortcode tabeli wymiarów tego produktu z TablePress i wklej go tutaj.', 'woocommerce'),
        )
    );
	
}

// Zapisz wartość custom field po zapisaniu produktu
function save_custom_product_field($post_id) {
    $custom_field_value = isset($_POST['_custom_tab_content']) ? sanitize_textarea_field($_POST['_custom_tab_content']) : '';
    update_post_meta($post_id, '_custom_tab_content', $custom_field_value);
}


// Dodaj nową zakładkę na stronie produktu - zakładka z tabelą wymiarów
function add_custom_tab_with_field($tabs) {
    // Dodajemy nową zakładkę
    $tabs['custom_tab'] = array(
        'title'    => __('Tabela wymiarów', 'woocommerce'),  // Tytuł zakładki
        'priority' => 50,                                         // Pozycja zakładki
        'callback' => 'custom_product_tab_content_with_field'     // Funkcja wywołująca zawartość
    );

    return $tabs;
}


// Zawartość zakładki – wyświetlanie tabeli rozmiarów
function custom_product_tab_content_with_field() {
    global $post;

    // Pobieramy wartość custom field
    $custom_content = get_post_meta($post->ID, '_custom_tab_content', true);

    if (!empty($custom_content)) {
        echo '<h4>Tabela wymiarów</h4>';
        echo do_shortcode(wp_kses_post($custom_content)); // Wyświetl zawartość, zezwalając na podstawowe HTML
    } else {
        echo '<p>' . __('Nie podano wymiarów.', 'woocommerce') . '</p>';
    }
}