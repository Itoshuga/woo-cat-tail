<?php
namespace CatTail;

if (!defined('ABSPATH')) exit;

class Settings {

    const OPTION_KEY = 'cat_tail_settings';

    public static function defaults(): array {
        return [
            'insertion_selector' => '.content-wrapper',
            'insertion_position' => 'afterend', // afterend|beforebegin|afterbegin|beforeend
            'margin_bottom'      => 150,
            'margin_top_mobile'  => 32,  // 2rem ≈ 32px
            'margin_top_desktop' => 40,  // 2.5rem ≈ 40px
        ];
    }

    public static function get_options(): array {
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args($saved, self::defaults());
    }

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']); // +++
    }

    public function add_menu() {
        add_menu_page(
            __('Woo Cat Tail', 'cat-tail'),
            __('Woo Cat Tail', 'cat-tail'),
            'manage_options',
            'woo-cat-tail',
            [$this, 'render_page'],
            'dashicons-pets', // 🐾 style animal
            56
        );
    }

    public function register_settings() {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default'           => self::defaults(),
        ]);

        add_settings_section('cat_tail_main', '', '__return_false', 'woo-cat-tail');

        add_settings_field('insertion_selector', __('Sélecteur d’insertion', 'cat-tail'),
            [$this, 'field_selector'], 'woo-cat-tail', 'cat_tail_main');

        add_settings_field('insertion_position', __('Position', 'cat-tail'),
            [$this, 'field_position'], 'woo-cat-tail', 'cat_tail_main');

        add_settings_field('margin_bottom', __('Margin bottom (px)', 'cat-tail'),
            [$this, 'field_margin_bottom'], 'woo-cat-tail', 'cat_tail_main');

        add_settings_field('margins_top', __('Margins top (px)', 'cat-tail'),
            [$this, 'field_margins_top'], 'woo-cat-tail', 'cat_tail_main');
    }

    public function sanitize($input) {
        $out = self::defaults();

        $out['insertion_selector'] = isset($input['insertion_selector'])
            ? sanitize_text_field($input['insertion_selector']) : $out['insertion_selector'];

        $allowed_pos = ['afterend','beforebegin','afterbegin','beforeend'];
        $pos = isset($input['insertion_position']) ? sanitize_text_field($input['insertion_position']) : '';
        $out['insertion_position'] = in_array($pos, $allowed_pos, true) ? $pos : $out['insertion_position'];

        $out['margin_bottom']      = isset($input['margin_bottom']) ? intval($input['margin_bottom']) : $out['margin_bottom'];
        $out['margin_top_mobile']  = isset($input['margin_top_mobile']) ? intval($input['margin_top_mobile']) : $out['margin_top_mobile'];
        $out['margin_top_desktop'] = isset($input['margin_top_desktop']) ? intval($input['margin_top_desktop']) : $out['margin_top_desktop'];

        return $out;
    }

    // ---- Fields renderers ----
    public function field_selector() {
        $o = self::get_options(); ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[insertion_selector]"
               value="<?php echo esc_attr($o['insertion_selector']); ?>" placeholder=".content-wrapper">
        <p class="description"><?php esc_html_e('Ex: .content-wrapper, #main, .site-inner', 'cat-tail'); ?></p>
        <?php
    }

    public function field_position() {
        $o = self::get_options();
        $opts = ['afterend'=>'afterend','beforebegin'=>'beforebegin','afterbegin'=>'afterbegin','beforeend'=>'beforeend']; ?>
        <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[insertion_position]">
            <?php foreach($opts as $val=>$label): ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($o['insertion_position'],$val); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Méthode utilisée par insertAdjacentElement.', 'cat-tail'); ?></p>
        <?php
    }

    public function field_margin_bottom() {
        $o = self::get_options(); ?>
        <input type="number" min="0" step="1" style="width:120px"
               name="<?php echo esc_attr(self::OPTION_KEY); ?>[margin_bottom]"
               value="<?php echo esc_attr($o['margin_bottom']); ?>"> px
        <?php
    }

    public function field_margins_top() {
        $o = self::get_options(); ?>
        <label>
            <?php esc_html_e('Mobile', 'cat-tail'); ?>:
            <input type="number" min="0" step="1" style="width:100px"
                   name="<?php echo esc_attr(self::OPTION_KEY); ?>[margin_top_mobile]"
                   value="<?php echo esc_attr($o['margin_top_mobile']); ?>"> px
        </label>
        &nbsp;&nbsp;
        <label>
            <?php esc_html_e('Desktop (≥992px)', 'cat-tail'); ?>:
            <input type="number" min="0" step="1" style="width:100px"
                   name="<?php echo esc_attr(self::OPTION_KEY); ?>[margin_top_desktop]"
                   value="<?php echo esc_attr($o['margin_top_desktop']); ?>"> px
        </label>
        <?php
    }

    public function enqueue_assets($hook){
        // ID d'écran de la page top-level : "toplevel_page_woo-cat-tail"
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'toplevel_page_woo-cat-tail') return;
    
        // On réutilise le CSS admin Bento
        wp_enqueue_style('cat-tail-admin', CAT_TAIL_URL . 'assets/admin.css', [], CAT_TAIL_VERSION);
    
        // Un léger style spécifique Settings (facultatif)
        $extra = '
          .ims-settings .ims-card + .ims-card{ margin-top:16px; }
          .ims-form-row{ display:flex; align-items:center; gap:12px; margin:10px 0; }
          .ims-form-row .regular-text{ width:420px; max-width:100%; }
          .ims-field-help{ margin-top:4px; color:#6b7280; }
          .ims-sublabel{ color:#6b7280; margin-right:8px; }
          .ims-number{ width:120px; }
          @media (max-width:782px){ .ims-form-row{ flex-direction:column; align-items:flex-start; } .ims-number{ width:160px; } }
        ';
        wp_add_inline_style('cat-tail-admin', $extra);
    }

    public function render_page() {
        if (!current_user_can('manage_options')) return;
    
        $o = self::get_options();
        $pos = ['afterend'=>'afterend','beforebegin'=>'beforebegin','afterbegin'=>'afterbegin','beforeend'=>'beforeend'];
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
                  Paramètres d’insertion
                </div>
              </div>
              <div class="ims-card__body">
    
                <div class="ims-form-row">
                  <label for="cat_tail_selector" class="ims-sublabel">Sélecteur d’insertion</label>
                  <input id="cat_tail_selector" type="text" class="regular-text"
                         name="<?php echo esc_attr(self::OPTION_KEY); ?>[insertion_selector]"
                         value="<?php echo esc_attr($o['insertion_selector']); ?>"
                         placeholder=".content-wrapper">
                </div>
                <p class="ims-field-help">Ex : <code>.content-wrapper</code>, <code>#main</code>, <code>.site-inner</code></p>
    
                <div class="ims-form-row">
                  <label for="cat_tail_position" class="ims-sublabel">Position</label>
                  <select id="cat_tail_position" name="<?php echo esc_attr(self::OPTION_KEY); ?>[insertion_position]">
                    <?php foreach($pos as $val => $label): ?>
                      <option value="<?php echo esc_attr($val); ?>" <?php selected($o['insertion_position'], $val); ?>>
                        <?php echo esc_html($label); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <p class="ims-field-help">Méthode utilisée par <code>insertAdjacentElement</code>.</p>
    
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
    
                <div class="ims-form-row">
                  <label for="cat_tail_mb" class="ims-sublabel">Margin bottom (px)</label>
                  <input id="cat_tail_mb" type="number" min="0" step="1" class="ims-number"
                         name="<?php echo esc_attr(self::OPTION_KEY); ?>[margin_bottom]"
                         value="<?php echo esc_attr($o['margin_bottom']); ?>"> px
                </div>
    
                <div class="ims-form-row">
                  <span class="ims-sublabel">Margins top (px)</span>
                  <label>
                    Mobile :
                    <input type="number" min="0" step="1" class="ims-number"
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[margin_top_mobile]"
                           value="<?php echo esc_attr($o['margin_top_mobile']); ?>"> px
                  </label>
                  <label>
                    Desktop (≥992px) :
                    <input type="number" min="0" step="1" class="ims-number"
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[margin_top_desktop]"
                           value="<?php echo esc_attr($o['margin_top_desktop']); ?>"> px
                  </label>
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
