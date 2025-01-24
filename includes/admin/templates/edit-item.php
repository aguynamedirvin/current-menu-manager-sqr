<?php
/**
 * Edit menu item template
 */
defined('ABSPATH') || exit;
?>

<div class="wrap max-w-8xl">
    <?php if (isset($this->success_message)): ?>
        <div class="mb-6">
            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                <?php 
                                printf(
                                    __('"%s" has been successfully %s.', 'the-menu-manager'),
                                    esc_html($this->success_message['title']),
                                    esc_html($this->success_message['action'])
                                ); 
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex">
                        <a href="<?php echo esc_url($referrer); ?>" 
                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <?php _e('Back to Menu', 'the-menu-manager'); ?> →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-6">
        <div class="flex flex-col">
            <a href="<?php echo esc_url($referrer); ?>" 
            class="text-blue-600 hover:text-blue-800">
                <?php _e('← Back to Menu', 'the-menu-manager'); ?>
            </a>
            <h1 class="!m-0">
                <?php echo $item ? __('Edit Menu Item', 'the-menu-manager') : __('Add Menu Item', 'the-menu-manager'); ?>
            </h1>
        </div>
        <?php if ($item): ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="inline">
                <?php wp_nonce_field('delete_menu_item', 'menu_item_delete_nonce'); ?>
                <input type="hidden" name="action" value="delete_menu_item">
                <input type="hidden" name="item_id" value="<?php echo esc_attr($item->ID); ?>">
                <input type="hidden" name="redirect_url" value="<?php echo esc_attr($referrer); ?>">
                <button type="submit" class="inline-flex cursor-pointer items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <?php _e('Delete', 'the-menu-manager'); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="menu-item-form" class="flex w-full">
        <?php wp_nonce_field('save_menu_item', 'menu_item_nonce'); ?>
        <input type="hidden" name="action" value="save_menu_item">
        <?php if ($item): ?>
            <input type="hidden" name="item_id" value="<?php echo esc_attr($item->ID); ?>">
        <?php endif; ?>

        <div class="flex flex-col w-full md:flex-row gap-6">
            <!-- Main Content Area (Left Column) -->
            <div class="md:w-2/3 flex-1">
                <div class="bg-white shadow rounded-lg p-6">
                    <!-- Title -->
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php _e('Title', 'the-menu-manager'); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               value="<?php echo $item ? esc_attr($item->post_title) : ''; ?>" 
                               class="mt-1 !p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               required>
                    </div>

                    <!-- Description -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php _e('Description', 'the-menu-manager'); ?>
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  rows="4" 
                                  class="mt-1 !p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo $item ? esc_textarea($item->post_content) : ''; ?></textarea>
                    </div>

                    <!-- Price -->
                    <div id="price-container" class="mb-6">
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                                    <?php _e('Price', 'the-menu-manager'); ?>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                    <input type="number" 
                                           name="price" 
                                           id="price" 
                                           step="0.01" 
                                           value="<?php echo esc_attr(get_post_meta($item ? $item->ID : 0, '_menu_item_price', true)); ?>" 
                                           class="mt-1 block w-full !p-1 !pl-8 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           <?php echo get_post_meta($item ? $item->ID : 0, '_menu_item_is_market_price', true) ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                            <div class="flex items-end pb-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" 
                                           name="is_market_price" 
                                           id="is_market_price" 
                                           class="!p-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                           <?php echo get_post_meta($item ? $item->ID : 0, '_menu_item_is_market_price', true) ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-sm text-gray-600"><?php _e('Market Price', 'the-menu-manager'); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Wine Pricing (Only shown for wine categories) -->
                    <div id="wine-pricing" class="mb-6 hidden">
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label for="wine_glass_price" class="block text-sm font-medium text-gray-700 mb-1">
                                    <?php _e('Glass Price', 'the-menu-manager'); ?>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                    <input type="number" 
                                           name="wine_glass_price" 
                                           id="wine_glass_price" 
                                           step="0.01" 
                                           value="<?php echo esc_attr(get_post_meta($item ? $item->ID : 0, '_menu_item_wine_glass_price', true)); ?>" 
                                           class="mt-1 block w-full !p-1 !pl-8 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            <div class="flex-1">
                                <label for="wine_bottle_price" class="block text-sm font-medium text-gray-700 mb-1">
                                    <?php _e('Bottle Price', 'the-menu-manager'); ?>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                    <input type="number" 
                                           name="wine_bottle_price" 
                                           id="wine_bottle_price" 
                                           step="0.01" 
                                           value="<?php echo esc_attr(get_post_meta($item ? $item->ID : 0, '_menu_item_wine_bottle_price', true)); ?>" 
                                           class="mt-1 block w-full !p-1 !pl-8 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="wine_origin" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php _e('Wine Origin', 'the-menu-manager'); ?>
                            </label>
                            <input type="text" 
                                   name="wine_origin" 
                                   id="wine_origin" 
                                   value="<?php echo $item ? esc_attr(get_post_meta($item->ID, '_menu_item_wine_origin', true)) : ''; ?>" 
                                   placeholder="<?php esc_attr_e('e.g., Napa Valley, California', 'the-menu-manager'); ?>"
                                   class="mt-1 !p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php _e('Notes', 'the-menu-manager'); ?>
                        </label>
                        <textarea name="notes" 
                                  id="notes" 
                                  rows="3" 
                                  class="mt-1 !p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="<?php esc_attr_e('Additional notes about this item', 'the-menu-manager'); ?>"
                        ><?php echo esc_textarea(get_post_meta($item ? $item->ID : 0, '_menu_item_notes', true)); ?></textarea>
                    </div>

                    <!-- Disclaimer -->
                    <div class="mb-6">
                        <label for="disclaimer" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php _e('Disclaimer', 'the-menu-manager'); ?>
                        </label>
                        <textarea name="disclaimer" 
                                  id="disclaimer" 
                                  rows="3" 
                                  class="mt-1 !p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="<?php esc_attr_e('Disclaimer shown to customers', 'the-menu-manager'); ?>"
                        ><?php echo esc_textarea(get_post_meta($item ? $item->ID : 0, '_menu_item_disclaimer', true)); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Right Column) -->
            <div class="md:w-1/3 md:max-w-96 space-y-6">
                <!-- Submit -->
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="flex justify-end gap-3">
                        <a href="<?php echo esc_url($referrer); ?>" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <?php _e('Cancel', 'the-menu-manager'); ?>
                        </a>
                        <button type="submit" 
                                class="inline-flex grow justify-center cursor-pointer items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <?php _e('Save Item', 'the-menu-manager'); ?>
                        </button>
                    </div>
                </div>

                <!-- Locations -->
                <div class="bg-white shadow rounded-lg">
                    <div class="border-b border-gray-200 py-4 px-6">
                        <h3 class="text-lg font-bold !m-0"><?php _e('Locations', 'the-menu-manager'); ?> <span class="text-red-500">*</span></h3>
                    </div>
                    <div class="space-y-2 !p-6">
                        <?php foreach ($locations as $location): ?>
                            <label class="flex items-start gap-2 py-1">
                                <input type="checkbox" 
                                       name="locations[]" 
                                       value="<?php echo esc_attr($location->term_id); ?>"
                                       <?php echo $item && has_term($location->term_id, 'location', $item) ? 'checked' : ''; ?>
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span><?php echo esc_html($location->name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Menu Categories -->
                <div class="bg-white shadow rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-bold !m-0"><?php _e('Menu Categories', 'the-menu-manager'); ?> <span class="text-red-500">*</span></h3>
                    </div>
                    <div class="space-y-4 !p-6">
                        <?php
                        // Get parent categories
                        $parent_categories = get_terms(array(
                            'taxonomy' => 'menu_category',
                            'hide_empty' => false,
                            'parent' => 0
                        ));

                        // Get current categories for this item
                        $current_categories = array();
                        if ($item) {
                            $current_categories = wp_get_object_terms($item->ID, 'menu_category', array('fields' => 'ids'));
                        }

                        // Display categories grouped by parent
                        foreach ($parent_categories as $parent) {
                            // Get subcategories
                            $subcategories = get_terms(array(
                                'taxonomy' => 'menu_category',
                                'hide_empty' => false,
                                'parent' => $parent->term_id
                            ));

                            if (!empty($subcategories)) {
                                echo '<div class="mb-4">';
                                echo '<div class="font-medium text-gray-700 mb-2">' . esc_html($parent->name) . '</div>';
                                echo '<div class="ml-4 space-y-2">';
                                
                                foreach ($subcategories as $subcategory) {
                                    ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               id="category_<?php echo esc_attr($subcategory->term_id); ?>" 
                                               name="menu_categories[]"
                                               value="<?php echo esc_attr($subcategory->term_id); ?>"
                                               <?php checked(in_array($subcategory->term_id, $current_categories)); ?>
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="category_<?php echo esc_attr($subcategory->term_id); ?>" 
                                               class="ml-2 text-sm text-gray-900">
                                            <?php echo esc_html($subcategory->name); ?>
                                        </label>
                                    </div>
                                    <?php
                                }
                                
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Make menu categories mutually exclusive
    const handleCategorySelection = function(checkbox) {
        if (checkbox.checked) {
            $('input[name="menu_categories[]"]').not(checkbox).prop('checked', false);
        }
    };

    function checkForWineCategory() {
        let isWineCategory = false;
        const checkedCategory = $('input[name="menu_categories[]"]:checked');
        
        if (checkedCategory.length) {
            // Get the category label text
            const categoryText = checkedCategory.parent().text().trim().toLowerCase();
            
            // Get the parent category name
            const parentCategory = checkedCategory.closest('.menu-category-group')
                                               .find('.menu-category-parent')
                                               .text().trim().toLowerCase();
            
            // Check both current category and parent category for wine-related terms
            isWineCategory = categoryText.includes('wine') || 
                           categoryText.includes('wines') ||
                           parentCategory.includes('wine') ||
                           parentCategory.includes('wines');
        }

        // Show/hide appropriate fields based on wine category
        if (isWineCategory) {
            $('#wine-pricing').removeClass('hidden');
            $('#price-container').addClass('hidden');
            $('#market-price-container').addClass('hidden');
            // Don't clear regular price and market price fields here
            // They will be cleared on form submit if needed
        } else {
            $('#wine-pricing').addClass('hidden');
            $('#price-container').removeClass('hidden');
            $('#market-price-container').removeClass('hidden');
            // Don't clear wine-specific fields here
            // They will be cleared on form submit if needed
        }
    }

    $('input[name="menu_categories[]"]').on('change', function() {
        handleCategorySelection(this);
        checkForWineCategory();
    });

    // Initial check on page load
    checkForWineCategory();

    // Handle market price checkbox
    $('#is_market_price').on('change', function() {
        const priceInput = $('#price');
        if (this.checked) {
            priceInput.prop('disabled', true);
        } else {
            priceInput.prop('disabled', false);
        }
    });

    // Clear appropriate fields on form submit based on category type
    $('#menu-item-form').on('submit', function() {
        const checkedCategory = $('input[name="menu_categories[]"]:checked');
        if (checkedCategory.length) {
            const categoryText = checkedCategory.parent().text().trim().toLowerCase();
            const parentCategory = checkedCategory.closest('.menu-category-group')
                                               .find('.menu-category-parent')
                                               .text().trim().toLowerCase();
            
            const isWineCategory = categoryText.includes('wine') || 
                                 categoryText.includes('wines') ||
                                 parentCategory.includes('wine') ||
                                 parentCategory.includes('wines');

            if (isWineCategory) {
                // Clear regular price and market price for wine items
                $('#price').val('');
                $('#is_market_price').prop('checked', false);
            } else {
                // Clear wine-specific fields for non-wine items
                $('#wine_glass_price, #wine_bottle_price, #wine_origin').val('');
            }
        }
    });
});
</script>