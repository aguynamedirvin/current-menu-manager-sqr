<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="wp-heading-inline text-2xl font-bold text-gray-900 mb-1">
                <?php _e('Menu Manager', 'the-menu-manager'); ?>
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                <?php _e('Manage your menu items, categories, and locations.', 'the-menu-manager'); ?>
            </p>
        </div>
        <div>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'menu-manager', 'action' => 'edit'], admin_url('admin.php'))); ?>" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium !text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                <?php _e('Add Item', 'the-menu-manager'); ?>
            </a>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-6 mt-6 items-start">
        <!-- Left Sidebar -->
        <div class="flex flex-col md:w-1/4">
            <!-- Location Filter -->
            <div class="mb-6 bg-white shadow rounded-lg">
                <h2 class="text-lg font-bold p-4 pt-1 border-b border-gray-200"><?php _e('Locations', 'the-menu-manager'); ?></h2>
                <ul class="space-y-2 !p-4 !pt-1">
                    <li>
                        <?php 
                        $all_locations_url = add_query_arg([
                            'page' => 'menu-manager',
                            'menu' => isset($_GET['menu']) ? $_GET['menu'] : ''
                        ], admin_url('admin.php'));
                        $all_locations_url = remove_query_arg('location', $all_locations_url);
                        ?>
                        <a href="<?php echo esc_url($all_locations_url); ?>" 
                           class="text-<?php echo empty($_GET['location']) ? 'blue-700 font-semibold' : 'gray-600 hover:text-blue-700'; ?>">
                            <?php _e('All Locations', 'the-menu-manager'); ?>
                        </a>
                    </li>
                    <?php foreach ($locations as $location): ?>
                        <li>
                            <?php 
                            $location_url = add_query_arg([
                                'location' => $location->slug,
                                'menu' => isset($_GET['menu']) ? $_GET['menu'] : '',
                                'section' => isset($_GET['section']) ? $_GET['section'] : ''
                            ], admin_url('admin.php?page=menu-manager'));
                            ?>
                            <a href="<?php echo esc_url($location_url); ?>" 
                               class="text-<?php echo isset($_GET['location']) && $_GET['location'] === $location->slug ? 'blue-700 font-semibold' : 'gray-600 hover:text-blue-700'; ?>">
                                <?php echo esc_html($location->name); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Menu List -->
            <div class="menu-list bg-white shadow rounded-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-4">
                    <h2 class="text-lg font-bold"><?php _e('Menus', 'the-menu-manager'); ?></h2>
                    <?php 
                    $all_menus_url = add_query_arg([
                        'page' => 'menu-manager',
                        'location' => isset($_GET['location']) ? $_GET['location'] : ''
                    ], admin_url('admin.php'));
                    $all_menus_url = remove_query_arg('menu', $all_menus_url);
                    ?>
                    <a href="<?php echo esc_url($all_menus_url); ?>" 
                       class="text-<?php echo empty($_GET['menu']) ? 'blue-700 font-semibold' : 'gray-600 hover:text-blue-700'; ?>">
                        <?php _e('All Menus', 'the-menu-manager'); ?>
                    </a>
                </div>

                <?php if (!empty($menus)): ?>
                    <ul class="space-y-4 !p-4">
                        <?php foreach ($menus as $menu): ?>
                            <li class="menu-item">
                                <!-- Menu Header -->
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <h3 class="font-bold text-gray-800">
                                        <?php 
                                        $menu_url = add_query_arg([
                                            'menu' => $menu['slug'],
                                            'location' => isset($_GET['location']) ? $_GET['location'] : ''
                                        ], admin_url('admin.php?page=menu-manager'));
                                        ?>
                                        <a href="<?php echo esc_url($menu_url); ?>" 
                                           class="text-<?php echo isset($_GET['menu']) && $_GET['menu'] === $menu['slug'] ? 'blue-700' : 'gray-800 hover:text-blue-700'; ?>">
                                            <?php echo esc_html($menu['name']); ?>
                                        </a>
                                    </h3>
                                    <div class="flex gap-2">
                                        <a href="<?php echo esc_url(admin_url('term.php?taxonomy=menu_category&tag_ID=' . $menu['id'])); ?>" 
                                           class="text-blue-500 hover:text-blue-700 text-sm">
                                            <?php _e('Edit', 'the-menu-manager'); ?>
                                        </a>
                                    </div>
                                </div>

                                <!-- Menu Sections -->
                                <?php if (!empty($menu['categories'])): ?>
                                    <ul class="pl-4 mt-2 space-y-2">
                                        <?php foreach ($menu['categories'] as $category): ?>
                                            <li class="flex justify-between items-center py-1">
                                                <a href="<?php 
                                                    echo esc_url(add_query_arg([
                                                        'menu' => $menu['slug'],
                                                        'section' => $category['slug'],
                                                        'location' => isset($_GET['location']) ? $_GET['location'] : ''
                                                    ], admin_url('admin.php?page=menu-manager'))); 
                                                ?>" 
                                                class="!text-<?php echo isset($_GET['section']) && $_GET['section'] === $category['slug'] ? 'blue-700 font-semibold' : 'gray-700 hover:!text-blue-700'; ?>">
                                                    <?php echo esc_html($category['name']); ?>
                                                    <span class="text-gray-500">(<?php echo $category['count']; ?>)</span>
                                                </a>
                                                <div class="flex gap-2">
                                                    <a href="<?php 
                                                        echo esc_url(admin_url('term.php?taxonomy=menu_category&tag_ID=' . $category['id'])); ?>" 
                                                       class="!text-gray-400 hover:!text-blue-700 text-xs">
                                                        <?php _e('Edit', 'the-menu-manager'); ?>
                                                    </a>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4"><?php _e('No menus found', 'the-menu-manager'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6 border-b border-gray-200">
                <div>
                    
                    <?php 
                    // Add location if set
                    if (isset($_GET['location']) && !empty($_GET['location'])) {
                        $location = get_term_by('slug', sanitize_text_field($_GET['location']), 'location');
                        if ($location) {
                            echo "<a href='" . esc_url(admin_url('admin.php?page=menu-manager&location=' . esc_attr($location->slug))) . "'><h2 class='!text-xl font-semibold !text-gray-600 !mb-0 !mt-0'>" . esc_html($location->name) . " › </h2></a>";
                        }
                    }
                    ?>
                    <h3 class="!text-2xl font-semibold text-gray-900 !mt-0">
                        <?php 
                        $header_parts = array();

                        // Add location if set
                        //if (isset($_GET['location']) && !empty($_GET['location'])) {
                        //    $location = get_term_by('slug', sanitize_text_field($_GET['location']), 'location');
                        //    if ($location) {
                        //        $header_parts[] = $location->name;
                        //    }
                        //}

                        // Add menu if set
                        if (isset($_GET['menu'])) {
                            $current_menu = get_term_by('slug', sanitize_text_field($_GET['menu']), 'menu_category');
                            if ($current_menu) {
                                $header_parts[] = "<a href='" . esc_url(admin_url('admin.php?page=menu-manager&menu=' . esc_attr($current_menu->slug))) . "' class='!text-blue-600 hover:!text-blue-800'>" . esc_html($current_menu->name) . "</a>";
                            }
                        }

                        // Add section if set
                        if (isset($_GET['section'])) {
                            $current_section = get_term_by('slug', sanitize_text_field($_GET['section']), 'menu_category');
                            if ($current_section) {
                                $header_parts[] = $current_section->name;
                            }
                        }

                        if (empty($header_parts)) {
                            _e('All Menu Items', 'the-menu-manager');
                        } else {
                            echo implode(' › ', $header_parts);
                        }
                        ?>
                    </h3>
                </div>
                <div>
                    <a href="<?php echo esc_url(add_query_arg(['page' => 'menu-manager', 'action' => 'edit'], admin_url('admin.php'))); ?>" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium !text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <?php _e('Add Item', 'the-menu-manager'); ?>
                    </a>
                </div>
            </div>
            <?php if (!empty($menu_items)): ?>
                <div class="grid grid-cols-1">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="menu-item-card border-b border-gray-200 py-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'menu-manager', 'action' => 'edit', 'item_id' => $item->ID], admin_url('admin.php'))); ?>">
                                        <h3 class="text-lg font-bold !m-0"><?php echo esc_html($item->post_title); ?></h3>
                                    </a>
                                    <div class="text-sm text-gray-500 mt-1">
                                        <?php
                                        $locations = wp_get_post_terms($item->ID, 'location');
                                        if (!is_wp_error($locations) && !empty($locations)) {
                                            echo '<span class="mr-3">' . esc_html('Locations: ' . implode(', ', wp_list_pluck($locations, 'name'))) . '</span>';
                                        }
                                        
                                        $categories = wp_get_post_terms($item->ID, 'menu_category');
                                        if (!is_wp_error($categories) && !empty($categories)) {
                                            echo '<span>' . esc_html('Menu: ' . implode(', ', wp_list_pluck($categories, 'name'))) . '</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <?php 
                                    // Check if it's a wine item by checking category names
                                    $is_wine = false;
                                    if (!is_wp_error($categories) && !empty($categories)) {
                                        foreach ($categories as $cat) {
                                            $cat_name = strtolower($cat->name);
                                            if (strpos($cat_name, 'wine') !== false || strpos($cat_name, 'wines') !== false) {
                                                $is_wine = true;
                                                break;
                                            }
                                            
                                            // Check parent category if exists
                                            if ($cat->parent) {
                                                $parent_cat = get_term($cat->parent, 'menu_category');
                                                if (!is_wp_error($parent_cat)) {
                                                    $parent_name = strtolower($parent_cat->name);
                                                    if (strpos($parent_name, 'wine') !== false || strpos($parent_name, 'wines') !== false) {
                                                        $is_wine = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $is_market_price = get_post_meta($item->ID, '_menu_item_is_market_price', true);
                                    
                                    if ($is_market_price): ?>
                                        <span class="text-lg font-bold">
                                            MP
                                        </span>
                                    <?php elseif ($is_wine): 
                                        $glass_price = get_post_meta($item->ID, '_menu_item_wine_glass_price', true);
                                        $bottle_price = get_post_meta($item->ID, '_menu_item_wine_bottle_price', true);
                                        if ($glass_price || $bottle_price):
                                    ?>
                                        <span class="flex flex-col text-md font-bold">
                                            <?php if ($glass_price): ?>
                                                <span class="mr-2">Glass: $<?php echo number_format($glass_price, 2); ?></span>
                                            <?php endif; ?>
                                            <?php if ($bottle_price): ?>
                                                <span>Bottle: $<?php echo number_format($bottle_price, 2); ?></span>
                                            <?php endif; ?>
                                        </span>
                                    <?php 
                                        endif;
                                    else:
                                        $price = get_post_meta($item->ID, '_menu_item_price', true);
                                        if ($price): ?>
                                            <span class="text-lg font-bold">
                                                $<?php echo number_format($price, 2); ?>
                                            </span>
                                        <?php endif;
                                    endif; ?>
                                    <a href="<?php 
                                        echo esc_url(add_query_arg([
                                            'page' => 'menu-manager',
                                            'action' => 'edit',
                                            'item_id' => $item->ID
                                        ], admin_url('admin.php'))); 
                                    ?>" 
                                    class="text-blue-500 hover:text-blue-700">
                                        <?php _e('Edit', 'the-menu-manager'); ?>
                                    </a>
                                </div>
                            </div>

                            <?php if (!empty($item->post_content)): ?>
                                <div class="text-gray-600 mb-2">
                                    <?php 
                                    $content = wp_strip_all_tags($item->post_content);
                                    $words = str_word_count($content, 1);
                                    if (count($words) > 25) {
                                        echo esc_html(implode(' ', array_slice($words, 0, 25))) . '...';
                                    } else {
                                        echo esc_html($content);
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            $notes = get_post_meta($item->ID, '_menu_item_notes', true);
                            if (!empty($notes)): ?>
                                <div class="text-gray-500 text-sm mb-2">
                                    <?php echo esc_html($notes); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($pagination): ?>
                    <?php include plugin_dir_path(__FILE__) . '../partials/pagination.php'; ?>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4"><?php _e('No items found', 'the-menu-manager'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
