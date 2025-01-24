<?php
defined('ABSPATH') || exit;

/**
 * Pagination partial
 * 
 * @param array $pagination Pagination data with keys:
 *   - current_page: int Current page number
 *   - total_pages: int Total number of pages
 *   - start: int Start item number
 *   - end: int End item number  
 *   - total_items: int Total number of items
 * @param string $current_url Base URL for pagination links
 */

if (!$pagination) {
    return;
}

$current_url = add_query_arg(array_filter([
    'location' => isset($_GET['location']) ? $_GET['location'] : '',
    'menu' => isset($_GET['menu']) ? $_GET['menu'] : '',
    'section' => isset($_GET['section']) ? $_GET['section'] : ''
]), admin_url('admin.php?page=menu-manager'));
?>

<div class="flex justify-between items-center mt-6 pb-8">
    <div class="text-sm text-gray-600">
        <?php 
        printf(
            __('Showing %d to %d of %d items', 'the-menu-manager'),
            $pagination['start'],
            $pagination['end'],
            $pagination['total_items']
        );
        ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="flex gap-2">
        <?php if ($pagination['current_page'] > 1): ?>
            <a href="<?php echo esc_url(add_query_arg('paged', $pagination['current_page'] - 1, $current_url)); ?>" 
               class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50">
                <?php _e('Previous', 'the-menu-manager'); ?>
            </a>
        <?php endif; ?>

        <?php
        $start_page = max(1, $pagination['current_page'] - 2);
        $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);

        if ($start_page > 1): ?>
            <a href="<?php echo esc_url(add_query_arg('paged', 1, $current_url)); ?>" 
               class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50">1</a>
            <?php if ($start_page > 2): ?>
                <span class="px-2 py-1 text-gray-500">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="<?php echo esc_url(add_query_arg('paged', $i, $current_url)); ?>" 
               class="px-3 py-1 text-sm <?php echo $i === $pagination['current_page'] ? 'bg-blue-50 text-blue-600 border-blue-500' : 'bg-white border-gray-300'; ?> border rounded hover:bg-gray-50">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($end_page < $pagination['total_pages']): ?>
            <?php if ($end_page < $pagination['total_pages'] - 1): ?>
                <span class="px-2 py-1 text-gray-500">...</span>
            <?php endif; ?>
            <a href="<?php echo esc_url(add_query_arg('paged', $pagination['total_pages'], $current_url)); ?>" 
               class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50">
                <?php echo $pagination['total_pages']; ?>
            </a>
        <?php endif; ?>

        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
            <a href="<?php echo esc_url(add_query_arg('paged', $pagination['current_page'] + 1, $current_url)); ?>" 
               class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50">
                <?php _e('Next', 'the-menu-manager'); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
</div>
