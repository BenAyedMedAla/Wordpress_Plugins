<?php
/**
 * Plugin Name: myplugin
 * Description: A plugin to disable the admin bar and store form data in a custom database table.
 * Version: 1.0
 * Author: alabenayed
 */

add_filter('show_admin_bar', '__return_false');

function myplugin_add_menu() {
    add_menu_page(
        'View Data',        // Page title
        'My Plugin',        // Menu title
        'manage_options',   // Capability
        'myplugin-view-data', // Menu slug
        'myplugin_view_data_page', // Callback function
        'dashicons-admin-plugins', // Icon (optional)
        30 // Position (optional)
    );

    add_submenu_page(
        'myplugin-view-data', // Parent slug
        'Add Person',       // Page title
        'Add Person',       // Menu title
        'manage_options',   // Capability
        'myplugin-add-person', // Menu slug
        'myplugin_add_person_page' // Callback function
    );
}

add_action('admin_menu', 'myplugin_add_menu');

function myplugin_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'myplugin_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name varchar(255) NOT NULL,
        last_name varchar(255) NOT NULL,
        age int NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'myplugin_create_table');

function myplugin_add_person_page() {
    echo do_shortcode('[myplugin_form]');
}

function myplugin_view_data_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'myplugin_data';

    // Fetch data from the database
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h1>View Data</h1>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th id="columnname" class="manage-column column-columnname" scope="col">ID</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">First Name</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Last Name</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Age</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)) {
                    foreach ($results as $row) { ?>
                        <tr class="alternate">
                            <td class="column-columnname"><?php echo esc_html($row->id); ?></td>
                            <td class="column-columnname"><?php echo esc_html($row->first_name); ?></td>
                            <td class="column-columnname"><?php echo esc_html($row->last_name); ?></td>
                            <td class="column-columnname"><?php echo esc_html($row->age); ?></td>
                            <td class="column-columnname">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo esc_attr($row->id); ?>" />
                                    <input type="submit" name="delete" value="Delete" class="button button-secondary" />
                                </form>
                                <a href="<?php echo admin_url('admin.php?page=myplugin-add-person&edit_id=' . esc_attr($row->id)); ?>" class="button button-secondary">Edit</a>
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
            <a href="<?php echo admin_url('admin.php?page=myplugin-add-person'); ?>" class="button button-primary">Add Person</a>
        </p>
    </div>
    <?php

    // Handle deletion
    if (isset($_POST['delete'])) {
        $delete_id = intval($_POST['delete_id']);
        $wpdb->delete($table_name, ['id' => $delete_id]);
        echo "<script>location.replace('" . admin_url('admin.php?page=myplugin-view-data') . "');</script>";
    }
}

function myplugin_form_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'myplugin_data';

    // Process form submission for adding and editing
    if (isset($_POST['submit'])) {
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $age = intval($_POST['age']);

        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $edit_id = intval($_POST['edit_id']);
            $wpdb->update(
                $table_name,
                [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'age' => $age
                ],
                ['id' => $edit_id]
            );
        } else {
            $wpdb->insert(
                $table_name,
                [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'age' => $age
                ]
            );
        }

        
    }

    // Check if we are editing
    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $first_name = '';
    $last_name = '';
    $age = '';

    if ($edit_id) {
        $person = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $edit_id");
        if ($person) {
            $first_name = $person->first_name;
            $last_name = $person->last_name;
            $age = $person->age;
        }
    }

    ob_start();
    ?>
    <div class="wrap">
        <h1><?php echo $edit_id ? 'Edit Person' : 'Add Person'; ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="first_name">First Name</label></th>
                    <td><input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" class="regular-text" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="last_name">Last Name</label></th>
                    <td><input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" class="regular-text" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="age">Age</label></th>
                    <td><input type="number" id="age" name="age" value="<?php echo esc_attr($age); ?>" class="regular-text" required /></td>
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

add_shortcode('myplugin_form', 'myplugin_form_shortcode');
?>
