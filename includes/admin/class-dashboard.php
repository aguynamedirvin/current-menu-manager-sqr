<?php
namespace sqr;

/**
 * Dashboard UI for the Menu Manager
 */
class Dashboard {
    /**
     * @var Menu
     */
    private $menu;
    private $editor;
    private $ajax_handler;

    /**
     * Initialize the dashboard
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_menu', [$this, 'add_submenu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        $this->menu = new Menu();
        $this->editor = new Menu_Item_Editor();
        $this->ajax_handler = new Ajax_Handler($this->menu);

        // Register AJAX handlers
        add_action('wp_ajax_get_menu_items', [$this->ajax_handler, 'ajax_get_menu_items']);
        add_action('wp_ajax_get_menu', [$this->ajax_handler, 'ajax_get_menu']);
    }

    /**
     * Add the menu page to WordPress admin
     */
    public function add_menu_page() {
        add_menu_page(
            __('Menu Manager', 'the-menu-manager'),
            __('Menu Manager', 'the-menu-manager'),
            'manage_options',   
            'menu-manager',
            [$this, 'render_page'],
            'dashicons-food',
            25
        );
    }

    /**
     * Add submenu pages
     */
    public function add_submenu_pages() {
        global $submenu;

        // Add Categories submenu
        add_submenu_page(
            'menu-manager',
            __('Menu Categories', 'the-menu-manager'),
            __('Categories', 'the-menu-manager'),
            'manage_options',
            'edit-tags.php?taxonomy=menu_category&post_type=menu_item',
            null
        );

        // Add Locations submenu
        add_submenu_page(
            'menu-manager',
            __('Menu Locations', 'the-menu-manager'),
            __('Locations', 'the-menu-manager'),
            'manage_options',
            'edit-tags.php?taxonomy=location&post_type=menu_item',
            null
        );

        // Add Menu Items submenu
        add_submenu_page(
            'menu-manager',
            __('All Menu Items', 'the-menu-manager'),
            __('All Items', 'the-menu-manager'),
            'manage_options',
            'admin.php?page=menu-manager',
            null
        );

        // Add New Menu Item submenu
        add_submenu_page(
            'menu-manager',
            __('Add New Menu Item', 'the-menu-manager'),
            __('Add New', 'the-menu-manager'),
            'manage_options',
            'admin.php?page=menu-manager&action=edit',
            null
        );

        // Fix the URLs in the submenu
        if (isset($submenu['menu-manager'])) {
            foreach ($submenu['menu-manager'] as $key => $item) {
                if (strpos($item[2], 'edit-tags.php') === 0 || 
                    strpos($item[2], 'edit.php') === 0 || 
                    strpos($item[2], 'post-new.php') === 0) {
                    $submenu['menu-manager'][$key][2] = admin_url($item[2]);
                }
            }
        }
    }

    /**
     * Enqueue scripts and styles for the dashboard
     */
    public function enqueue_scripts($hook) {
        // Only load on our menu page
        if ($hook !== 'toplevel_page_menu-manager') {
            return;
        }

        // Enqueue Tailwind CSS from CDN
        wp_enqueue_script(
            'tailwindcss',
            'https://unpkg.com/@tailwindcss/browser@4',
            [],
            '4.0',
            false
        );

        // Pass data to JavaScript
        wp_localize_script('menu-manager-dashboard', 'menuManagerData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('menu_manager_nonce'),
        ]);
    }

    /**
     * Get all menu locations
     */
    private function get_locations() {
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
     * Get all menus
     */
    private function get_menus() {
        return $this->menu->get_menus();
    }

    /**
     * Render the dashboard page
     */
    public function render_page() {
        // Check if we're editing an item
        if (isset($_GET['action']) && $_GET['action'] === 'edit') {
            $this->editor->display();
            return;
        }

        // Get all required data
        $locations = $this->ajax_handler->get_locations();
        $menus = $this->menu->get_menus();
        $menu_items = $this->ajax_handler->get_menu_items();
        $pagination = $this->ajax_handler->get_pagination_data();

        // Include the template
        include __DIR__ . '/templates/main.php';
    }
}
