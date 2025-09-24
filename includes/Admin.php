<?php
namespace CatTail;

if (!defined('ABSPATH')) exit;

/**
 * Admin UI for linking an Elementor "section" template to a WooCommerce product category.
 * Adds a "Remplacer / Associer un autre" button that opens a modal to pick among existing sections.
 * Requires constants:
 *  - CAT_TAIL_URL
 *  - CAT_TAIL_VERSION
 *  - CAT_TAIL_META_KEY
 */
class Admin {
    public function __construct() {
        add_action('product_cat_edit_form_fields', [$this, 'render_bento_ui'], 50);
        add_action('admin_head', [$this, 'maybe_enqueue_admin_css']);
        add_action('admin_init', [$this, 'handle_actions']); // create/replace + detach

        // Assets + AJAX for the modal "Remplacer"
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_cat_tail_list_sections', [$this, 'ajax_list_sections']);
        add_action('wp_ajax_cat_tail_assign_section', [$this, 'ajax_assign_section']);
    }

    /**
     * Detect we are on product_cat screens (list or single term edit)
     */
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

    /**
     * Enqueue admin CSS early (legacy approach).
     * Keeps your previous inline pills while allowing external CSS file for modal.
     */
    public function maybe_enqueue_admin_css() {
        if (!$this->screen_is_product_cat()) return;
        echo '<link rel="stylesheet" href="' . esc_url(CAT_TAIL_URL . 'assets/admin.css') . '?ver=' . esc_attr(CAT_TAIL_VERSION) . '" />';
        echo '<style>
          .ims-inline{margin:2px 0 6px;}
          .ims-inline__label{color:#6b7280;font-weight:500;margin-right:6px;}
          .ims-pill{background:#f3f4f6;padding:2px 6px;border-radius:6px;display:inline-block;line-height:1.2;}
        </style>';
    }

    /**
     * Enqueue JS (and ensure available also on single term edit screen: term.php)
     */
    public function enqueue_admin_assets($hook) {
        // Load on taxonomy list (edit-tags.php) and single term edit (term.php)
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
                'modal_title' => __('Remplacer la section liée', 'cat-tail'),
                'search_ph'   => __('Rechercher une section…', 'cat-tail'),
                'loading'     => __('Chargement…', 'cat-tail'),
                'no_results'  => __('Aucun résultat', 'cat-tail'),
                'confirm'     => __('Relier cette section', 'cat-tail'),
                'cancel'      => __('Annuler', 'cat-tail'),
            ]
        ]);
    }

    /**
     * Render the settings "bento" + injects the hidden modal markup once per page
     */
    public function render_bento_ui($term) {
        if (!current_user_can('edit_posts') || !class_exists('\Elementor\Plugin')) return;

        $term_id     = (int) $term->term_id;
        $template_id = (int) get_term_meta($term_id, CAT_TAIL_META_KEY, true);
        $template_name = '';
        
        if ($template_id) {
          $post = get_post($template_id);
          if ($post && $post->post_type === 'elementor_library') {
              $template_name = $post->post_title;
              // Supprimer le préfixe "Bas de catégorie :" si présent
              $template_name = preg_replace('/^Bas de catégorie\s*:\s*/i', '', $template_name);
          }
        }

        // Settings
        $opts     = \CatTail\Settings::get_options();
        $selector = isset($opts['insertion_selector'])   ? $opts['insertion_selector']   : '.content-wrapper';
        $position = isset($opts['insertion_position'])   ? $opts['insertion_position']   : 'afterend';
        $mb       = isset($opts['margin_bottom'])        ? (int)$opts['margin_bottom']   : 150;
        $mtm      = isset($opts['margin_top_mobile'])    ? (int)$opts['margin_top_mobile']  : 32;
        $mtd      = isset($opts['margin_top_desktop'])   ? (int)$opts['margin_top_desktop'] : 40;

        // Action links
        $create_replace_url = wp_nonce_url(
            add_query_arg(['ims_edit_el_template' => $term_id], admin_url('edit-tags.php?taxonomy=product_cat&post_type=product')),
            'ims_edit_el_template_' . $term_id
        );
        $detach_url = wp_nonce_url(
            add_query_arg(['ims_detach_el_template' => $term_id], admin_url('edit-tags.php?taxonomy=product_cat&post_type=product')),
            'ims_detach_el_template_' . $term_id
        );
        $view_url = get_term_link($term_id, 'product_cat'); ?>
        <tr class="form-field">
          <th scope="row"><label><?php esc_html_e('Bloc bas de catégorie', 'cat-tail'); ?></label></th>
          <td>
            <div class="ims-bento">
              <!-- Main card -->
              <div class="ims-card">
                <div class="ims-card__header">
                  <div class="ims-card__title">
                    <span class="ims-icon" aria-hidden="true">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="6" rx="2" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="10" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/></svg>
                    </span>
                    <?php esc_html_e('Contenu Elementor lié à cette catégorie', 'cat-tail'); ?>
                  </div>
                  <span class="ims-badge <?php echo $template_id ? 'ims-badge--ok' : 'ims-badge--empty'; ?>">
                    <?php echo $template_id ? esc_html__('Lié', 'cat-tail') : esc_html__('Non lié', 'cat-tail'); ?>
                  </span>
                </div>

                <div class="ims-card__body">
                  <?php if ($template_id): ?>
                    <p class="ims-inline">
                      <span class="ims-inline__label"><?php esc_html_e('Modèle', 'cat-tail'); ?> :</span>
                      <code class="ims-pill">
                          <?php echo esc_html($template_name); ?> #<?php echo (int) $template_id; ?>
                      </code>
                    </p>
                  <?php else: ?>
                    <p class="description"><?php esc_html_e('Aucun modèle n’est encore associé à cette catégorie.', 'cat-tail'); ?></p>
                  <?php endif; ?>

                  <p class="ims-helper">
                    <?php
                    echo wp_kses_post(
                      sprintf(
                        __('Le modèle est inséré <em>%1$s</em> le sélecteur <code>%2$s</code> (déplacement JS), avec <code>margin-top: %3$dpx / %4$dpx</code> et <code>margin-bottom: %5$dpx</code>.', 'cat-tail'),
                        esc_html($position),
                        esc_html($selector),
                        $mtm,
                        $mtd,
                        $mb
                      )
                    );
                    ?>
                  </p>
                </div>

                <div class="ims-card__actions">
                  <?php if ($template_id): ?>
                    <a class="button button-primary ims-btn" href="<?php echo esc_url( admin_url('post.php?post=' . $template_id . '&action=elementor') ); ?>">
                      <?php esc_html_e('Modifier avec Elementor', 'cat-tail'); ?>
                    </a>

                    <!-- Replace via modal -->
                    <button type="button"
                            class="button ims-btn button-secondary ims-open-replace-modal"
                            data-term="<?php echo esc_attr($term_id); ?>">
                      <?php esc_html_e('Remplacer / Associer un autre', 'cat-tail'); ?>
                    </button>

                    <a class="button ims-btn ims-btn--ghost" href="<?php echo esc_url($detach_url); ?>"
                       onclick="return confirm('<?php echo esc_attr__('Dissocier ce modèle ? Le contenu ne s’affichera plus sur la catégorie.', 'cat-tail'); ?>');">
                      <?php esc_html_e('Dissocier', 'cat-tail'); ?>
                    </a>
                  <?php else: ?>
                    <!-- If none: keep create & edit flow -->
                    <a class="button button-primary ims-btn" href="<?php echo esc_url($create_replace_url); ?>">
                      <?php esc_html_e('Créer & modifier avec Elementor', 'cat-tail'); ?>
                    </a>

                    <!-- Also allow linking an existing one via modal -->
                    <button type="button"
                            class="button ims-btn button-secondary ims-open-replace-modal"
                            data-term="<?php echo esc_attr($term_id); ?>">
                      <?php esc_html_e('Associer un existant', 'cat-tail'); ?>
                    </button>
                  <?php endif; ?>

                  <?php if (!is_wp_error($view_url)): ?>
                    <a class="button ims-btn ims-btn--view" target="_blank" rel="noopener" href="<?php echo esc_url($view_url); ?>">
                      <?php esc_html_e('Voir la page catégorie', 'cat-tail'); ?>
                    </a>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Reminder card -->
              <div class="ims-card ims-card--muted">
                <div class="ims-card__header">
                  <div class="ims-card__title">
                    <span class="ims-icon" aria-hidden="true">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <?php esc_html_e('Rappel d’intégration', 'cat-tail'); ?>
                  </div>
                </div>
                <div class="ims-card__body">
                  <ul class="ims-list">
                    <li><?php echo wp_kses_post(__('Modèle Elementor de type <strong>Section</strong> (recommandé).', 'cat-tail')); ?></li>
                    <li><?php echo wp_kses_post( sprintf('<code>%s</code> (%s <code>insertAdjacentElement</code>)', esc_html($selector), esc_html($position)) ); ?></li>
                    <li><?php echo wp_kses_post( sprintf('<code>.ims-bottom-el-wrap { margin-top:%dpx / %dpx; margin-bottom:%dpx; }</code>', $mtm, $mtd, $mb) ); ?></li>
                  </ul>
                </div>
              </div>
            </div>

            <?php
            // Hidden modal markup — inject once per page
            static $modal_printed = false;
            if (!$modal_printed) {
                $modal_printed = true;
                ?>
                <div id="cat-tail-modal-overlay" style="display:none;">
                  <div id="cat-tail-modal" role="dialog" aria-modal="true" aria-labelledby="cat-tail-modal-title">
                    <div class="cat-tail-modal__header">
                      <h2 id="cat-tail-modal-title"><?php echo esc_html__('Remplacer la section liée', 'cat-tail'); ?></h2>
                      <button type="button" class="cat-tail-modal__close" aria-label="<?php echo esc_attr__('Fermer', 'cat-tail'); ?>">&times;</button>
                    </div>

                    <div class="cat-tail-modal__body">
                      <input type="text" id="cat-tail-search" placeholder="<?php echo esc_attr__('Rechercher une section…', 'cat-tail'); ?>" />
                      <div id="cat-tail-results" class="cat-tail-results"></div>
                    </div>

                    <div class="cat-tail-modal__footer">
                      <button type="button" class="button button-secondary cat-tail-cancel"><?php echo esc_html__('Annuler', 'cat-tail'); ?></button>
                      <button type="button" class="button button-primary cat-tail-confirm" disabled><?php echo esc_html__('Relier cette section', 'cat-tail'); ?></button>
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

    /**
     * Existing flows: create/attach/open in Elementor, detach
     */
    public function handle_actions() {
        if (!is_admin()) return;

        // Create/attach/open in Elementor
        if (isset($_GET['ims_edit_el_template'])) {
            $term_id = (int) $_GET['ims_edit_el_template'];
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ims_edit_el_template_' . $term_id)) return;
            if (!current_user_can('edit_posts') || !class_exists('\Elementor\Plugin')) return;

            $template_id = (int) get_term_meta($term_id, CAT_TAIL_META_KEY, true);
            if (!$template_id) {
                $term = get_term($term_id, 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $new_id = wp_insert_post([
                        'post_title'  => 'Bas de catégorie : ' . wp_strip_all_tags($term->name),
                        'post_type'   => 'elementor_library',
                        'post_status' => 'publish',
                        'meta_input'  => [
                            '_elementor_edit_mode'     => 'builder',
                            '_elementor_template_type' => 'section',
                        ],
                    ]);
                    if ($new_id && !is_wp_error($new_id)) {
                        $template_id = (int) $new_id;
                        update_term_meta($term_id, CAT_TAIL_META_KEY, $template_id);
                    }
                }
            }
            if ($template_id) {
                wp_safe_redirect(admin_url('post.php?post=' . (int)$template_id . '&action=elementor'));
                exit;
            }
        }

        // Detach
        if (isset($_GET['ims_detach_el_template'])) {
            $term_id = (int) $_GET['ims_detach_el_template'];
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ims_detach_el_template_' . $term_id)) return;
            if (!current_user_can('edit_posts')) return;

            delete_term_meta($term_id, CAT_TAIL_META_KEY);
            wp_safe_redirect(admin_url('edit-tags.php?taxonomy=product_cat&post_type=product'));
            exit;
        }
    }

    /**
     * AJAX: list Elementor Library items of type "section"
     * POST: search (string), page (int)
     * Return: { success:true, data:{ items:[{id,title}], found, max_pages, page } }
     */
    public function ajax_list_sections() {
        check_ajax_referer('cat_tail_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
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
                'id'    => (int)$p->ID,
                'title' => html_entity_decode(get_the_title($p), ENT_QUOTES, get_bloginfo('charset')),
            ];
        }

        wp_send_json_success([
            'items'      => $items,
            'found'      => (int)$q->found_posts,
            'max_pages'  => (int)$q->max_num_pages,
            'page'       => $paged,
        ]);
    }

    /**
     * AJAX: assign a selected section to the category (term meta)
     * POST: term_id (int), template_id (int)
     */
    public function ajax_assign_section() {
        check_ajax_referer('cat_tail_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $term_id     = (int)($_POST['term_id'] ?? 0);
        $template_id = (int)($_POST['template_id'] ?? 0);

        if ($term_id <= 0 || $template_id <= 0) {
            wp_send_json_error(['message' => 'Bad request'], 400);
        }

        // Ensure the template is an Elementor "section"
        $is_section = get_post_meta($template_id, '_elementor_template_type', true) === 'section';
        if (!$is_section) {
            wp_send_json_error(['message' => 'Not a section template'], 400);
        }

        update_term_meta($term_id, CAT_TAIL_META_KEY, $template_id);

        wp_send_json_success([
            'term_id'     => $term_id,
            'template_id' => $template_id,
        ]);
    }
}
