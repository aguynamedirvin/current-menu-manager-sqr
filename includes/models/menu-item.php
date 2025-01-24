<?php
namespace sqr;

/**
 * Menu Item Registration and Management
 * Handles the registration and management of the menu item custom post type
 * 
 * includes/models/menu-item.php
 */
class MenuItem {
    /**
     * Register the Menu Item custom post type
     */
    public function register_post_type() {
        $labels = [
            'name' => __('Menu Items', 'the-menu-manager'),
            'singular_name' => __('Menu Item', 'the-menu-manager'),
            'add_new' => __('Add New', 'the-menu-manager'),
            'add_new_item' => __('Add New Menu Item', 'the-menu-manager'),
            'edit_item' => __('Edit Menu Item', 'the-menu-manager'),
            'new_item' => __('New Menu Item', 'the-menu-manager'),
            'view_item' => __('View Menu Item', 'the-menu-manager'),
            'search_items' => __('Search Menu Items', 'the-menu-manager'),
            'not_found' => __('No menu items found', 'the-menu-manager'),
            'not_found_in_trash' => __('No menu items found in trash', 'the-menu-manager'),
            'parent_item_colon' => __('Parent Menu Item:', 'the-menu-manager'),
            'menu_name' => __('Menu Items', 'the-menu-manager')
        ];

        $args = [
            'labels' => $labels,
            'description' => __('Menu items for restaurants', 'the-menu-manager'),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-food',
            'query_var' => true,
            'rewrite' => ['slug' => 'menu-item'],
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => ['title', 'editor'],
            'show_in_rest' => true,
        ];

        register_post_type('menu_item', $args);

        // Register meta fields
        register_post_meta('menu_item', '_menu_item_notes', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
        
        register_post_meta('menu_item', '_menu_item_price', [
            'type' => 'number',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_post_meta('menu_item', '_menu_item_is_market_price', [
            'type' => 'boolean',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_post_meta('menu_item', '_menu_item_wine_glass_price', [
            'type' => 'number',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_post_meta('menu_item', '_menu_item_wine_bottle_price', [
            'type' => 'number',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_post_meta('menu_item', '_menu_item_wine_origin', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
    }

    /**
     * Register custom taxonomies
     */
    public function register_custom_taxonomies() {
        // Register Menu Category taxonomy
        register_taxonomy('menu_category', 'menu_item', [
            'labels' => [
                'name' => __('Menu Categories'),
                'singular_name' => __('Menu Category'),
                'menu_name' => __('Categories'),
                'all_items' => __('All Categories'),
                'edit_item' => __('Edit Category'),
                'view_item' => __('View Category'),
                'update_item' => __('Update Category'),
                'add_new_item' => __('Add New Category'),
                'new_item_name' => __('New Category Name'),
                'parent_item' => __('Parent Category'),
                'parent_item_colon' => __('Parent Category:'),
                'search_items' => __('Search Categories'),
                'popular_items' => __('Popular Categories'),
                'separate_items_with_commas' => __('Separate categories with commas'),
                'add_or_remove_items' => __('Add or remove categories'),
                'choose_from_most_used' => __('Choose from the most used categories'),
                'not_found' => __('No categories found'),
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'menu-category'],
            'show_in_rest' => true,
        ]);

        // Register Location taxonomy
        register_taxonomy('location', 'menu_item', [
            'labels' => [
                'name' => __('Locations'),
                'singular_name' => __('Location'),
                'menu_name' => __('Locations'),
                'all_items' => __('All Locations'),
                'edit_item' => __('Edit Location'),
                'view_item' => __('View Location'),
                'update_item' => __('Update Location'),
                'add_new_item' => __('Add New Location'),
                'new_item_name' => __('New Location Name'),
                'search_items' => __('Search Locations'),
                'popular_items' => __('Popular Locations'),
                'separate_items_with_commas' => __('Separate locations with commas'),
                'add_or_remove_items' => __('Add or remove locations'),
                'choose_from_most_used' => __('Choose from the most used locations'),
                'not_found' => __('No locations found'),
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'location'],
            'show_in_rest' => true,
        ]);
    }

    /**
     * Add meta boxes to the menu item post type
     */
    public function add_menu_item_meta_boxes() {
        add_meta_box(
            'menu_item_details',
            'Menu Item Details',
            [$this, 'render_menu_item_meta_box'],
            'menu_item',
            'normal',
            'high'
        );
    }

    /**
     * Render menu item meta boxes
     */
    public function render_menu_item_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('menu_item_meta_box', 'menu_item_meta_box_nonce');

        // Get the current values
        $price = get_post_meta($post->ID, '_menu_item_price', true);
        $is_market_price = get_post_meta($post->ID, '_menu_item_is_market_price', true);
        $wine_glass_price = get_post_meta($post->ID, '_menu_item_wine_glass_price', true);
        $wine_bottle_price = get_post_meta($post->ID, '_menu_item_wine_bottle_price', true);
        $wine_origin = get_post_meta($post->ID, '_menu_item_wine_origin', true);
        $notes = get_post_meta($post->ID, '_menu_item_notes', true);
        $disclaimer = get_post_meta($post->ID, '_menu_item_disclaimer', true);
        ?>
        <div class="menu-item-fields">
            <p>
                <label for="menu_item_price">Price:</label>
                <input type="text" id="menu_item_price" name="menu_item_price" value="<?php echo esc_attr($price); ?>">
            </p>
            <p>
                <label for="menu_item_is_market_price">Market Price:</label>
                <input type="checkbox" id="menu_item_is_market_price" name="menu_item_is_market_price" <?php checked($is_market_price, 'yes'); ?>>
            </p>
            <p>
                <label for="menu_item_wine_glass_price">Wine Glass Price:</label>
                <input type="text" id="menu_item_wine_glass_price" name="menu_item_wine_glass_price" value="<?php echo esc_attr($wine_glass_price); ?>">
            </p>
            <p>
                <label for="menu_item_wine_bottle_price">Wine Bottle Price:</label>
                <input type="text" id="menu_item_wine_bottle_price" name="menu_item_wine_bottle_price" value="<?php echo esc_attr($wine_bottle_price); ?>">
            </p>
            <p>
                <label for="menu_item_wine_origin">Wine Origin:</label>
                <input type="text" id="menu_item_wine_origin" name="menu_item_wine_origin" value="<?php echo esc_attr($wine_origin); ?>" placeholder="e.g., Napa Valley, California">
            </p>
            <p>
                <label for="menu_item_notes">Notes:</label>
                <textarea id="menu_item_notes" name="menu_item_notes"><?php echo esc_textarea($notes); ?></textarea>
            </p>
            <p>
                <label for="menu_item_disclaimer">Disclaimer:</label>
                <textarea id="menu_item_disclaimer" name="menu_item_disclaimer"><?php echo esc_textarea($disclaimer); ?></textarea>
            </p>
        </div>
        <style>
            .menu-item-fields label {
                display: inline-block;
                width: 120px;
                font-weight: bold;
            }
            .menu-item-fields input[type="text"] {
                width: 200px;
            }
            .menu-item-fields textarea {
                width: 100%;
                height: 100px;
            }
        </style>
        <?php
    }

    /**
     * Save the meta box data
     */
    public function save_menu_item_meta_box($post_id) {
        // Security checks
        if (!isset($_POST['menu_item_meta_box_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['menu_item_meta_box_nonce'], 'menu_item_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save the fields
        $fields = [
            'menu_item_price',
            'menu_item_is_market_price',
            'menu_item_wine_glass_price',
            'menu_item_wine_bottle_price',
            'menu_item_wine_origin',
            'menu_item_notes',
            'menu_item_disclaimer'
        ];

        foreach ($fields as $field) {
            $value = isset($_POST[$field]) ? $_POST[$field] : '';
            if ($field === 'menu_item_is_market_price') {
                $value = $value ? 'yes' : 'no';
            }
            update_post_meta($post_id, '_' . $field, sanitize_text_field($value));
        }
    }
}