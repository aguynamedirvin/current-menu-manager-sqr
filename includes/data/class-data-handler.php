<?php
namespace sqr;

/**
 * Data Handler for Menu Manager
 */
class Data_Handler {
    /**
     * @var Menu
     */
    private $menu;

    /**
     * @var Menu
     */
    private $menuClass;

    /**
     * @var array
     */
    private $pagination;

    /**
     * Initialize the Data handler
     */
    public function __construct(Menu $menu) {
        $this->menuClass = new Menu();
        $this->menu = $menu;
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
     * AJAX handler for getting menu data
     */
    public function ajax_get_menu() {
        check_ajax_referer('menu_manager_nonce', 'nonce');

        $menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 0;
        $menu = $this->menu->get_menu($menu_id);

        if (!$menu) {
            wp_send_json_error(['message' => __('Menu not found', 'the-menu-manager')]);
        }

        wp_send_json_success(['menu' => $menuClass]);
    }

    /**
     * Fetch Menus form WordPress Database
     */
    public function fetch_menus() {
        $menus = get_terms([
            'taxonomy' => 'menu_category',
            'hide_empty' => false,
            'parent' => 0 // Only get top-level categories
        ]);

        if (is_wp_error($menus)) {
            return [];
        }

        // Return our formattd menus
        return array_map([$this->menuClass, 'format_menu'], $menus);
    }


    /**
     * Fetch Menu Items form WordPress Database
     */
    public function fetch_menu_items(array $filters = [], int $items_per_page = 15, int $current_page = 1) {

        // Base query args
        $args = [
            'post_type' => 'menu_item',
            'posts_per_page' => $items_per_page,
            'paged' => $current_page,
            'orderby' => 'title',
            'order' => 'ASC',
        ];
    
        // Apply filters from parameters
        $tax_query = [];
    
        if (!empty($filters['location'])) {
            $tax_query[] = [
                'taxonomy' => 'location',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['location']),
            ];
        }
    
        if (!empty($filters['section'])) {
            // Section filter takes precedence
            $tax_query[] = [
                'taxonomy' => 'menu_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['section']),
            ];
        } elseif (!empty($filters['menu'])) {
            // Menu filter applies if section is not set
            $tax_query[] = [
                'taxonomy' => 'menu_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['menu']),
            ];
        }
    
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }
    
        // Use WP_Query for advanced querying and pagination
        $query = new \WP_Query($args);
    
        // Store pagination data
        $this->pagination = [
            'total_pages' => $query->max_num_pages,
            'current_page' => $current_page,
            'total_items' => $query->found_posts,
            'start' => (($current_page - 1) * $items_per_page) + 1,
            'end' => min($current_page * $items_per_page, $query->found_posts),
        ];
    
        return $query->posts;
    }

    /**
     * AJAX handler for fetching menu items
     */
    public function ajax_fetch_menu_items() {
        check_ajax_referer('menu_manager_nonce', 'nonce');
    
        // Extract filters from request
        $filters = [
            'location' => isset($_GET['location']) ? $_GET['location'] : '',
            'menu' => isset($_GET['menu']) ? $_GET['menu'] : '',
            'section' => isset($_GET['section']) ? $_GET['section'] : '',
        ];
        $items_per_page = isset($_GET['items_per_page']) ? intval($_GET['items_per_page']) : '';
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : '';
    
        // Fetch menu items
        $menu_items = $this->fetch_menu_items($filters, $items_per_page, $current_page);
    
        // Format the response
        $formatted_items = array_map(function ($item) {
            return [
                'id' => $item->ID,
                'title' => $item->post_title,
                'content' => $item->post_content,
                'price' => get_post_meta($item->ID, '_menu_item_price', true),
                'is_market_price' => get_post_meta($item->ID, '_menu_item_is_market_price', true),
                'wine_glass_price' => get_post_meta($item->ID, '_menu_item_wine_glass_price', true),
                'wine_bottle_price' => get_post_meta($item->ID, '_menu_item_wine_bottle_price', true),
                'wine_origin' => get_post_meta($item->ID, '_menu_item_wine_origin', true),
                'notes' => get_post_meta($item->ID, '_menu_item_notes', true),
                'disclaimer' => get_post_meta($item->ID, '_menu_item_disclaimer', true),
            ];
        }, $menu_items);
    
        wp_send_json_success([
            'items' => $formatted_items,
            'pagination' => $this->get_pagination_data(),
        ]);
    }
    
    
}
