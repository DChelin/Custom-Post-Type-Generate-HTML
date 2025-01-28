/* ADD EXPORT JOB LISTINGS POST TO HTML FUNCTIONALITY */

// Add the submenu to Tools > Export to HTML
function add_export_menu_item() {
    add_submenu_page(
        'tools.php',              // Parent menu
        'Export to HTML',         // Page title
        'Export to HTML',         // Menu title
        'edit_posts',             // Capability
        'export-to-html',         // Menu slug
        'render_export_page'      // Callback function
    );
}
add_action('admin_menu', 'add_export_menu_item');

// Render the Export to HTML page
function render_export_page() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Set posts per page to 200
    $posts_per_page = 200; // Display 200 posts per page
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

    $query = new WP_Query(array(
        'post_type'      => 'job-listings', // Your custom post type
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
    ));

    $posts = $query->posts;
    ?>
    <div class="wrap">
        <h1>Export Posts to HTML</h1>
        <form method="post">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all" /></th>
                        <th>Date</th>
                        <th>Ref</th>
                        <th>Title/Industry/Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($posts): ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><input type="checkbox" name="post_ids[]" value="<?php echo esc_attr($post->ID); ?>" /></td>
                                <td><?php echo esc_html($post->post_date); ?></td>
                                <td><?php echo esc_html(get_post_meta($post->ID, 'reference_number', true)); ?></td>
                                <td>
                                    <?php 
                                        // Hyperlinked title
                                        $post_url = get_permalink($post->ID);
                                        $title = '<a href="' . esc_url($post_url) . '">' . esc_html($post->post_title) . '</a>';

                                        // Job regions
                                        $regions = wp_get_post_terms($post->ID, 'job-regions', array('fields' => 'names'));
                                        $regions_text = $regions ? 'Regions: ' . implode(', ', $regions) : 'Regions: None';

                                        // Job categories
                                        $categories = wp_get_post_terms($post->ID, 'job-categories', array('fields' => 'names'));
                                        $categories_text = $categories ? 'Categories: ' . implode(', ', $categories) : 'Categories: None';

                                        // Combine into one cell with line breaks
                                        echo $title . '<br>' . esc_html($regions_text) . '<br>' . esc_html($categories_text);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No posts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            // Pagination
            $total_pages = $query->max_num_pages;
            if ($total_pages > 1) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'current'   => $paged,
                    'total'     => $total_pages,
                ));
                echo '</div></div>';
            }
            ?>

            <br>
            <input type="submit" name="export_html" class="button-primary" value="Export Selected to HTML" />
        </form>
    </div>

    <script>
        // JavaScript for "Select All"
        document.getElementById('select-all').addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('input[name="post_ids[]"]');
            for (const checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });
    </script>
    <?php
}

// Handle the HTML export
function export_selected_posts_to_html() {
    if (isset($_POST['export_html'])) {
        if (empty($_POST['post_ids'])) {
            echo '<div class="notice notice-warning"><p>No posts selected for export.</p></div>';
            return;
        }

        $post_ids = array_map('intval', $_POST['post_ids']);
        $posts = get_posts(array(
            'post_type'      => 'job-listings',
            'post__in'       => $post_ids,
            'posts_per_page' => -1,
        ));

        if (empty($posts)) {
            echo '<div class="notice notice-warning"><p>No posts found for the selected IDs.</p></div>';
            return;
        }

        // Prepare HTML output
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="exported_posts.html"');

        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Exported Posts</title>';
        echo '<style>';
        echo 'table { width: 100%; border-collapse: collapse; }';
        echo 'th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }';
        echo 'th { background-color: #f2f2f2; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<h1>Exported Job Listings</h1>';
        echo '<table>';
        echo '<tr><th>Date</th><th>Ref</th><th>Title/Industry/Location</th></tr>';

        foreach ($posts as $post) {
            $reference_number = get_post_meta($post->ID, 'reference_number', true);
            $post_url = get_permalink($post->ID);

            // Job regions and categories
            $regions = wp_get_post_terms($post->ID, 'job-regions', array('fields' => 'names'));
            $regions_text = $regions ? 'Regions: ' . implode(', ', $regions) : 'Regions: None';

            $categories = wp_get_post_terms($post->ID, 'job-categories', array('fields' => 'names'));
            $categories_text = $categories ? 'Categories: ' . implode(', ', $categories) : 'Categories: None';

            echo '<tr>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td>' . esc_html($reference_number ? $reference_number : 'None') . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url($post_url) . '">' . esc_html($post->post_title) . '</a><br>';
            echo esc_html($regions_text) . '<br>';
            echo esc_html($categories_text);
            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '</body>';
        echo '</html>';

        exit;
    }
}
add_action('admin_init', 'export_selected_posts_to_html');
