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

// Render the Export to HTML page with filters
function render_export_page() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Get the selected filters (if any)
    $selected_region = isset($_GET['job_region']) ? sanitize_text_field($_GET['job_region']) : '';
    $selected_category = isset($_GET['job_category']) ? sanitize_text_field($_GET['job_category']) : '';
    $selected_author = isset($_GET['job_author']) ? sanitize_text_field($_GET['job_author']) : '';

    // Set posts per page to 200
    $posts_per_page = 200; // Display 200 posts per page
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

    // Build query args based on filters
    $query_args = array(
        'post_type'      => 'job-listings',
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
    );

    if ($selected_region) {
        $query_args['tax_query'][] = array(
            'taxonomy' => 'job-regions',
            'field'    => 'slug',
            'terms'    => $selected_region,
        );
    }

    if ($selected_category) {
        $query_args['tax_query'][] = array(
            'taxonomy' => 'job-categories',
            'field'    => 'slug',
            'terms'    => $selected_category,
        );
    }

    if ($selected_author) {
        $query_args['author'] = $selected_author;
    }

    $query = new WP_Query($query_args);
    $posts = $query->posts;

    // Get all job regions, categories, and authors for filters
    $all_regions = get_terms(array('taxonomy' => 'job-regions', 'hide_empty' => false));
    $all_categories = get_terms(array('taxonomy' => 'job-categories', 'hide_empty' => false));
    $all_authors = get_users(array('who' => 'authors', 'fields' => array('ID', 'display_name')));

    ?>

    <div class="wrap">
        <h1>Export Posts to HTML</h1>

        <!-- Filter Form -->
        <form method="get" action="">
            <input type="hidden" name="page" value="export-to-html" />
            <label for="job_region">Filter by Job Region:</label>
            <select name="job_region" id="job_region">
                <option value="">All Regions</option>
                <?php foreach ($all_regions as $region): ?>
                    <option value="<?php echo esc_attr($region->slug); ?>" <?php selected($selected_region, $region->slug); ?>>
                        <?php echo esc_html($region->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="job_category">Filter by Job Category:</label>
            <select name="job_category" id="job_category">
                <option value="">All Categories</option>
                <?php foreach ($all_categories as $category): ?>
                    <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_category, $category->slug); ?>>
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="job_author">Filter by Author:</label>
            <select name="job_author" id="job_author">
                <option value="">All Authors</option>
                <?php foreach ($all_authors as $author): ?>
                    <option value="<?php echo esc_attr($author->ID); ?>" <?php selected($selected_author, $author->ID); ?>>
                        <?php echo esc_html($author->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="button">Filter</button>
        </form>

        <br style="clear: both; height: 15px;" />

        <!-- Table of Posts -->
        <form method="post">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all" /><label for="select-all" style="margin-left: 5px; cursor: pointer;">Select All</label></th>
                        <th>Date</th>
                        <th>Ref</th>
                        <th>Title/Location/Industry</th>
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
        // Ensure post IDs are selected
        if (empty($_POST['post_ids'])) {
            echo '<div class="notice notice-warning"><p>No posts selected for export.</p></div>';
            return;
        }

        $post_ids = array_map('intval', $_POST['post_ids']); // Sanitize the IDs
        $posts = get_posts(array(
            'post_type'      => 'job-listings',
            'post__in'       => $post_ids,
            'posts_per_page' => -1,
        ));

        if (empty($posts)) {
            echo '<div class="notice notice-warning"><p>No posts found for the selected IDs.</p></div>';
            return;
        }

        // Generate HTML table for export without the Date column
        $html = '<table border="1" cellpadding="5" cellspacing="0">';
        $html .= '<thead><tr><th>Ref</th><th>Title/Location/Industry</th></tr></thead><tbody>';

        foreach ($posts as $post) {
            $post_url = get_permalink($post->ID);
            $title = '<a href="' . esc_url($post_url) . '">' . esc_html($post->post_title) . '</a>';
            $regions = wp_get_post_terms($post->ID, 'job-regions', array('fields' => 'names'));
            $categories = wp_get_post_terms($post->ID, 'job-categories', array('fields' => 'names'));
            $regions_text = $regions ? 'Regions: ' . implode(', ', $regions) : 'Regions: None';
            $categories_text = $categories ? 'Categories: ' . implode(', ', $categories) : 'Categories: None';

            $html .= '<tr>';
            $html .= '<td>' . esc_html(get_post_meta($post->ID, 'reference_number', true)) . '</td>';
            $html .= '<td>' . $title . '<br>' . esc_html($regions_text) . '<br>' . esc_html($categories_text) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Output HTML file
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="exported_posts.html"');
        echo $html;
        exit;
    }
}
add_action('admin_init', 'export_selected_posts_to_html');
