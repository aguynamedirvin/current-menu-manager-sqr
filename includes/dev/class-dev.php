<?php
namespace sqr;

/**
 * Development tools and functionality
 */
class Dev {
    /**
     * Add development tools to admin menu
     */
    public function add_dev_pages() {
        add_submenu_page(
            'edit.php?post_type=menu_item',
            __('Menu JSON Preview', 'the-menu-manager'),
            __('JSON Preview', 'the-menu-manager'),
            'manage_options',
            'menu-json-preview',
            [$this, 'render_json_preview_page']
        );
    }

    /**
     * Render JSON preview page
     */
    public function render_json_preview_page() {
        $menu_data = new \sqr\MenuData();
        $location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : null;
        $json_data = $menu_data->get_menu_data($location);
        
        // Get all locations for the filter dropdown
        $locations = get_terms([
            'taxonomy' => 'location',
            'hide_empty' => false
        ]);
        ?>
        <div class="wrap">
            <h1><?php _e('Menu JSON Preview', 'the-menu-manager'); ?></h1>
            
            <form method="get">
                <input type="hidden" name="post_type" value="menu_item">
                <input type="hidden" name="page" value="menu-json-preview">
                
                <select name="location">
                    <option value=""><?php _e('All Locations', 'the-menu-manager'); ?></option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo esc_attr($loc->slug); ?>" <?php selected($location, $loc->slug); ?>>
                            <?php echo esc_html($loc->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php submit_button(__('Filter', 'the-menu-manager'), 'secondary', 'submit', false); ?>
            </form>

            <div style="margin-top: 20px;">
                <pre style="background: #f0f0f1; padding: 15px; overflow: auto;">
<?php echo esc_html(json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>
                </pre>
            </div>
        </div>
        <?php
    }

    public function __construct() {
        // Initialize
        $this->init();
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_dev_menu'));
        add_action('admin_init', array($this, 'handle_import'));
        add_action('admin_menu', array($this, 'add_dev_pages'));
    }

    public function handle_import() {
        if (!isset($_POST['import_additional_data'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('import_additional_data', 'import_additional_data_nonce');

        $overwrite_items = isset($_POST['overwrite_items']) ? true : false;
        $overwrite_categories = isset($_POST['overwrite_categories']) ? true : false;

        require_once plugin_dir_path(__FILE__) . 'class-additional-data-importer.php';
        $importer = new Additional_Data_Importer($overwrite_items, $overwrite_categories);
        $importer->import_data();
    }

    public function add_dev_menu() {
        add_submenu_page(
            'menu-manager',
            __('Dev Tools', 'the-menu-manager'),
            __('Dev Tools', 'the-menu-manager'),
            'manage_options',
            'menu-manager-dev',
            array($this, 'render_dev_page')
        );
    }

    public function render_dev_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Dev Tools', 'the-menu-manager'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Import Additional Menu Data', 'the-menu-manager'); ?></h2>
                <p><?php _e('Import additional menu data for Littleton location.', 'the-menu-manager'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('import_additional_data', 'import_additional_data_nonce'); ?>
                    
                    <p>
                        <label>
                            <input type="checkbox" name="overwrite_items" value="1">
                            <?php _e('Overwrite existing items', 'the-menu-manager'); ?>
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <input type="checkbox" name="overwrite_categories" value="1">
                            <?php _e('Overwrite existing categories', 'the-menu-manager'); ?>
                        </label>
                    </p>
                    
                    <input type="submit" 
                           name="import_additional_data"
                           class="button button-primary" 
                           value="<?php esc_attr_e('Import Additional Data', 'the-menu-manager'); ?>">
                </form>
            </div>
        </div>
        <?php
    }
}
