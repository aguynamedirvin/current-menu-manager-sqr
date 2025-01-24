<?php
// Load WordPress
require_once('../../../wp-load.php');

// Set JSON header
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Get the request path
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/mannings-menu/wp-content/plugins/current-menu-manager-sqr/api.php';
$endpoint = str_replace($base_path, '', $request_uri);

// Debug
error_log('API Request: ' . $request_uri);
error_log('Endpoint: ' . $endpoint);

// Initialize menu
require_once('includes/models/class-menu.php');
$menu = new sqr\Menu();

// Handle endpoints
if (strpos($endpoint, 'test') !== false) {
    echo json_encode(['status' => 'ok']);
    exit;
}

if (strpos($endpoint, 'data') !== false) {
    // Get all locations
    $locations = get_terms([
        'taxonomy' => 'location',
        'hide_empty' => false
    ]);

    $data = [];
    foreach ($locations as $location) {
        // Get menus for this location
        $menu_items = get_posts([
            'post_type' => 'menu_item',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'location',
                    'field' => 'term_id',
                    'terms' => $location->term_id
                ]
            ]
        ]);

        // Get all menu categories (sections) for this location's items
        $menu_categories = [];
        $parent_menus = [];
        
        // First, get ALL menu categories
        $all_categories = get_terms([
            'taxonomy' => 'menu_category',
            'hide_empty' => false
        ]);

        // Organize categories by parent/child relationship
        foreach ($all_categories as $term) {
            if ($term->parent === 0) {
                // This is a parent menu
                if (!isset($parent_menus[$term->term_id])) {
                    $parent_menus[$term->term_id] = [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'description' => $term->description,
                        'sections' => []
                    ];
                }
            } else {
                // This is a subcategory
                if (!isset($menu_categories[$term->term_id])) {
                    $menu_categories[$term->term_id] = [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'description' => $term->description,
                        'parent_id' => $term->parent,
                        'items' => []
                    ];
                }
            }
        }

        // Add items to their categories
        foreach ($menu_items as $item) {
            $item_data = [
                'id' => $item->ID,
                'title' => $item->post_title
            ];

            // Add description only if not empty
            if (!empty($item->post_content)) {
                $item_data['description'] = $item->post_content;
            }

            // Add price fields only if they have values
            $price = get_post_meta($item->ID, '_menu_item_price', true);
            if (!empty($price)) {
                $item_data['price'] = $price;
            }

            $is_market_price = get_post_meta($item->ID, '_menu_item_is_market_price', true);
            if (!empty($is_market_price)) {
                $item_data['is_market_price'] = $is_market_price;
            }

            $wine_glass_price = get_post_meta($item->ID, '_menu_item_wine_glass_price', true);
            if (!empty($wine_glass_price)) {
                $item_data['wine_glass_price'] = $wine_glass_price;
            }

            $wine_bottle_price = get_post_meta($item->ID, '_menu_item_wine_bottle_price', true);
            if (!empty($wine_bottle_price)) {
                $item_data['wine_bottle_price'] = $wine_bottle_price;
            }

            $wine_origin = get_post_meta($item->ID, '_menu_item_wine_origin', true);
            if (!empty($wine_origin)) {
                $item_data['wine_origin'] = $wine_origin;
            }

            $notes = get_post_meta($item->ID, '_menu_item_notes', true);
            if (!empty($notes)) {
                $item_data['notes'] = $notes;
            }

            $disclaimer = get_post_meta($item->ID, '_menu_item_disclaimer', true);
            if (!empty($disclaimer)) {
                $item_data['disclaimer'] = $disclaimer;
            }

            $terms = wp_get_post_terms($item->ID, 'menu_category');
            foreach ($terms as $term) {
                if (isset($menu_categories[$term->term_id])) {
                    $menu_categories[$term->term_id]['items'][] = $item_data;
                }
            }
        }

        // Clean up empty values from categories
        foreach ($menu_categories as &$category) {
            if (empty($category['description'])) {
                unset($category['description']);
            }
        }

        // Clean up empty values from parent menus
        foreach ($parent_menus as &$menu) {
            if (empty($menu['description'])) {
                unset($menu['description']);
            }
        }

        // Organize categories under their parent menus
        foreach ($menu_categories as $category) {
            if (isset($parent_menus[$category['parent_id']])) {
                $parent_menus[$category['parent_id']]['sections'][] = $category;
            }
        }

        $location_data = [
            'id' => $location->term_id,
            'name' => $location->name,
            'slug' => $location->slug
        ];

        // Only add description if not empty
        if (!empty($location->description)) {
            $location_data['description'] = $location->description;
        }

        $data[] = [
            'location' => $location_data,
            'menus' => array_values($parent_menus)
        ];
    }

    echo json_encode($data);
    exit;
}

// If no endpoint matched
echo json_encode(['error' => 'Invalid endpoint']);
exit;
