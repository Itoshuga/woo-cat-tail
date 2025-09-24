<?php
// includes/Frontend.php
namespace CatTail;

if (!defined('ABSPATH')) exit;

class Frontend {
    private $printed = false;

    public function __construct() {
        add_action('woocommerce_after_shop_loop', [$this, 'render_template_wrapper'], 20);
        add_action('wp_footer', [$this, 'render_fallback_wrapper'], 5); // fallback si rien n’a été imprimé
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    private function is_product_category() : bool {
        return is_tax('product_cat');
    }

    private function build_wrapper_html($template_id, $term_id){
        $html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id);
        if (!$html) return '';
        return '<div id="ims-bottom-el-wrap" class="ims-bottom-el-wrap" data-ims-term="' . (int)$term_id . '" style="display:block;">' . $html . '</div>';
    }

    public function render_template_wrapper() {
        if (!class_exists('\Elementor\Plugin')) return;
        if (!$this->is_product_category()) return;

        $term = get_queried_object();
        if (!$term || empty($term->term_id)) return;

        $template_id = (int) get_term_meta($term->term_id, CAT_TAIL_META_KEY, true);
        if (!$template_id) return;

        $out = $this->build_wrapper_html($template_id, $term->term_id);
        if ($out) {
            echo $out;
            $this->printed = true;
        }
    }

    // Fallback : si le hook WooCommerce ne s’est pas déclenché, on imprime dans le footer (le JS le déplacera)
    public function render_fallback_wrapper() {
        if ($this->printed) return;
        if (!class_exists('\Elementor\Plugin')) return;
        if (!$this->is_product_category()) return;

        $term = get_queried_object();
        if (!$term || empty($term->term_id)) return;

        $template_id = (int) get_term_meta($term->term_id, CAT_TAIL_META_KEY, true);
        if (!$template_id) return;

        $out = $this->build_wrapper_html($template_id, $term->term_id);
        if ($out) echo $out;
    }

    public function enqueue_assets() {
        if (!$this->is_product_category()) return;

        $opts = \CatTail\Settings::get_options();

        wp_enqueue_style('cat-tail-frontend', CAT_TAIL_URL . 'assets/frontend.css', [], CAT_TAIL_VERSION);

        $css = sprintf(
            '.ims-bottom-el-wrap{margin-bottom:%dpx;margin-top:%dpx}@media(min-width:992px){.ims-bottom-el-wrap{margin-top:%dpx}}',
            (int)$opts['margin_bottom'],
            (int)$opts['margin_top_mobile'],
            (int)$opts['margin_top_desktop']
        );
        wp_add_inline_style('cat-tail-frontend', $css);

        wp_enqueue_script('cat-tail-frontend', CAT_TAIL_URL . 'assets/frontend.js', [], CAT_TAIL_VERSION, true);
        wp_localize_script('cat-tail-frontend', 'CatTailConfig', [
            'selector' => (string)$opts['insertion_selector'],
            'position' => (string)$opts['insertion_position'],
        ]);
    }
}
