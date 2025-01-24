<?php
namespace sqr;

/**
 * Menu Item Editor class
 */
class Menu_Item_Editor {
    /**
     * Menu instance
     */
    private $menu;

    /**
     * Success message
     */
    private $success_message;

    /**
     * Initialize the editor
     */
    public function __construct() {
        $this->menu = new Menu();
        add_action('admin_post_save_menu_item', [$this, 'save_menu_item']);
        add_action('admin_post_nopriv_save_menu_item', [$this, 'save_menu_item']);
        add_action('admin_post_delete_menu_item', [$this, 'delete_menu_item']);
        add_action('admin_post_nopriv_delete_menu_item', [$this, 'delete_menu_item']);
    }

    /**
     * Display the editor page
     */
    public function display() {
        // Get item if editing
        $item = null;
        if (isset($_GET['item_id'])) {
            $item = get_post($_GET['item_id']);
        }

        // Store referrer URL if coming from another page
        $referrer = wp_get_referer();
        if ($referrer && strpos($referrer, 'menu-manager') !== false) {
            $referrer = esc_url($referrer);
        } else {
            $referrer = add_query_arg('page', 'menu-manager', admin_url('admin.php'));
        }

        // Check for success message in transient
        $message = get_transient('menu_item_success');
        if ($message) {
            $this->success_message = $message;
            delete_transient('menu_item_success');
        }

        // Get menus and locations
        $menus = $this->menu->get_menus();
        $locations = get_terms([
            'taxonomy' => 'location',
            'hide_empty' => false
        ]);

        // Load editor template
        include __DIR__ . '/templates/edit-item.php';
    }

    /**
     * Save menu item
     */
    public function save_menu_item() {
        if (!isset($_POST['menu_item_nonce']) || !wp_verify_nonce($_POST['menu_item_nonce'], 'save_menu_item')) {
            wp_die(__('Invalid nonce specified', 'the-menu-manager'), __('Error', 'the-menu-manager'), array(
                'response' => 403,
                'back_link' => true,
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'the-menu-manager'));
        }

        $post_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';

        if (empty($title)) {
            wp_die(__('Title is required', 'the-menu-manager'), __('Error', 'the-menu-manager'), array(
                'response' => 400,
                'back_link' => true,
            ));
        }

        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_type' => 'menu_item',
            'post_status' => 'publish'
        );

