<?php
/**
 * Plugin Name: Woo Cat Tail
 * Description: Ajoutez facilement un bloc Elementor personnalisé en bas de vos pages de catégories WooCommerce. Associez chaque catégorie à une section Elementor existante ou créez-en une nouvelle directement depuis l’interface d’administration.
 * Author: Itoshuga
 * Version: 1.2.0
 * Requires PHP: 7.4
 * Text Domain: woo-cat-tail
 * Requires Plugins: elementor
 */

if (!defined('ABSPATH')) exit;

define('CAT_TAIL_VERSION', '1.2.0');
define('CAT_TAIL_DIR', plugin_dir_path(__FILE__));
define('CAT_TAIL_URL', plugin_dir_url(__FILE__));
define('CAT_TAIL_META_KEY', '_ims_bottom_elementor_template_id');

require_once CAT_TAIL_DIR . 'includes/Frontend.php';
require_once CAT_TAIL_DIR . 'includes/Admin.php';
require_once CAT_TAIL_DIR . 'includes/Settings.php';

add_action('plugins_loaded', function () {
    // WooCommerce requis pour nos hooks d’archive produit
    if (!class_exists('WooCommerce')) return;

    new \CatTail\Frontend();
    if (is_admin()) {
        new \CatTail\Admin();
        new \CatTail\Settings();
    }
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links){
    $settings_url = admin_url('admin.php?page=woo-cat-tail');
    array_unshift($links, '<a href="' . esc_url($settings_url) . '">' . esc_html__('Réglages', 'cat-tail') . '</a>');
    return $links;
});