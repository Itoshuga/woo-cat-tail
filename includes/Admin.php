<?php
namespace CatTail;

if (!defined('ABSPATH')) exit;

/**
 * Admin UI for linking Elementor section templates to WooCommerce category pages.
 * Supports two independent slots per category:
 * - top
 * - bottom
 */
class Admin {
    public function __construct() {
        add_action('product_cat_edit_form_fields', [$this, 'render_bento_ui'], 50);
        add_action('admin_head', [$this, 'maybe_enqueue_admin_css']);
        add_action('admin_init', [$this, 'handle_actions']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_cat_tail_list_sections', [$this, 'ajax_list_sections']);
        add_action('wp_ajax_cat_tail_assign_section', [$this, 'ajax_assign_section']);
    }

    private function sanitize_slot(string $slot): string {
        return $slot === 'top' ? 'top' : 'bottom';
    }

    private function get_slot_label(string $slot): string {
        return $slot === 'top' ? I18n::t('slot_top') : I18n::t('slot_bottom');
    }

    private function get_slot_meta_key(string $slot): string {
        return $slot === 'top' ? CAT_TAIL_META_KEY_TOP : CAT_TAIL_META_KEY_BOTTOM;
    }

    private function get_template_prefix(string $slot): string {
        return $slot === 'top' ? I18n::t('prefix_top') : I18n::t('prefix_bottom');
    }

    private function screen_is_product_cat(): bool {
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && isset($screen->taxonomy) && $screen->taxonomy === 'product_cat') {
                return true;
            }
            if ($screen && $screen->id === 'edit-product_cat') {
                return true;
            }
        }
        return (($_GET['taxonomy'] ?? '') === 'product_cat');
    }

    public function maybe_enqueue_admin_css() {
        if (!$this->screen_is_product_cat()) return;

        echo '<link rel="stylesheet" href="' . esc_url(CAT_TAIL_URL . 'assets/admin.css') . '?ver=' . esc_attr(CAT_TAIL_VERSION) . '" />';
    }

    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, ['edit-tags.php', 'term.php'], true)) return;

        $tax = $_GET['taxonomy'] ?? '';
        if ($tax !== 'product_cat') return;

        wp_enqueue_script(
            'cat-tail-admin',
            CAT_TAIL_URL . 'assets/admin.js',
            ['jquery'],
            CAT_TAIL_VERSION,
            true
        );

        wp_localize_script('cat-tail-admin', 'CatTailAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cat_tail_admin_nonce'),
            'strings'  => [
                'modal_title'  => I18n::t('modal_title'),
                'search_ph'    => I18n::t('modal_search_placeholder'),
                'loading'      => I18n::t('loading'),
                'no_results'   => I18n::t('no_results'),
                'confirm'      => I18n::t('confirm_link_section'),
                'cancel'       => I18n::t('cancel'),
                'slot_top'     => I18n::t('slot_top'),
                'slot_bottom'  => I18n::t('slot_bottom'),
                'load_more'    => I18n::t('load_more'),
                'assign_error' => I18n::t('assign_error'),
            ]
        ]);
    }

    private function get_template_name(int $template_id, string $slot): string {
        $post = get_post($template_id);
        if (!$post || $post->post_type !== 'elementor_library') {
            return '';
        }

        $name = (string) $post->post_title;
        $prefix_key = $slot === 'top' ? 'prefix_top' : 'prefix_bottom';
        $prefixes = I18n::variants($prefix_key);
        if ($slot === 'top') {
            $prefixes[] = 'Haut de categorie : ';
            $prefixes[] = 'Haut de catégorie : ';
        } else {
            $prefixes[] = 'Bas de categorie : ';
            $prefixes[] = 'Bas de catégorie : ';
        }

        foreach (array_values(array_unique($prefixes)) as $prefix) {
            if (stripos($name, $prefix) === 0) {
                $name = substr($name, strlen($prefix));
                break;
            }
        }
        return $name;
    }

    private function build_edit_url(int $term_id, string $slot): string {
        return wp_nonce_url(
            add_query_arg(
                [
                    'ims_edit_el_template' => $term_id,
                    'ims_slot' => $slot,
                ],
                admin_url('edit-tags.php?taxonomy=product_cat&post_type=product')
            ),
            'ims_edit_el_template_' . $term_id . '_' . $slot
        );
    }

    private function build_detach_url(int $term_id, string $slot): string {
        return wp_nonce_url(
            add_query_arg(
                [
                    'ims_detach_el_template' => $term_id,
                    'ims_slot' => $slot,
                ],
                admin_url('edit-tags.php?taxonomy=product_cat&post_type=product')
            ),
            'ims_detach_el_template_' . $term_id . '_' . $slot
        );
    }

    private function render_slot_card(int $term_id, string $slot): void {
        $slot_label = $this->get_slot_label($slot);
        $meta_key = $this->get_slot_meta_key($slot);
        $template_id = (int) get_term_meta($term_id, $meta_key, true);
        $template_name = $template_id ? $this->get_template_name($template_id, $slot) : '';

        $create_replace_url = $this->build_edit_url($term_id, $slot);
        $detach_url = $this->build_detach_url($term_id, $slot);
        ?>
        <div class="ims-card">
          <div class="ims-card__header">
            <div class="ims-card__title">
              <span class="ims-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="6" rx="2" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="10" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/></svg>
              </span>
              <?php echo esc_html($slot_label); ?>
            </div>
            <span class="ims-badge <?php echo $template_id ? 'ims-badge--ok' : 'ims-badge--empty'; ?>">
              <?php echo $template_id ? esc_html(I18n::t('status_linked')) : esc_html(I18n::t('status_not_linked')); ?>
            </span>
          </div>

          <div class="ims-card__body">
            <div class="ims-subgrid ims-subgrid--compact">
              <div class="ims-mini-card">
                <p class="ims-mini-card__label"><?php echo esc_html(I18n::t('template_linked')); ?></p>
                <?php if ($template_id): ?>
                  <code class="ims-pill">
                    <?php echo esc_html($template_name); ?> #<?php echo (int) $template_id; ?>
                  </code>
                <?php else: ?>
                  <p class="ims-mini-card__help"><?php echo esc_html(I18n::t('no_template_linked')); ?></p>
                <?php endif; ?>
              </div>

              <div class="ims-mini-card">
                <p class="ims-mini-card__label"><?php echo esc_html(I18n::t('render_position')); ?></p>
                <p class="ims-mini-value">
                  <?php echo $slot === 'top' ? esc_html(I18n::t('render_position_top')) : esc_html(I18n::t('render_position_bottom')); ?>
                </p>
              </div>
            </div>
          </div>

          <div class="ims-card__actions">
            <?php if ($template_id): ?>
              <a class="button button-primary ims-btn" href="<?php echo esc_url(admin_url('post.php?post=' . $template_id . '&action=elementor')); ?>">
                <?php echo esc_html(I18n::t('edit_with_elementor')); ?>
              </a>

              <button type="button"
                      class="button ims-btn button-secondary ims-open-replace-modal"
                      data-term="<?php echo esc_attr($term_id); ?>"
                      data-slot="<?php echo esc_attr($slot); ?>">
                <?php echo esc_html(I18n::t('replace_or_assign')); ?>
              </button>

              <a class="button ims-btn ims-btn--ghost" href="<?php echo esc_url($detach_url); ?>"
                 onclick="return confirm('<?php echo esc_attr(I18n::t('detach_confirm')); ?>');">
                <?php echo esc_html(I18n::t('detach')); ?>
              </a>
            <?php else: ?>
              <a class="button button-primary ims-btn" href="<?php echo esc_url($create_replace_url); ?>">
                <?php echo esc_html(I18n::t('create_edit_with_elementor')); ?>
              </a>

              <button type="button"
                      class="button ims-btn button-secondary ims-open-replace-modal"
                      data-term="<?php echo esc_attr($term_id); ?>"
                      data-slot="<?php echo esc_attr($slot); ?>">
                <?php echo esc_html(I18n::t('link_existing')); ?>
              </button>
            <?php endif; ?>
          </div>
        </div>
        <?php
    }

    public function render_bento_ui($term) {
        if (!current_user_can('edit_posts') || !class_exists('\Elementor\Plugin')) return;

        $term_id = (int) $term->term_id;
        $view_url = get_term_link($term_id, 'product_cat');

        $opts = \CatTail\Settings::get_options();
        ?>
        <tr class="form-field">
          <th scope="row"><label><?php echo esc_html(I18n::t('plugin_name')); ?></label></th>
          <td>
            <div class="ims-bento">
              <?php $this->render_slot_card($term_id, 'top'); ?>
              <?php $this->render_slot_card($term_id, 'bottom'); ?>

              <div class="ims-card ims-card--muted">
                <div class="ims-card__header">
                  <div class="ims-card__title">
                    <span class="ims-icon" aria-hidden="true">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <?php echo esc_html(I18n::t('insertion_reminder')); ?>
                  </div>
                </div>
                <div class="ims-card__body">
                  <div class="ims-subgrid ims-subgrid--compact">
                    <div class="ims-mini-card">
                      <p class="ims-mini-card__label"><?php echo esc_html(I18n::t('slot_top')); ?></p>
                      <code class="ims-pill"><?php echo esc_html($opts['top_insertion_selector']); ?></code>
                      <p class="ims-mini-card__help"><?php echo esc_html($opts['top_insertion_position']); ?></p>
                    </div>
                    <div class="ims-mini-card">
                      <p class="ims-mini-card__label"><?php echo esc_html(I18n::t('slot_bottom')); ?></p>
                      <code class="ims-pill"><?php echo esc_html($opts['insertion_selector']); ?></code>
                      <p class="ims-mini-card__help"><?php echo esc_html($opts['insertion_position']); ?></p>
                    </div>
                  </div>
                </div>
                <?php if (!is_wp_error($view_url)): ?>
                  <div class="ims-card__actions">
                    <a class="button ims-btn ims-btn--view" target="_blank" rel="noopener" href="<?php echo esc_url($view_url); ?>">
                      <?php echo esc_html(I18n::t('view_category_page')); ?>
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <?php
            static $modal_printed = false;
            if (!$modal_printed) {
                $modal_printed = true;
                ?>
                <div id="cat-tail-modal-overlay" style="display:none;">
                  <div id="cat-tail-modal" role="dialog" aria-modal="true" aria-labelledby="cat-tail-modal-title">
                    <div class="cat-tail-modal__header">
                      <h2 id="cat-tail-modal-title"><?php echo esc_html(I18n::t('modal_title')); ?></h2>
                      <button type="button" class="cat-tail-modal__close" aria-label="<?php echo esc_attr(I18n::t('close')); ?>">&times;</button>
                    </div>

                    <div class="cat-tail-modal__body">
                      <div class="ims-mini-card ims-mini-card--search">
                        <label class="ims-mini-card__label" for="cat-tail-search"><?php echo esc_html(I18n::t('search_section_label')); ?></label>
                        <input type="text" id="cat-tail-search" placeholder="<?php echo esc_attr(I18n::t('modal_search_placeholder')); ?>" />
                      </div>
                      <div id="cat-tail-results" class="cat-tail-results"></div>
                    </div>

                    <div class="cat-tail-modal__footer">
                      <button type="button" class="button button-secondary cat-tail-cancel"><?php echo esc_html(I18n::t('cancel')); ?></button>
                      <button type="button" class="button button-primary cat-tail-confirm" disabled><?php echo esc_html(I18n::t('confirm_link_section')); ?></button>
                    </div>
                  </div>
                </div>
                <?php
            }
            ?>
          </td>
        </tr>
        <?php
    }

    public function handle_actions() {
        if (!is_admin()) return;

        if (isset($_GET['ims_edit_el_template'])) {
            $term_id = (int) $_GET['ims_edit_el_template'];
            $slot = $this->sanitize_slot((string) ($_GET['ims_slot'] ?? 'bottom'));
            $nonce_action = 'ims_edit_el_template_' . $term_id . '_' . $slot;

            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', $nonce_action)) return;
            if (!current_user_can('edit_posts') || !class_exists('\Elementor\Plugin')) return;

            $meta_key = $this->get_slot_meta_key($slot);
            $template_id = (int) get_term_meta($term_id, $meta_key, true);

            if (!$template_id) {
                $term = get_term($term_id, 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $new_id = wp_insert_post([
                        'post_title'  => $this->get_template_prefix($slot) . wp_strip_all_tags($term->name),
                        'post_type'   => 'elementor_library',
                        'post_status' => 'publish',
                        'meta_input'  => [
                            '_elementor_edit_mode'     => 'builder',
                            '_elementor_template_type' => 'section',
                        ],
                    ]);
                    if ($new_id && !is_wp_error($new_id)) {
                        $template_id = (int) $new_id;
                        update_term_meta($term_id, $meta_key, $template_id);
                    }
                }
            }

            if ($template_id) {
                wp_safe_redirect(admin_url('post.php?post=' . $template_id . '&action=elementor'));
                exit;
            }
        }

        if (isset($_GET['ims_detach_el_template'])) {
            $term_id = (int) $_GET['ims_detach_el_template'];
            $slot = $this->sanitize_slot((string) ($_GET['ims_slot'] ?? 'bottom'));
            $nonce_action = 'ims_detach_el_template_' . $term_id . '_' . $slot;

            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', $nonce_action)) return;
            if (!current_user_can('edit_posts')) return;

            delete_term_meta($term_id, $this->get_slot_meta_key($slot));
            wp_safe_redirect(admin_url('edit-tags.php?taxonomy=product_cat&post_type=product'));
            exit;
        }
    }

    /**
     * AJAX: list Elementor section templates.
     * POST: search (string), page (int)
     */
    public function ajax_list_sections() {
        check_ajax_referer('cat_tail_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => I18n::t('unauthorized')], 403);
        }

        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $paged  = max(1, (int)($_POST['page'] ?? 1));

        $q = new \WP_Query([
            'post_type'      => 'elementor_library',
            'posts_per_page' => 20,
            'paged'          => $paged,
            's'              => $search,
            'meta_query'     => [
                [
                    'key'   => '_elementor_template_type',
                    'value' => 'section',
                ]
            ],
            'post_status'    => ['publish', 'private'],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $items = [];
        foreach ($q->posts as $p) {
            $items[] = [
                'id'    => (int) $p->ID,
                'title' => html_entity_decode(get_the_title($p), ENT_QUOTES, get_bloginfo('charset')),
            ];
        }

        wp_send_json_success([
            'items'      => $items,
            'found'      => (int) $q->found_posts,
            'max_pages'  => (int) $q->max_num_pages,
            'page'       => $paged,
        ]);
    }

    /**
     * AJAX: assign a selected section to the category slot.
     * POST: term_id (int), template_id (int), slot (top|bottom)
     */
    public function ajax_assign_section() {
        check_ajax_referer('cat_tail_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => I18n::t('unauthorized')], 403);
        }

        $term_id     = (int) ($_POST['term_id'] ?? 0);
        $template_id = (int) ($_POST['template_id'] ?? 0);
        $slot        = $this->sanitize_slot((string) ($_POST['slot'] ?? 'bottom'));

        if ($term_id <= 0 || $template_id <= 0) {
            wp_send_json_error(['message' => I18n::t('bad_request')], 400);
        }

        $is_section = get_post_meta($template_id, '_elementor_template_type', true) === 'section';
        if (!$is_section) {
            wp_send_json_error(['message' => I18n::t('not_section_template')], 400);
        }

        update_term_meta($term_id, $this->get_slot_meta_key($slot), $template_id);

        wp_send_json_success([
            'term_id'     => $term_id,
            'template_id' => $template_id,
            'slot'        => $slot,
        ]);
    }
}
