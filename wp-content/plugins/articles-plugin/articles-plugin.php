<?php
/**
 * Plugin Name: Articles Plugin
 * Description: A plugin to manage articles and categories.
 * Version: 1.0
 * Author: alabenayed
 */

add_filter('show_admin_bar', '__return_false');

// Add menu pages
function articles_plugin_add_menu() {
    add_menu_page(
        'View Data',        // Page title
        'Articles Plugin',  // Menu title
        'manage_options',   // Capability
        'articles-plugin-view-data', // Menu slug
        'articles_plugin_view_data_page', // Callback function
        'dashicons-admin-plugins', // Icon (optional)
        30 // Position (optional)
    );

    add_submenu_page(
        'articles-plugin-view-data', // Parent slug
        'Add Entry',       // Page title
        'Add Entry',       // Menu title
        'manage_options',  // Capability
        'articles-plugin-add-entry', // Menu slug
        'articles_plugin_add_entry_page' // Callback function
    );
}
add_action('admin_menu', 'articles_plugin_add_menu');

// Create database tables on plugin activation
function articles_plugin_create_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$wpdb->prefix}articles (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        brand varchar(255) NOT NULL,
        category varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}article_categories (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'articles_plugin_create_table');

// Add entry page
function articles_plugin_add_entry_page() {
    echo do_shortcode('[articles_plugin_form]');
}

// View data page
function articles_plugin_view_data_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'articles';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h1>View Data</h1>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th class="manage-column column-columnname" scope="col">ID</th>
                    <th class="manage-column column-columnname" scope="col">Article Name</th>
                    <th class="manage-column column-columnname" scope="col">Brand</th>
                    <th class="manage-column column-columnname" scope="col">Category</th>
                    <th class="manage-column column-columnname" scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)) {
                    foreach ($results as $row) { ?>
                        <tr class="alternate">
                            <td class="column-columnname"><?php echo esc_html($row->id); ?></td>
                            <td class="column-columnname"><?php echo esc_html($row->name); ?></td>
                            <td class="column-columnname"><?php echo esc_html($row->brand); ?></td>
                            <td class="column-columnname"><?php echo esc_html($row->category); ?></td>
                            <td class="column-columnname">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo esc_attr($row->id); ?>" />
                                    <input type="submit" name="delete" value="Delete" class="button button-secondary" />
                                </form>
                                <a href="<?php echo admin_url('admin.php?page=articles-plugin-add-entry&edit_id=' . esc_attr($row->id)); ?>" class="button button-secondary">Edit</a>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr class="no-items">
                        <td class="colspanchange" colspan="5">No data found.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <p class="submit">
            <a href="<?php echo admin_url('admin.php?page=articles-plugin-add-entry'); ?>" class="button button-primary">Add Entry</a>
        </p>
    </div>
    <?php

    if (isset($_POST['delete'])) {
        $delete_id = intval($_POST['delete_id']);
        $wpdb->delete($table_name, ['id' => $delete_id]);
        echo "<script>location.replace('" . admin_url('admin.php?page=articles-plugin-view-data') . "');</script>";
    }
}

// Shortcode for the form
function articles_plugin_form_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'articles';

    if (isset($_POST['submit'])) {
        $name = sanitize_text_field($_POST['name']);
        $brand = sanitize_text_field($_POST['brand']);
        $category = sanitize_text_field($_POST['category']);

        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $edit_id = intval($_POST['edit_id']);
            $wpdb->update(
                $table_name,
                ['name' => $name, 'brand' => $brand, 'category' => $category],
                ['id' => $edit_id]
            );
        } else {
            $wpdb->insert(
                $table_name,
                ['name' => $name, 'brand' => $brand, 'category' => $category]
            );
        }
    }

    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $name = '';
    $brand = '';
    $category = '';

    if ($edit_id) {
        $entry = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $edit_id");
        if ($entry) {
            $name = $entry->name;
            $brand = $entry->brand;
            $category = $entry->category;
        }
    }

    ob_start();
    ?>
    <div class="wrap">
        <h1><?php echo $edit_id ? 'Edit Entry' : 'Add Entry'; ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="name">Article Name</label></th>
                    <td><input type="text" id="name" name="name" value="<?php echo esc_attr($name); ?>" class="regular-text" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="brand">Article Brand</label></th>
                    <td><input type="text" id="brand" name="brand" value="<?php echo esc_attr($brand); ?>" class="regular-text" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="category">Article Category</label></th>
                    <td><input type="text" id="category" name="category" value="<?php echo esc_attr($category); ?>" class="regular-text" required /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_id); ?>" />
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" />
            </p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('articles_plugin_form', 'articles_plugin_form_shortcode');

// Enqueue scripts and styles
function articles_plugin_scripts() {
    wp_enqueue_script('articles-plugin-script', plugin_dir_url(__FILE__) . 'js/articles-plugin.js', array('jquery', 'jquery-ui-autocomplete'), '1.0', true);
    wp_localize_script('articles-plugin-script', 'articlesPluginAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    wp_enqueue_style('articles-plugin-style', plugin_dir_url(__FILE__) . 'css/style.css');
}
add_action('wp_enqueue_scripts', 'articles_plugin_scripts');

// Handle AJAX request to search articles
// function articles_plugin_search_articles() {
//     global $wpdb;
//     $search_term = sanitize_text_field($_POST['search_term']);
//     $results = $wpdb->get_results($wpdb->prepare(
//         "SELECT name FROM {$wpdb->prefix}articles WHERE name LIKE %s",
//         '%' . $wpdb->esc_like($search_term) . '%'
//     ));

//     wp_send_json($results);
// }
function articles_plugin_search_articles() {
    global $wpdb;
    $search_term = sanitize_text_field($_POST['search_term']);
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}articles WHERE name LIKE %s",
        $wpdb->esc_like($search_term) . '%'
    ));

    wp_send_json($results);
}
add_action('wp_ajax_search_articles', 'articles_plugin_search_articles');
add_action('wp_ajax_nopriv_search_articles', 'articles_plugin_search_articles');

// Shortcode to display articles page
function articles_plugin_shortcode() {
    ob_start();
    ?>
    <div class="articles-filter">
        <select id="articles-category-filter">
            <option value="">All Categories</option>
            <?php
            global $wpdb;
            $categories = $wpdb->get_results("SELECT DISTINCT category FROM {$wpdb->prefix}articles");
            foreach ($categories as $category) {
                echo '<option value="' . esc_attr($category->category) . '">' . esc_html($category->category) . '</option>';
            }
            ?>
        </select>
        <select id="articles-brand-filter">
            <option value="">All Brands</option>
            <?php
            $brands = $wpdb->get_results("SELECT DISTINCT brand FROM {$wpdb->prefix}articles");
            foreach ($brands as $brand) {
                echo '<option value="' . esc_attr($brand->brand) . '">' . esc_html($brand->brand) . '</option>';
            }
            ?>
        </select>
        <input type="text" id="articles-search" placeholder="Search articles..." />
    </div>
    <div id="articles-list">
        <?php
        $articles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}articles");
        foreach ($articles as $article) {
            echo '<div class="article-item">';
            echo '<h2>' . esc_html($article->name) . '</h2>';
            echo '<p>Brand: ' . esc_html($article->brand) . '</p>';
            echo '<p>Category: ' . esc_html($article->category) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('articles_plugin', 'articles_plugin_shortcode');

?>
