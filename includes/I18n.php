<?php
namespace CatTail;

if (!defined('ABSPATH')) exit;

final class I18n {
    /** @var array<string,array<string,string>> */
    private const STRINGS = [
        'plugin_name' => [
            'en' => 'Woo Cat Tail',
            'fr' => 'Woo Cat Tail',
        ],
        'settings_link' => [
            'en' => 'Settings',
            'fr' => 'Reglages',
        ],
        'save' => [
            'en' => 'Save',
            'fr' => 'Enregistrer',
        ],

        // Shared UI.
        'slot_top' => [
            'en' => 'Top block',
            'fr' => 'Bloc haut',
        ],
        'slot_bottom' => [
            'en' => 'Bottom block',
            'fr' => 'Bloc bas',
        ],
        'status_linked' => [
            'en' => 'Linked',
            'fr' => 'Lie',
        ],
        'status_not_linked' => [
            'en' => 'Not linked',
            'fr' => 'Non lie',
        ],

        // Admin category.
        'template_linked' => [
            'en' => 'Linked template',
            'fr' => 'Modele lie',
        ],
        'no_template_linked' => [
            'en' => 'No template linked for this slot.',
            'fr' => 'Aucun modele associe pour ce slot.',
        ],
        'render_position' => [
            'en' => 'Render position',
            'fr' => 'Position de rendu',
        ],
        'render_position_top' => [
            'en' => 'Above the products loop',
            'fr' => 'Au-dessus de la boucle produits',
        ],
        'render_position_bottom' => [
            'en' => 'Below the products loop',
            'fr' => 'Sous la boucle produits',
        ],
        'edit_with_elementor' => [
            'en' => 'Edit with Elementor',
            'fr' => 'Modifier avec Elementor',
        ],
        'replace_or_assign' => [
            'en' => 'Replace / Link another',
            'fr' => 'Remplacer / Associer un autre',
        ],
        'detach' => [
            'en' => 'Detach',
            'fr' => 'Dissocier',
        ],
        'detach_confirm' => [
            'en' => 'Detach this template?',
            'fr' => 'Dissocier ce modele ?',
        ],
        'create_edit_with_elementor' => [
            'en' => 'Create & edit with Elementor',
            'fr' => 'Creer & modifier avec Elementor',
        ],
        'link_existing' => [
            'en' => 'Link existing template',
            'fr' => 'Associer un existant',
        ],
        'insertion_reminder' => [
            'en' => 'Insertion reminder',
            'fr' => 'Rappel insertion',
        ],
        'view_category_page' => [
            'en' => 'View category page',
            'fr' => 'Voir la page categorie',
        ],
        'search_section_label' => [
            'en' => 'Section search',
            'fr' => 'Recherche de section',
        ],

        // Admin modal / JS.
        'modal_title' => [
            'en' => 'Link an Elementor section',
            'fr' => 'Associer une section Elementor',
        ],
        'modal_search_placeholder' => [
            'en' => 'Search a section...',
            'fr' => 'Rechercher une section...',
        ],
        'loading' => [
            'en' => 'Loading...',
            'fr' => 'Chargement...',
        ],
        'no_results' => [
            'en' => 'No results',
            'fr' => 'Aucun resultat',
        ],
        'confirm_link_section' => [
            'en' => 'Link this section',
            'fr' => 'Relier cette section',
        ],
        'cancel' => [
            'en' => 'Cancel',
            'fr' => 'Annuler',
        ],
        'close' => [
            'en' => 'Close',
            'fr' => 'Fermer',
        ],
        'load_more' => [
            'en' => 'More...',
            'fr' => 'Plus...',
        ],
        'assign_error' => [
            'en' => 'An error occurred while linking.',
            'fr' => 'Erreur lors de la liaison.',
        ],
        'unauthorized' => [
            'en' => 'Unauthorized',
            'fr' => 'Non autorise',
        ],
        'bad_request' => [
            'en' => 'Bad request',
            'fr' => 'Requete invalide',
        ],
        'not_section_template' => [
            'en' => 'Not a section template',
            'fr' => "Ce n'est pas un template de section",
        ],

        // Template prefixes.
        'prefix_top' => [
            'en' => 'Top category: ',
            'fr' => 'Haut de categorie : ',
        ],
        'prefix_bottom' => [
            'en' => 'Bottom category: ',
            'fr' => 'Bas de categorie : ',
        ],

        // Settings page.
        'settings_insertion_title' => [
            'en' => 'Insertion settings',
            'fr' => "Parametres d'insertion",
        ],
        'selector_label' => [
            'en' => 'Insertion selector',
            'fr' => "Selecteur d'insertion",
        ],
        'selector_help' => [
            'en' => 'Ex: .content-wrapper, #main, .site-inner',
            'fr' => 'Ex : .content-wrapper, #main, .site-inner',
        ],
        'position' => [
            'en' => 'Position',
            'fr' => 'Position',
        ],
        'position_help' => [
            'en' => 'Method used by insertAdjacentElement.',
            'fr' => 'Methode utilisee par insertAdjacentElement.',
        ],
        'spacings' => [
            'en' => 'Spacings',
            'fr' => 'Espacements',
        ],
        'top_margin_bottom_mobile' => [
            'en' => 'Bottom margin mobile (px)',
            'fr' => 'Margin bottom mobile (px)',
        ],
        'top_margin_bottom_desktop' => [
            'en' => 'Bottom margin desktop (px)',
            'fr' => 'Margin bottom desktop (px)',
        ],
        'bottom_margin_bottom' => [
            'en' => 'Bottom margin (px)',
            'fr' => 'Margin bottom (px)',
        ],
        'bottom_margin_top_mobile' => [
            'en' => 'Top margin mobile (px)',
            'fr' => 'Margin top mobile (px)',
        ],
        'bottom_margin_top_desktop' => [
            'en' => 'Top margin desktop (px)',
            'fr' => 'Margin top desktop (px)',
        ],
    ];

    public static function t(string $key): string {
        if (!isset(self::STRINGS[$key])) {
            return $key;
        }

        $lang = self::lang();
        return self::STRINGS[$key][$lang] ?? self::STRINGS[$key]['en'];
    }

    /**
     * Return all localized variants for a key (used for backward compatibility).
     *
     * @return string[]
     */
    public static function variants(string $key): array {
        if (!isset(self::STRINGS[$key])) {
            return [];
        }

        $out = [];
        foreach (self::STRINGS[$key] as $value) {
            if (!is_string($value) || $value === '') continue;
            $out[] = $value;
        }
        return array_values(array_unique($out));
    }

    private static function lang(): string {
        static $lang = null;
        if ($lang !== null) {
            return $lang;
        }

        $locale = '';
        if (function_exists('determine_locale')) {
            $locale = (string) determine_locale();
        }
        if ($locale === '' && function_exists('get_locale')) {
            $locale = (string) get_locale();
        }

        $prefix = strtolower(substr($locale, 0, 2));
        $lang = ($prefix === 'fr') ? 'fr' : 'en';
        return $lang;
    }
}