        if ($post_id) {
            $post_data['ID'] = $post_id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id)) {
            wp_die($post_id->get_error_message(), __('Error', 'the-menu-manager'), array(
                'response' => 500,
                'back_link' => true,
            ));
        }

        // Save price
        $is_market_price = isset($_POST['is_market_price']);
        update_post_meta($post_id, '_menu_item_is_market_price', $is_market_price);
        
        if (!$is_market_price && isset($_POST['price'])) {
            update_post_meta($post_id, '_menu_item_price', floatval($_POST['price']));
        } else {
            delete_post_meta($post_id, '_menu_item_price');
        }

        // Save wine pricing if present
        if (isset($_POST['wine_glass_price'])) {
            $glass_price = sanitize_text_field($_POST['wine_glass_price']);
            if ($glass_price !== '') {
                update_post_meta($post_id, '_menu_item_wine_glass_price', floatval($glass_price));
            } else {
                delete_post_meta($post_id, '_menu_item_wine_glass_price');
            }
        }

        // Save bottle price
        if (isset($_POST['wine_bottle_price'])) {
            $bottle_price = sanitize_text_field($_POST['wine_bottle_price']);
            if ($bottle_price !== '') {
                update_post_meta($post_id, '_menu_item_wine_bottle_price', floatval($bottle_price));
            } else {
                delete_post_meta($post_id, '_menu_item_wine_bottle_price');
            }
        }

        // Save wine origin
        if (isset($_POST['wine_origin'])) {
            $wine_origin = sanitize_text_field($_POST['wine_origin']);
            if ($wine_origin !== '') {
                update_post_meta($post_id, '_menu_item_wine_origin', $wine_origin);
            } else {
                delete_post_meta($post_id, '_menu_item_wine_origin');
            }
        }

        // Save notes
        if (isset($_POST['notes'])) {
            $notes = sanitize_textarea_field($_POST['notes']);
            if (!empty($notes)) {
                update_post_meta($post_id, '_menu_item_notes', $notes);
            } else {
                delete_post_meta($post_id, '_menu_item_notes');
            }
        }

        // Save disclaimer
        if (isset($_POST['disclaimer'])) {
            $disclaimer = sanitize_textarea_field($_POST['disclaimer']);
            if (!empty($disclaimer)) {
                update_post_meta($post_id, '_menu_item_disclaimer', $disclaimer);
            } else {
                delete_post_meta($post_id, '_menu_item_disclaimer');
            }
        }

        // Save menu categories
        if (isset($_POST['menu_categories'])) {
            $menu_categories = array_map('intval', $_POST['menu_categories']);
            
            // Only save the actual selected categories, not their parents
            $selected_categories = array();
            foreach ($menu_categories as $cat_id) {
                $term = get_term($cat_id, 'menu_category');
                if (!is_wp_error($term)) {
                    $selected_categories[] = $cat_id;
                }
            }
            
            wp_set_object_terms($post_id, $selected_categories, 'menu_category');
        } else {
            // If no categories selected, remove all category assignments
            wp_set_object_terms($post_id, array(), 'menu_category');
        }

        // Save locations
        $locations = isset($_POST['locations']) ? array_map('intval', $_POST['locations']) : [];
        wp_set_object_terms($post_id, $locations, 'location');

        // Store success message in transient
        set_transient('menu_item_success', [
            'title' => $post_data['post_title'],
            'action' => !empty($_POST['item_id']) ? 'updated' : 'created'
        ], 30);

        // Redirect back to editor
        wp_redirect(add_query_arg([
            'page' => 'menu-manager',
            'action' => 'edit',
            'item_id' => $post_id
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Delete menu item
     */
    public function delete_menu_item() {
        if (!isset($_POST['menu_item_delete_nonce']) || !wp_verify_nonce($_POST['menu_item_delete_nonce'], 'delete_menu_item')) {
            wp_die(__('Invalid nonce specified', 'the-menu-manager'), __('Error', 'the-menu-manager'), array(
                'response' => 403,
                'back_link' => true,
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'the-menu-manager'));
        }

        $post_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw($_POST['redirect_url']) : add_query_arg('page', 'menu-manager', admin_url('admin.php'));
        
        if (!$post_id) {
            wp_die(__('No item specified', 'the-menu-manager'), __('Error', 'the-menu-manager'), array(
                'response' => 400,
                'back_link' => true,
            ));
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'menu_item') {
            wp_die(__('Invalid menu item', 'the-menu-manager'), __('Error', 'the-menu-manager'), array(
                'response' => 404,
                'back_link' => true,
            ));
        }

        // Delete all post meta
        delete_post_meta($post_id, '_menu_item_price');
        delete_post_meta($post_id, '_menu_item_is_market_price');
        delete_post_meta($post_id, '_menu_item_wine_glass_price');
        delete_post_meta($post_id, '_menu_item_wine_bottle_price');
        delete_post_meta($post_id, '_menu_item_wine_origin');
        delete_post_meta($post_id, '_menu_item_notes');
        delete_post_meta($post_id, '_menu_item_disclaimer');

        // Delete the post and force delete (skip trash)
        if (wp_delete_post($post_id, true) === false) {
            wp_die(__('Error deleting menu item', 'the-menu-manager'), __('Error', 'the-menu-manager'), array(
                'response' => 500,
                'back_link' => true,
            ));
        }

        // Set success message
        set_transient('menu_item_success', array(
            'title' => $post->post_title,
            'action' => __('deleted', 'the-menu-manager')
        ), 30);

        // Redirect back to the referring page
        wp_redirect($redirect_url);
        exit;
    }
}
