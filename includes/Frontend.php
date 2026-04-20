<?php
// includes/Frontend.php
namespace CatTail;

if (!defined('ABSPATH')) exit;

class Frontend {
    /** @var array<string,bool> */
    private $printed = [
        'top' => false,
        'bottom' => false,
    ];

    public function __construct() {
        add_action('woocommerce_before_shop_loop', [$this, 'render_top_template_wrapper'], 5);
        add_action('woocommerce_after_shop_loop', [$this, 'render_bottom_template_wrapper'], 20);
        add_action('wp_footer', [$this, 'render_fallback_wrapper'], 5);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    private function is_product_category(): bool {
        return is_tax('product_cat');
    }

    private function get_current_term_id(): int {
        $term = get_queried_object();
        if (!$term || empty($term->term_id)) {
            return 0;
        }
        return (int) $term->term_id;
    }

    private function get_meta_key_for_slot(string $slot): string {
        return $slot === 'top' ? CAT_TAIL_META_KEY_TOP : CAT_TAIL_META_KEY_BOTTOM;
    }

    private function get_template_id_for_slot(int $term_id, string $slot): int {
        return (int) get_term_meta($term_id, $this->get_meta_key_for_slot($slot), true);
    }

    private function get_wrapper_id_for_slot(string $slot): string {
        return $slot === 'top' ? 'ims-top-el-wrap' : 'ims-bottom-el-wrap';
    }

    private function build_wrapper_html(int $template_id, int $term_id, string $slot): string {
        $html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id);
        if (!$html) {
            return '';
        }

        $slot_class = $slot === 'top' ? 'ims-cat-tail-wrap--top' : 'ims-cat-tail-wrap--bottom';

        return sprintf(
            '<div id="%1$s" class="ims-cat-tail-wrap %2$s" data-ims-term="%3$d" data-ims-slot="%4$s" style="display:block;">%5$s</div>',
            esc_attr($this->get_wrapper_id_for_slot($slot)),
            esc_attr($slot_class),
            (int) $term_id,
            esc_attr($slot),
            $html
        );
    }

    private function render_slot_wrapper(string $slot): void {
        if (!class_exists('\Elementor\Plugin')) return;
        if (!$this->is_product_category()) return;
        if (!isset($this->printed[$slot]) || $this->printed[$slot]) return;

        $term_id = $this->get_current_term_id();
        if (!$term_id) return;

        $template_id = $this->get_template_id_for_slot($term_id, $slot);
        if (!$template_id) return;

        $out = $this->build_wrapper_html($template_id, $term_id, $slot);
        if ($out) {
            echo $out;
            $this->printed[$slot] = true;
        }
    }

    public function render_top_template_wrapper(): void {
        $this->render_slot_wrapper('top');
    }

    public function render_bottom_template_wrapper(): void {
        $this->render_slot_wrapper('bottom');
    }

    // Fallback: if one hook was not triggered by the theme, output in footer then move via JS.
    public function render_fallback_wrapper(): void {
        if (!class_exists('\Elementor\Plugin')) return;
        if (!$this->is_product_category()) return;

        $term_id = $this->get_current_term_id();
        if (!$term_id) return;

        foreach (['top', 'bottom'] as $slot) {
            if (!empty($this->printed[$slot])) {
                continue;
            }

            $template_id = $this->get_template_id_for_slot($term_id, $slot);
            if (!$template_id) {
                continue;
            }

            $out = $this->build_wrapper_html($template_id, $term_id, $slot);
            if ($out) {
                echo $out;
            }
        }
    }

    public function enqueue_assets(): void {
        if (!$this->is_product_category()) return;

        $opts = \CatTail\Settings::get_options();

        wp_enqueue_style('cat-tail-frontend', CAT_TAIL_URL . 'assets/frontend.css', [], CAT_TAIL_VERSION);

        $css = sprintf(
            '.ims-cat-tail-wrap--top{margin-bottom:%1$dpx}@media(min-width:992px){.ims-cat-tail-wrap--top{margin-bottom:%2$dpx}}.ims-cat-tail-wrap--bottom{margin-bottom:%3$dpx;margin-top:%4$dpx}@media(min-width:992px){.ims-cat-tail-wrap--bottom{margin-top:%5$dpx}}',
            (int) $opts['top_margin_bottom_mobile'],
            (int) $opts['top_margin_bottom_desktop'],
            (int) $opts['margin_bottom'],
            (int) $opts['margin_top_mobile'],
            (int) $opts['margin_top_desktop']
        );
        wp_add_inline_style('cat-tail-frontend', $css);

        wp_enqueue_script('cat-tail-frontend', CAT_TAIL_URL . 'assets/frontend.js', [], CAT_TAIL_VERSION, true);
        wp_localize_script('cat-tail-frontend', 'CatTailConfig', [
            'top_selector'    => (string) $opts['top_insertion_selector'],
            'top_position'    => (string) $opts['top_insertion_position'],
            'bottom_selector' => (string) $opts['insertion_selector'],
            'bottom_position' => (string) $opts['insertion_position'],
        ]);
    }
}
