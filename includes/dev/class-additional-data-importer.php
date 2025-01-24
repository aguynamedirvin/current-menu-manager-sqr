<?php
namespace sqr;

class Additional_Data_Importer {
    private $location_terms = array();
    private $parent_category_id = 83; // wine list category id
    private $location_id = 73; // Littleton location id
    private $messages = array();
    private $category_map = array(); // Maps category names to IDs
    private $menu_data = null;
    private $overwrite_items = false;
    private $overwrite_categories = false;

    public function __construct($overwrite_items = false, $overwrite_categories = false) {
        add_action('admin_notices', array($this, 'display_import_messages'));
        $this->overwrite_items = $overwrite_items;
        $this->overwrite_categories = $overwrite_categories;
        $this->log_debug('Initializing Additional Data Importer');
    }

    private function log_debug($message) {
        $this->messages[] = $message;
        error_log('[Additional Data Importer] ' . $message);
    }

    public function display_import_messages() {
        if (!empty($this->messages)) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>Additional Data Import Results:</strong></p>';
            echo '<ul>';
            foreach ($this->messages as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }

    private function get_location_id() {
        if ($this->location_id === null) {
            $location = get_term_by('name', 'Littleton, CO', 'location');
            if ($location) {
                $this->location_id = $location->term_id;
                $this->log_debug("Found Littleton location ID: " . $this->location_id);
            } else {
                $this->log_debug("Error: Littleton location not found!");
            }
        }
        return $this->location_id;
    }

    private function create_or_get_category($name, $parent_id = 0) {
        $existing = get_term_by('name', $name, 'menu_category');
        if ($existing) {
            $this->log_debug("Found existing category: {$name}");
            return $existing->term_id;
        }

        $result = wp_insert_term($name, 'menu_category', array(
            'parent' => $parent_id
        ));

        if (is_wp_error($result)) {
            $this->log_debug("Error creating category {$name}: " . $result->get_error_message());
            return false;
        }

        $this->log_debug("Created new category: {$name}");
        return $result['term_id'];
    }

    private function load_data() {
        $file = plugin_dir_path(__FILE__) . 'menu-data-wine-1-23-25.php';
        if (!file_exists($file)) {
            $this->log_debug("Data file not found: {$file}");
            return false;
        }

        include $file;
        if (!isset($menu_data) || !is_array($menu_data)) {
            $this->log_debug('Invalid menu data structure');
            return false;
        }

        $this->menu_data = $menu_data;
        return true;
    }

    private function import_categories() {
        $this->log_debug('Starting category import...');
        
        if (!isset($this->menu_data['categories'])) {
            $this->log_debug('No categories found in data');
            return false;
        }

        foreach ($this->menu_data['categories'] as $category) {
            $existing = get_term_by('name', $category['name'], 'menu_category');
            
            if ($existing) {
                if ($this->overwrite_categories) {
                    // Update existing category
                    $result = wp_update_term($existing->term_id, 'menu_category', array(
                        'name' => $category['name'],
                        'parent' => $category['parent']
                    ));
                    
                    if (is_wp_error($result)) {
                        $this->log_debug("Error updating category {$category['name']}: " . $result->get_error_message());
                        continue;
                    }
                    
                    $this->category_map[$category['name']] = $existing->term_id;
                    $this->log_debug("Updated category: {$category['name']}");
                } else {
                    $this->log_debug("Category already exists (skipping): {$category['name']}");
                    $this->category_map[$category['name']] = $existing->term_id;
                }
                continue;
            }

            $result = wp_insert_term(
                $category['name'],
                'menu_category',
                array('parent' => $category['parent'])
            );

            if (is_wp_error($result)) {
                $this->log_debug("Error creating category {$category['name']}: " . $result->get_error_message());
                continue;
            }

            $this->category_map[$category['name']] = $result['term_id'];
            $this->log_debug("Created category: {$category['name']}");
        }

        return true;
    }

    private function create_menu_item($item_data, $category_name) {
        $title = sanitize_text_field($item_data['name']);
        
        // Check if item exists
        $existing = get_page_by_title($title, OBJECT, 'menu_item');
        if ($existing) {
            if ($this->overwrite_items) {
                // Update existing item
                $post_data = array(
                    'ID' => $existing->ID,
                    'post_title' => $title,
                    'post_type' => 'menu_item'
                );

                if (isset($item_data['description'])) {
                    $post_data['post_content'] = $item_data['description'];
                }

                $updated = wp_update_post($post_data);
                if (is_wp_error($updated)) {
                    $this->log_debug("Error updating item {$title}: " . $updated->get_error_message());
                    return false;
                }

                // Update wine prices
                if (isset($item_data['meta']['price_glass'])) {
                    update_post_meta($existing->ID, '_menu_item_wine_glass_price', floatval($item_data['meta']['price_glass']));
                }
                if (isset($item_data['meta']['price_bottle'])) {
                    update_post_meta($existing->ID, '_menu_item_wine_bottle_price', floatval($item_data['meta']['price_bottle']));
                }
                // Update regular price
                if (isset($item_data['price'])) {
                    update_post_meta($existing->ID, '_menu_item_price', floatval($item_data['price']));
                }
                // Update wine origin
                if (isset($item_data['meta']['origin'])) {
                    update_post_meta($existing->ID, '_menu_item_wine_origin', $item_data['meta']['origin']);
                }
                // Update notes
                if (isset($item_data['notes'])) {
                    update_post_meta($existing->ID, '_menu_item_notes', $item_data['notes']);
                }

                // Update category
                if (isset($this->category_map[$category_name])) {
                    wp_set_object_terms($existing->ID, $this->category_map[$category_name], 'menu_category');
                }

                // Update location
                wp_set_object_terms($existing->ID, $this->location_id, 'location');

                $this->log_debug("Updated item: {$title}");
                return true;
            } else {
                $this->log_debug("Item already exists (skipping): {$title}");
                return false;
            }
        }

        // Create new item
        $post_data = array(
            'post_title' => $title,
            'post_status' => 'publish',
            'post_type' => 'menu_item'
        );

        if (isset($item_data['description'])) {
            $post_data['post_content'] = $item_data['description'];
        }

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $this->log_debug("Error creating item {$title}: " . $post_id->get_error_message());
            return false;
        }

        // Set wine prices
        if (isset($item_data['meta']['price_glass'])) {
            update_post_meta($post_id, '_menu_item_wine_glass_price', floatval($item_data['meta']['price_glass']));
        }
        if (isset($item_data['meta']['price_bottle'])) {
            update_post_meta($post_id, '_menu_item_wine_bottle_price', floatval($item_data['meta']['price_bottle']));
        }
        // Set regular price
        if (isset($item_data['price'])) {
            update_post_meta($post_id, '_menu_item_price', floatval($item_data['price']));
        }
        // Set wine origin
        if (isset($item_data['meta']['origin'])) {
            update_post_meta($post_id, '_menu_item_wine_origin', $item_data['meta']['origin']);
        }
        // Set notes
        if (isset($item_data['notes'])) {
            update_post_meta($post_id, '_menu_item_notes', $item_data['notes']);
        }

        // Set category
        if (isset($this->category_map[$category_name])) {
            wp_set_object_terms($post_id, $this->category_map[$category_name], 'menu_category');
        } else {
            $this->log_debug("Category not found for item {$title}: {$category_name}");
        }

        // Set location
        wp_set_object_terms($post_id, $this->location_id, 'location');

        $this->log_debug("Created item: {$title}");
        return true;
    }

    private function import_items() {
        $this->log_debug('Starting items import...');
        
        if (!isset($this->menu_data['items'])) {
            $this->log_debug('No items found in data');
            return false;
        }

        foreach ($this->menu_data['items'] as $item) {
            if (!isset($item['category'])) {
                $this->log_debug("Item {$item['name']} has no category");
                continue;
            }

            $this->create_menu_item($item, $item['category']);
        }

        return true;
    }

    public function import_data() {
        $this->log_debug('Starting import process...');
        
        // Load data first
        if (!$this->load_data()) {
            $this->log_debug('Failed to load menu data');
            return;
        }
        
        // Step 1: Import categories
        if (!$this->import_categories()) {
            $this->log_debug('Import failed at category stage');
            return;
        }

        // Get location ID first
        if (!$this->get_location_id()) {
            $this->log_debug('Cannot proceed without Littleton location');
            return;
        }

        // Step 2: Import items
        if (!$this->import_items()) {
            $this->log_debug('Import failed at items stage');
            return;
        }

        $this->log_debug('Import completed successfully');
    }
}
