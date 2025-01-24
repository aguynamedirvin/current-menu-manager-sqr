<?php
namespace sqr;

/**
 * Menu management class
 */
class Menu {
    /**
     * Get all menus
     */
    public function get_menus() {
        $menus = get_terms([
            'taxonomy' => 'menu_category',
            'hide_empty' => false,
            'parent' => 0 // Only get top-level categories
        ]);

        if (is_wp_error($menus)) {
            return [];
        }

        return array_map([$this, 'format_menu'], $menus);
    }

    /**
     * Get a specific menu
     */
    public function get_menu($menu_id) {
        $menu = get_term($menu_id, 'menu_category');
        if (is_wp_error($menu)) {
            return null;
        }

        return $this->format_menu($menu);
    }

    /**
     * Format menu data
     */
    private function format_menu($menu) {
        // Get child categories (sections)
        $sections = get_terms([
            'taxonomy' => 'menu_category',
            'hide_empty' => false,
            'parent' => $menu->term_id
        ]);

        if (is_wp_error($sections)) {
            $sections = [];
        }

        // Get count of items directly in this menu (not in subcategories)
        $menu_items = get_posts([
            'post_type' => 'menu_item',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'menu_category',
                    'field' => 'term_id',
                    'terms' => $menu->term_id,
                    'include_children' => false
                ]
            ]
        ]);
        $direct_count = count($menu_items);

        // Format sections with their counts
        $formatted_sections = array_map(function($section) {
            $section_items = get_posts([
                'post_type' => 'menu_item',
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'menu_category',
                        'field' => 'term_id',
                        'terms' => $section->term_id,
                        'include_children' => false
                    ]
                ]
            ]);

            return [
                'id' => $section->term_id,
                'name' => $section->name,
                'slug' => $section->slug,
                'description' => $section->description,
                'count' => count($section_items)
            ];
        }, $sections);

        return [
            'id' => $menu->term_id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'description' => $menu->description,
            'count' => $direct_count,
            'categories' => $formatted_sections
        ];
    }

}
