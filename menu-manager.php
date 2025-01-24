<?php
/**
 * Plugin Name: Menu Manager
 * Description: A plugin to manage menus for restaurants
 * Version: 1.0.0
 * Author: SquarePixl
 */

namespace sqr;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/dev/class-dev.php';

// Models
require_once plugin_dir_path(__FILE__) . 'includes/models/menu-item.php';
require_once plugin_dir_path(__FILE__) . 'includes/models/class-menu.php';

// AJAX Handler
require_once plugin_dir_path(__FILE__) . 'includes/ajax/class-ajax-handler.php';

// Include core files
require_once __DIR__ . '/includes/models/menu-item.php';
require_once __DIR__ . '/includes/admin/class-menu-item.php';
require_once __DIR__ . '/includes/admin/class-dashboard.php';

/**
 * Main plugin class
 */
class TheMenuManager {
    /**
     * @var TheMenuManager
     */
    private static $instance = null;

    /**
     * @var MenuItem
     */
    private $menu_item;

    /**
     * @var Dashboard
     */
    private $dashboard;

    /**
     * @var Dev
     */
    private $dev;

    /**
     * Initialize the plugin
     */
    private function __construct() {
        $this->menu_item = new MenuItem();
        $menu = new Menu();
        $ajax_handler = new Ajax_Handler($menu);
        $this->dashboard = new Dashboard($menu, $ajax_handler);
        $this->dev = new Dev();

        // Register post types and taxonomies on init
        add_action('init', [$this, 'register_post_types_and_taxonomies']);

        // Add meta boxes
        add_action('add_meta_boxes', [$this->menu_item, 'add_meta_boxes']);
        add_action('save_post_menu_item', [$this->menu_item, 'save_meta_box']);
    }

    /**
     * Register post types and taxonomies
     */
    public function register_post_types_and_taxonomies() {
        $this->menu_item->register_post_type();
        $this->menu_item->register_custom_taxonomies();
    }

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        // Ensure post types and taxonomies are registered
        $menu_item = new MenuItem();
        $menu_item->register_post_type();
        $menu_item->register_custom_taxonomies();

        // Clear permalinks
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['sqr\TheMenuManager', 'activate']);
register_deactivation_hook(__FILE__, ['sqr\TheMenuManager', 'deactivate']);

/**
 * Initialize the plugin
 */
function init_menu_manager() {
    error_log('Menu Manager: Initializing plugin');
    return TheMenuManager::get_instance();
}

// Initialize the plugin
add_action('init', 'sqr\init_menu_manager', 0);
