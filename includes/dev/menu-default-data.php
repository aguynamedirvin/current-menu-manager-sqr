<?php
namespace sqr;

// To disable this importer, simply comment out the following line
define('MENU_IMPORT_ENABLED', true);

class Menu_Data_Importer {
    private $locations = [
        'Lakewood, CO',
        'Littleton, CO'
    ];

    private $location_terms = [];
    private $menu_terms = [];

    public function __construct() {
        if (!defined('MENU_IMPORT_ENABLED') || !MENU_IMPORT_ENABLED) {
            return;
        }

        add_action('admin_init', [$this, 'maybe_import_data']);
    }

    public function maybe_import_data() {
        // Check if we've already imported
        if (get_option('menu_data_imported')) {
            return;
        }

        $this->import_data();
        update_option('menu_data_imported', true);
    }

    private function log_debug($message) {
        error_log('MENU IMPORTER: ' . print_r($message, true));
    }

    private function import_data() {
        $this->log_debug('Starting import...');
        
        // Step 1: Create Locations
        $this->create_locations();
        
        // Get JSON content
        $json_file = plugin_dir_path(__FILE__) . 'menu-data.json';
        if (!file_exists($json_file)) {
            $this->log_debug('Menu data file not found');
            return;
        }

        $json_content = file_get_contents($json_file);
        if (!$json_content) {
            $this->log_debug('Could not read menu data file');
            return;
        }

        $menu_data = json_decode($json_content, true);
        if (!$menu_data) {
            $this->log_debug('Failed to decode JSON data');
            return;
        }

        // Step 2: Create Categories
        foreach ($menu_data as $menu_type => $section) {
            if (!is_array($section) || !isset($section['categories'])) {
                $this->log_debug("Skipping {$menu_type} - no categories found");
                continue;
            }

            $this->log_debug("Processing menu type: {$menu_type}");

            // Create parent menu category (e.g., "Wine List", "Food")
            $menu_name = isset($section['name']) ? $section['name'] : ucfirst($menu_type);
            
            // Create a clean slug for the parent menu
            $menu_slug = sanitize_title($menu_name);
            
            // Check if parent menu already exists by slug
            $existing_menu = get_term_by('slug', $menu_slug, 'menu_category');
            if ($existing_menu) {
                $parent_term = ['term_id' => $existing_menu->term_id];
                $this->log_debug("Found existing menu category: {$menu_name}");
            } else {
                $parent_term = wp_insert_term($menu_name, 'menu_category', [
                    'slug' => $menu_slug
                ]);
                
                if (is_wp_error($parent_term)) {
                    $this->log_debug("Error creating menu category {$menu_name}: " . $parent_term->get_error_message());
                    continue;
                }
                $this->log_debug("Created new menu category: {$menu_name}");
            }

            // Store menu disclaimer if present
            if (isset($section['disclaimer'])) {
                update_term_meta($parent_term['term_id'], 'disclaimer', sanitize_text_field($section['disclaimer']));
                $this->log_debug("Added disclaimer to {$menu_name}");
            }

            // Create subcategories
            foreach ($section['categories'] as $category) {
                if (!isset($category['name'])) {
                    continue;
                }

                // Create a clean slug for the subcategory
                $subcategory_slug = sanitize_title($category['name'] . '-' . $menu_slug);

                // Check if subcategory already exists by slug
                $existing_subterm = get_term_by('slug', $subcategory_slug, 'menu_category');
                if ($existing_subterm) {
                    // If it exists but under a different parent, update its parent
                    if ($existing_subterm->parent != $parent_term['term_id']) {
                        wp_update_term($existing_subterm->term_id, 'menu_category', [
                            'parent' => $parent_term['term_id']
                        ]);
                        $this->log_debug("Updated parent for {$category['name']} under {$menu_name}");
                    }
                    $subcategory_term = ['term_id' => $existing_subterm->term_id];
                    $this->log_debug("Found existing subcategory: {$category['name']} under {$menu_name}");
                } else {
                    // Create the subcategory with a unique slug
                    $subcategory_term = wp_insert_term(
                        $category['name'],
                        'menu_category',
                        [
                            'parent' => $parent_term['term_id'],
                            'slug' => $subcategory_slug
                        ]
                    );

                    if (is_wp_error($subcategory_term)) {
                        $this->log_debug("Error creating subcategory {$category['name']}: " . $subcategory_term->get_error_message());
                        continue;
                    }
                    $this->log_debug("Created new subcategory: {$category['name']} under {$menu_name}");
                }

                // Add description if exists
                if (isset($category['description'])) {
                    update_term_meta($subcategory_term['term_id'], 'description', sanitize_text_field($category['description']));
                }

                // Add note if exists
                if (isset($category['note'])) {
                    update_term_meta($subcategory_term['term_id'], 'note', sanitize_text_field($category['note']));
                }

                // Create menu items for this category
                if (isset($category['items']) && is_array($category['items'])) {
                    foreach ($category['items'] as $item) {
                        $this->create_menu_item($item, $subcategory_term['term_id'], $menu_type);
                    }
                }
            }
        }
    }

