<?php
namespace CatTail;

if (!defined('ABSPATH')) exit;

class Settings {

    const OPTION_KEY = 'cat_tail_settings';

    public static function defaults(): array {
        return [
            // Top slot
            'top_insertion_selector'    => '.content-wrapper',
            'top_insertion_position'    => 'beforebegin', // afterend|beforebegin|afterbegin|beforeend
            'top_margin_bottom_mobile'  => 24,
            'top_margin_bottom_desktop' => 32,

            // Bottom slot
            'insertion_selector' => '.content-wrapper',
            'insertion_position' => 'afterend', // afterend|beforebegin|afterbegin|beforeend
            'margin_bottom'      => 150,
            'margin_top_mobile'  => 32,
            'margin_top_desktop' => 40,
        ];
    }

    public static function get_options(): array {
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args($saved, self::defaults());
    }

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu() {
        add_menu_page(
            __('Woo Cat Tail', 'cat-tail'),
            __('Woo Cat Tail', 'cat-tail'),
            'manage_options',
            'woo-cat-tail',
            [$this, 'render_page'],
            'dashicons-pets',
            56
        );
    }

    public function register_settings() {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default'           => self::defaults(),
        ]);
    }

    public function sanitize($input) {
        $out = self::defaults();

        $allowed_pos = ['afterend', 'beforebegin', 'afterbegin', 'beforeend'];

        $out['top_insertion_selector'] = isset($input['top_insertion_selector'])
            ? sanitize_text_field($input['top_insertion_selector'])
            : $out['top_insertion_selector'];

        $top_pos = isset($input['top_insertion_position']) ? sanitize_text_field($input['top_insertion_position']) : '';
        $out['top_insertion_position'] = in_array($top_pos, $allowed_pos, true) ? $top_pos : $out['top_insertion_position'];

        $out['insertion_selector'] = isset($input['insertion_selector'])
            ? sanitize_text_field($input['insertion_selector'])
            : $out['insertion_selector'];

        $bottom_pos = isset($input['insertion_position']) ? sanitize_text_field($input['insertion_position']) : '';
        $out['insertion_position'] = in_array($bottom_pos, $allowed_pos, true) ? $bottom_pos : $out['insertion_position'];

        $out['top_margin_bottom_mobile']  = isset($input['top_margin_bottom_mobile']) ? max(0, (int) $input['top_margin_bottom_mobile']) : $out['top_margin_bottom_mobile'];
        $out['top_margin_bottom_desktop'] = isset($input['top_margin_bottom_desktop']) ? max(0, (int) $input['top_margin_bottom_desktop']) : $out['top_margin_bottom_desktop'];
        $out['margin_bottom']             = isset($input['margin_bottom']) ? max(0, (int) $input['margin_bottom']) : $out['margin_bottom'];
        $out['margin_top_mobile']         = isset($input['margin_top_mobile']) ? max(0, (int) $input['margin_top_mobile']) : $out['margin_top_mobile'];
        $out['margin_top_desktop']        = isset($input['margin_top_desktop']) ? max(0, (int) $input['margin_top_desktop']) : $out['margin_top_desktop'];

        return $out;
    }

    public function enqueue_assets($hook) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'toplevel_page_woo-cat-tail') return;

        wp_enqueue_style('cat-tail-admin', CAT_TAIL_URL . 'assets/admin.css', [], CAT_TAIL_VERSION);
    }

    public function render_page() {
        if (!current_user_can('manage_options')) return;

        $o = self::get_options();
        $pos = ['afterend' => 'afterend', 'beforebegin' => 'beforebegin', 'afterbegin' => 'afterbegin', 'beforeend' => 'beforeend'];
        ?>
        <div class="wrap ims-settings">
          <h1>Woo Cat Tail</h1>

          <form method="post" action="options.php">
            <?php settings_fields(self::OPTION_KEY); ?>

            <div class="ims-card">
              <div class="ims-card__header">
                <div class="ims-card__title">
                  <span class="ims-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="6" rx="2" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="10" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/></svg>
                  </span>
                  Parametres d'insertion
                </div>
              </div>
              <div class="ims-card__body">
                <p class="ims-section-label">Bloc haut</p>
                <div class="ims-subgrid">
                  <div class="ims-mini-card">
                    <label class="ims-mini-card__label" for="cat_tail_top_selector">Selecteur d'insertion</label>
                    <input id="cat_tail_top_selector" type="text" class="regular-text ims-control"
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[top_insertion_selector]"
                           value="<?php echo esc_attr($o['top_insertion_selector']); ?>"
                           placeholder=".content-wrapper">
                    <p class="ims-mini-card__help">Ex : <code>.content-wrapper</code>, <code>#main</code>, <code>.site-inner</code></p>
                  </div>

                  <div class="ims-mini-card">
                    <label class="ims-mini-card__label" for="cat_tail_top_position">Position</label>
                    <select id="cat_tail_top_position" class="ims-control" name="<?php echo esc_attr(self::OPTION_KEY); ?>[top_insertion_position]">
                      <?php foreach ($pos as $val => $label): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($o['top_insertion_position'], $val); ?>>
                          <?php echo esc_html($label); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <p class="ims-mini-card__help">Methode utilisee par <code>insertAdjacentElement</code>.</p>
                  </div>
                </div>

                <p class="ims-section-label">Bloc bas</p>
                <div class="ims-subgrid">
                  <div class="ims-mini-card">
                    <label class="ims-mini-card__label" for="cat_tail_bottom_selector">Selecteur d'insertion</label>
                    <input id="cat_tail_bottom_selector" type="text" class="regular-text ims-control"
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[insertion_selector]"
                           value="<?php echo esc_attr($o['insertion_selector']); ?>"
                           placeholder=".content-wrapper">
                    <p class="ims-mini-card__help">Ex : <code>.content-wrapper</code>, <code>#main</code>, <code>.site-inner</code></p>
                  </div>

                  <div class="ims-mini-card">
                    <label class="ims-mini-card__label" for="cat_tail_bottom_position">Position</label>
                    <select id="cat_tail_bottom_position" class="ims-control" name="<?php echo esc_attr(self::OPTION_KEY); ?>[insertion_position]">
                      <?php foreach ($pos as $val => $label): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($o['insertion_position'], $val); ?>>
                          <?php echo esc_html($label); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <p class="ims-mini-card__help">Methode utilisee par <code>insertAdjacentElement</code>.</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="ims-card ims-card--muted">
              <div class="ims-card__header">
                <div class="ims-card__title">
                  <span class="ims-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                  </span>
                  Espacements
                </div>
              </div>
              <div class="ims-card__body">
                <p class="ims-section-label">Bloc haut</p>
                <div class="ims-subgrid ims-subgrid--compact">
                  <div class="ims-mini-card">
                    <label class="ims-mini-card__label" for="cat_tail_top_margin_mobile">Margin bottom mobile (px)</label>
                    <input id="cat_tail_top_margin_mobile" type="number" min="0" step="1" class="ims-control"
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[top_margin_bottom_mobile]"
                           value="<?php echo esc_attr($o['top_margin_bottom_mobile']); ?>">
                  </div>
                  <div class="ims-mini-card">
                    <label class="ims-mini-card__label" for="cat_tail_top_margin_desktop">Margin bottom desktop (px)</label>
                    <input id="cat_tail_top_margin_desktop" type="number" min="0" step="1" class="ims-control"
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[top_margin_bottom_desktop]"
                           value="<?php echo esc_attr($o['top_margin_bottom_desktop']); ?>">
                  </div>
                </div>

                <p class="ims-section-label">Bloc bas</p>
                <div class="ims-subgrid ims-subgrid--compact">
                  <div class="ims-mini-card">
                    <label class="ims-mini-card__label" for="cat_tail_mb">Margin bottom (px)</label>
                    <input id="cat_tail_mb" type="number" min="0" step="1" class="ims-control"
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[margin_bottom]"
                           value="<?php echo esc_attr($o['margin_bottom']); ?>">
                  </div>
                  <div class="ims-mini-card">
                    <label class="ims-mini-card__label" for="cat_tail_bottom_margin_mobile">Margin top mobile (px)</label>
                    <input id="cat_tail_bottom_margin_mobile" type="number" min="0" step="1" class="ims-control"
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[margin_top_mobile]"
                           value="<?php echo esc_attr($o['margin_top_mobile']); ?>">
                  </div>
                  <div class="ims-mini-card">
                    <label class="ims-mini-card__label" for="cat_tail_bottom_margin_desktop">Margin top desktop (px)</label>
                    <input id="cat_tail_bottom_margin_desktop" type="number" min="0" step="1" class="ims-control"
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[margin_top_desktop]"
                           value="<?php echo esc_attr($o['margin_top_desktop']); ?>">
                  </div>
                </div>
              </div>
            </div>

            <div style="margin-top:16px;">
              <?php submit_button(__('Enregistrer', 'cat-tail')); ?>
            </div>
          </form>
        </div>
        <?php
    }
}
