<?php 
/*
Plugin Name: Form Handler
Description: A plugin to handle form submissions
Version: 1.0
Author: Faruk Delić
*/

if(!defined('ABSPATH')){
    exit;
}

// Include the new class file at the top
require_once plugin_dir_path(__FILE__) . 'class-form-submissions-list-table.php';

// Function to create the custom table on plugin activation
function form_handler_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'client_form_data_custom';
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        submission_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        street varchar(255) NOT NULL,
        option1 tinyint(1) DEFAULT 0,
        option2 tinyint(1) DEFAULT 0,
        option3 tinyint(1) DEFAULT 0,
        is_holder tinyint(1) DEFAULT 0,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook the table creation function to plugin activation
register_activation_hook(__FILE__, 'form_handler_create_table');

// Enqueue necessary scripts and styles
function form_handler_enqueue_scripts() {
    wp_enqueue_style('form-handler-style', plugin_dir_url(__FILE__) . 'form-app-legacy/style.css');
    wp_enqueue_script('angular', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js', array(), null, true);
    wp_enqueue_script('jquery');
    wp_enqueue_style('toastr-css', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css');
    wp_enqueue_script('toastr-js', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js', array('jquery'), null, true);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    wp_enqueue_script('form-handler-app', plugin_dir_url(__FILE__) . 'form-app-legacy/app.js', array('angular'), null, true);

    // Localize the script with new data
    $script_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('form_handler_nonce'),
    );
    wp_localize_script('form-handler-app', 'form_handler_data', $script_data);
}
add_action('wp_enqueue_scripts', 'form_handler_enqueue_scripts');

// Function to handle form submission via AJAX
function form_handler_submit() {
    check_ajax_referer('form_handler_nonce', 'nonce');

    $clients = json_decode(stripslashes($_POST['clients']), true);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'client_form_data_custom';

    $success = true;
    $inserted_ids = array();

    foreach ($clients as $client) {
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($client['name']),
                'email' => sanitize_email($client['email']),
                'phone' => sanitize_text_field($client['phone']),
                'street' => sanitize_text_field($client['street']),
                'option1' => isset($client['option1']) && $client['option1'] ? 1 : 0,
                'option2' => isset($client['option2']) && $client['option2'] ? 1 : 0,
                'option3' => isset($client['option3']) && $client['option3'] ? 1 : 0,
                'is_holder' => isset($client['holder']) && $client['holder'] ? 1 : 0,
            )
        );

        if ($result === false) {
            $success = false;
            break;
        } else {
            $inserted_ids[] = $wpdb->insert_id;
        }
    }

    if ($success) {
        wp_send_json_success(array(
            'message' => 'Form data submitted successfully',
            'inserted_ids' => $inserted_ids
        ));
    } else {
        wp_send_json_error('Error submitting form data');
    }

    wp_die();
}
add_action('wp_ajax_form_handler_submit', 'form_handler_submit');
add_action('wp_ajax_nopriv_form_handler_submit', 'form_handler_submit');

// Add a shortcode to display the AngularJS form
function form_handler_shortcode() {
    ob_start();
    ?>
    <div ng-app="formApp">
        <main class="container">
            <div>
                <h1 class="baseH1">Client Contact Form</h1>

                <div class="form-container" ng-controller="formController">
                    <form class="base-form" ng-repeat="client in clients" ng-submit="submitForm(client)">
                        <div class="form-row">
                            <div class="form-column">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" ng-model="client.name" required>
                            </div>

                            <div class="form-column">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" ng-model="client.email" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-column">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" ng-model="client.phone" required>
                            </div>

                            <div class="form-column">
                                <label for="street">Street Address</label>
                                <input type="text" id="street" name="street" ng-model="client.street" required>
                            </div>
                        </div>

                        <div class="form-column options-wrapper">
                            <label for="options">Options</label>
                            <div>
                                <div class="checkbox-wrapper-parent">
                                    <div class="checkbox-wrapper-2">
                                        <input class="checkbox-toggler" type="checkbox" id="option1" name="option1"
                                            ng-model="client.option1">
                                    </div>
                                    <label for="option1">Option 1</label>
                                </div>

                                <div class="checkbox-wrapper-parent">
                                    <div class="checkbox-wrapper-2">
                                        <input class="checkbox-toggler" type="checkbox" id="option2" name="option2"
                                            ng-model="client.option2">
                                    </div>
                                    <label for="option2">Option 2</label>
                                </div>

                                <div class="checkbox-wrapper-parent">
                                    <div class="checkbox-wrapper-2">
                                        <input class="checkbox-toggler" type="checkbox" id="option3" name="option3"
                                            ng-model="client.option3">
                                    </div>
                                    <label for="option3">Option 3</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-column">
                            <div class="checkbox-wrapper-parent form-holder">
                                <div class="checkbox-wrapper-2 form-holder-flex">
                                    <input class="checkbox-toggler" type="checkbox" id="holder" name="holder"
                                        ng-model="client.holder" ng-checked="client.holder || ($index === 0)"
                                        ng-change="updateFormHolder($index)">
                                    <label for="holder">Form Holder</label>
                                </div>
                            </div>
                            <p class="form-desc">(Automatically selected as Form Holder)</p>
                        </div>

                        <div class="btn_wrapper">
                            <button class="btn btn_add" type="button" ng-click="addClient()">Add Another Client <i
                                    class="ml-2 fa-solid fa-plus"></i></button>
                            <button class="btn btn_submit" type="submit">Submit <i
                                    class="ml-2 fa-solid fa-paper-plane"></i></button>
                        </div>

                        <div>
                            <!-- Show "Remove" button only if there's more than 1 client -->
                            <button class="btn_remove" type="button" ng-if="$index > 0" ng-click="removeClient($index)">
                                <i class="ml-2 fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('client_contact_form', 'form_handler_shortcode');

// add menu item called "Form Submissions" to the admin menu
function form_handler_add_menu() {
    add_menu_page('Form Submissions', 'Form Submissions', 'manage_options', 'form-submissions', 'form_handler_submissions_page');
}
add_action('admin_menu', 'form_handler_add_menu');

// display the form submissions page
function form_handler_submissions_page() {
    $list_table = new Form_Submissions_List_Table();
    $list_table->prepare_items();

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Form Submissions</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
    $list_table->display();
    echo '</form>';
    echo '</div>';
}