    private function create_menu_item($item_data, $category_id, $menu_type) {
        $title = sanitize_text_field($item_data['name']);
        
        // Check for existing item by title
        $existing_item = get_page_by_title($title, OBJECT, 'menu_item');
        if ($existing_item) {
            $this->log_debug("Menu item already exists: {$title}");
            return;
        }

        // Create the menu item
        $post_data = array(
            'post_title' => $title,
            'post_content' => isset($item_data['description']) ? sanitize_text_field($item_data['description']) : '',
            'post_status' => 'publish',
            'post_type' => 'menu_item'
        );

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            $this->log_debug("Error creating menu item {$title}: " . $post_id->get_error_message());
            return;
        }

        $this->log_debug("Created menu item: {$title}");

        // Assign to all locations
        wp_set_object_terms($post_id, $this->location_terms, 'location');
        
        // Assign to category
        wp_set_object_terms($post_id, array($category_id), 'menu_category');

        // Handle notes if present
        if (isset($item_data['notes'])) {
            update_post_meta($post_id, '_menu_item_notes', sanitize_text_field($item_data['notes']));
            $this->log_debug("Added note to {$title}");
        }

        // Handle disclaimer if present
        if (isset($item_data['disclaimer'])) {
            update_post_meta($post_id, '_menu_item_disclaimer', sanitize_text_field($item_data['disclaimer']));
            $this->log_debug("Added disclaimer to {$title}");
        }

        // Handle price fields based on menu type
        if ($menu_type === 'wine') {
            // Handle wine-specific fields
            if (isset($item_data['glassPrice'])) {
                update_post_meta($post_id, '_menu_item_wine_glass_price', $item_data['glassPrice']);
            }
            if (isset($item_data['bottlePrice'])) {
                update_post_meta($post_id, '_menu_item_wine_bottle_price', $item_data['bottlePrice']);
            }
            if (isset($item_data['origin'])) {
                update_post_meta($post_id, '_menu_item_wine_origin', sanitize_text_field($item_data['origin']));
            }
        } else {
            // Handle regular price or market price
            if (isset($item_data['price'])) {
                if ($item_data['price'] === 'MP') {
                    update_post_meta($post_id, '_menu_item_is_market_price', '1');
                } else {
                    $price = str_replace('$', '', $item_data['price']);
                    if (is_numeric($price)) {
                        update_post_meta($post_id, '_menu_item_price', $price);
                    }
                }
            }
        }
    }

    private function create_locations() {
        $locations = array(
            'Lakewood, CO',
            'Littleton, CO'
        );

        $this->location_terms = array();
        foreach ($locations as $location) {
            $existing_location = get_term_by('name', $location, 'location');
            if ($existing_location) {
                $this->log_debug("Found existing location: {$location} with ID: {$existing_location->term_id}");
                $this->location_terms[] = $existing_location->term_id;
            } else {
                $location_term = wp_insert_term($location, 'location');
                if (!is_wp_error($location_term)) {
                    $this->log_debug("Created location: {$location} with ID: {$location_term['term_id']}");
                    $this->location_terms[] = $location_term['term_id'];
                }
            }
        }
    }
}

// Initialize the importer
new Menu_Data_Importer();
