<?php
namespace sqr;

function run_additional_data_import() {
    if (!class_exists('\\sqr\\Additional_Data_Importer')) {
        require_once plugin_dir_path(__FILE__) . 'class-additional-data-importer.php';
    }

    // Check if we've already run this import
    if (get_option('additional_menu_data_imported_1_23_25')) {
        return;
    }

    $importer = new Additional_Data_Importer();
    $importer->import_data();

    // Mark as imported
    update_option('additional_menu_data_imported_1_23_25', true);
}

// Hook into WordPress init with namespace
add_action('admin_init', __NAMESPACE__ . '\\run_additional_data_import');
