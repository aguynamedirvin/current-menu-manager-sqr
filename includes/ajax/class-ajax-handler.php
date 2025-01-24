<?php
namespace sqr;

/**
 * AJAX Handler for Menu Manager
 */
class Ajax_Handler {
    /**
     * @var Menu
     */
    private $menu;

    /**
     * @var array
     */
    private $pagination;

    /**
     * Initialize the AJAX handler
     */
    public function __construct(Menu $menu) {
        $this->menu = $menu;
    }

    /**
     * Get menu items based on current filters
     */
    public function get_menu_items() {
        $items_per_page = 15;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

        $args = [
            'post_type' => 'menu_item',
            'posts_per_page' => $items_per_page,
            'paged' => $current_page,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        // Get filters from URL
        $location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
        $menu = isset($_GET['menu']) ? sanitize_text_field($_GET['menu']) : '';
        $section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

        // Build tax query if we have filters
        $tax_query = [];

        if (!empty($location)) {
            $tax_query[] = [
                'taxonomy' => 'location',
                'field' => 'slug',
                'terms' => $location
            ];
        }

        if (!empty($section)) {
            // If section is specified, use that instead of menu
            $tax_query[] = [
                'taxonomy' => 'menu_category',
                'field' => 'slug',
                'terms' => $section
            ];
        } elseif (!empty($menu)) {
            // Otherwise use menu if specified
            $tax_query[] = [
                'taxonomy' => 'menu_category',
                'field' => 'slug',
                'terms' => $menu
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        // Use WP_Query instead of get_posts to get pagination info
        $query = new \WP_Query($args);
        
        // Store pagination data
        $this->pagination = [
            'total_pages' => $query->max_num_pages,
            'current_page' => $current_page,
            'total_items' => $query->found_posts,
            'start' => (($current_page - 1) * $items_per_page) + 1,
            'end' => min($current_page * $items_per_page, $query->found_posts)
        ];

        return $query->posts;
    }

    /**
     * Get pagination data
     */
    public function get_pagination_data() {
        return $this->pagination ?? null;
    }

    /**
     * Get all menu locations
     */
    public function get_locations() {
        $terms = get_terms([
            'taxonomy' => 'location',
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        return $terms;
    }

    /**
     * AJAX handler for getting menu items
     */
    public function ajax_get_menu_items() {
        check_ajax_referer('menu_manager_nonce', 'nonce');

        $menu_items = $this->get_menu_items();

        wp_send_json_success([
            'items' => array_map(function($item) {
                return [
                    'id' => $item->ID,
                    'title' => $item->post_title,
                    'content' => $item->post_content,
                    'price' => get_post_meta($item->ID, '_menu_item_price', true),
                    'is_market_price' => get_post_meta($item->ID, '_menu_item_is_market_price', true),
                ];
            }, $menu_items),
            'pagination' => $this->get_pagination_data()
        ]);
    }

    /**
     * AJAX handler for getting menu data
     */
    public function ajax_get_menu() {
        check_ajax_referer('menu_manager_nonce', 'nonce');

        $menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 0;
        $menu = $this->menu->get_menu($menu_id);

        if (!$menu) {
            wp_send_json_error(['message' => __('Menu not found', 'the-menu-manager')]);
        }

        wp_send_json_success(['menu' => $menu]);
    }
}
