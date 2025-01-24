<?php
namespace sqr;

/**
 * Dashboard UI for the Menu Manager
 * 
 * includes/admin/clas-dashboard.php
 */
class Dashboard {
    /**
     * @var Menu
     */
    private $menu;
    private $editor;
    private $data_handler;

    /**
     * Initialize the dashboard
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_menu', [$this, 'add_submenu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        $this->menu = new Menu();
        $this->editor = new Menu_Item_Editor();
        $this->data_handler = new Data_Handler($this->menu);
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
     * Render the dashboard page
     */
    public function render_page() {
        // Check if we're editing an item
        if (isset($_GET['action']) && $_GET['action'] === 'edit') {
            $this->editor->display();
            return;
        }

        // Check if we have filters set
        $filters = [];
        $filters['paged'] = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $filters['items_per_page'] = isset($_GET['items_per_page']) ? intval($_GET['items_per_page']) : 15;
        if (isset($_GET['location'])) {
            $filters['location'] = sanitize_text_field($_GET['location']);
        }
        if (isset($_GET['menu'])) {
            $filters['menu'] = sanitize_text_field($_GET['menu']);
        }
        if (isset($_GET['section'])) {
            $filters['section'] = sanitize_text_field($_GET['section']);
        }

        // Get all required data
        $locations = $this->data_handler->get_locations();
        $menus = $this->data_handler->fetch_menus();
        $menu_items = $this->data_handler->fetch_menu_items($filters, $filters['items_per_page'], $filters['paged']);
        $pagination = $this->data_handler->get_pagination_data();

        // Include the template
        include __DIR__ . '/views/main.php';
    }
}
