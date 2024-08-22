<?php
/*
Plugin Name: Chatbot Plugin
Description: A chatbot plugin to interact with residence data.
Version: 1.0
Author: alabenayed
*/

function chatbotplugin_add_admin_page() {
    add_menu_page(
        'Chatbot Plugin',
        'Chatbot Plugin',
        'manage_options',
        'chatbotplugin',
        'chatbotplugin_render_admin_page',
        'dashicons-format-chat',
        6
    );
}
add_action('admin_menu', 'chatbotplugin_add_admin_page');

function chatbotplugin_shortcode() {
    ob_start();
    chatbotplugin_render_chatbot_interface();
    return ob_get_clean();
}
add_shortcode('chatbotplugin', 'chatbotplugin_shortcode');

function chatbotplugin_call_api() {
    $auth_key = 'wordpress';
    $auth_secret = 'f4ae4d1a35cf653bed2e78623cc1cfd0';
    $api_url = 'https://admin.arpej.fr/api/wordpress/residences/';
    
    $args = array(
        'headers' => array(
            'X-Auth-Key' => $auth_key,
            'X-Auth-Secret' => $auth_secret,
        ),
    );

    $response = wp_remote_get($api_url, $args);

    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (empty($data) || !is_array($data)) {
        return null;
    }

    return $data;
}

function chatbotplugin_render_chatbot_interface() { ?>
    <button id="chatbot-toggle-btn"><img src="<?php echo '' . plugin_dir_url(__FILE__) . 'sources/chatbot.png';?>" alt="Chatbot Icon" /> Chatbot </button>
     <div id="chatbotplugin">
     <div class="chat-header"><span>Chatbot</span><button id="close-btn">&times</button></div>
     <div id="chatbotplugin-chatbox"></div> 
     <div id="chatbotplugin-buttons">
     <button id="city" data-question="city" >City</button>
     <button id="budget" data-question="budget" >Budget</button>
     <button id="name" data-question="name" >Name</button>
     </div>
     <div class="chat-input">
     <input type="text" id="chatbotplugin-input" placeholder="Ask a question..." />
     <button id="chatbotplugin-send">Send</button>
     </div>
     <div class="copyright"><a href="https://www.thecodinghubs.com/" target="_blank">Search for residences</a></div>
     </div>
<?php }

function chatbotplugin_handle_question() {
    check_ajax_referer('chatbotplugin_nonce', 'nonce');
    
    $question_type = isset($_POST['question_type']) ? sanitize_text_field($_POST['question_type']) : '';
    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    $residences = chatbotplugin_call_api();
    $response = '';

    if ($question_type === 'city') {
        $response = "Type the name of the City :";
        $filtered_residences = array_filter($residences, function($residence) use ($query) {
            return stripos($residence->city, $query) !== false;
        });
        if (!empty($filtered_residences)) {
            ob_start();?>
            <div class="chatbotplugin-cards-container">
            <?php foreach ($filtered_residences as $residence) {
                $title = esc_html($residence->title);
                $address = esc_html($residence->address . ', ' . $residence->zip_code . ' ' . $residence->city);
                $price = isset($residence->preview->rent_amount_from) ? esc_html($residence->preview->rent_amount_from) : 'Prix non disponible';
                $picture_url = !empty($residence->pictures) ? esc_url($residence->pictures[0]->url) : 'https://via.placeholder.com/150'; ?>
                <div class="chatbotplugin-card">
                <img src="<?php echo '' . $picture_url . '';?>" alt="' . $title . '" class="chatbotplugin-card-img">
                <div class="chatbotplugin-card-body">
                <h3 class="chatbotplugin-card-title"><?php echo '' . $title . '';?></h3>
                <p class="chatbotplugin-card-address"><?php echo '' . $address . '';?></p>
                <p class="chatbotplugin-card-price"><?php echo '' . $price . '';?> €</p>
                <a href="http://localhost:8084/?page_id=39" id="chatbotplugin-view-details-button" class="chatbotplugin-view-details-button">See More</a>
                </div>
                </div>
            <?php } ?>
            </div>
            <?php
            $response = ob_get_clean();
        } else {
            $response = "No residences found in $query.";
        }
    } elseif ($question_type === 'budget') {
        $max_budget = floatval($query); // Convert query to float for comparison
        $filtered_residences = array_filter($residences, function($residence) use ($max_budget) {
            return isset($residence->preview->rent_amount_from) && $residence->preview->rent_amount_from <= $max_budget;
        });
        if (!empty($filtered_residences)) {
            ob_start();
            echo '<div class="chatbotplugin-cards-container">';
            foreach ($filtered_residences as $residence) {
                $title = esc_html($residence->title);
                $address = esc_html($residence->address . ', ' . $residence->zip_code . ' ' . $residence->city);
                $price = isset($residence->preview->rent_amount_from) ? esc_html($residence->preview->rent_amount_from) : 'Prix non disponible';
                $picture_url = !empty($residence->pictures) ? esc_url($residence->pictures[0]->url) : 'https://via.placeholder.com/150'; ?>

                <div class="chatbotplugin-card">
                <img src="<?php echo '' . $picture_url . '';?>" alt="' . $title . '" class="chatbotplugin-card-img">
                <div class="chatbotplugin-card-body">
                <h3 class="chatbotplugin-card-title"><?php echo '' . $title . '';?></h3>
                <p class="chatbotplugin-card-address"><?php echo '' . $address . '';?></p>
                <p class="chatbotplugin-card-price"><?php echo '' . $price . '';?> €</p>
                <a href="http://localhost:8084/?page_id=39" id="chatbotplugin-view-details-button" class="chatbotplugin-view-details-button">See More</a>
                </div>
                </div> <?php
            }?>
            </div> <?php
            $response = ob_get_clean();
        } else {
            $response = "No residences found within a budget of $query €.";
        }
    } elseif ($question_type === 'name') {
        $residence = array_filter($residences, function($residence) use ($query) {
            return stripos($residence->title, $query) !== false;
        });
        $residence = reset($residence); // Get the first match

        if ($residence) {
            ob_start();
            $title = esc_html($residence->title);
            $address = esc_html($residence->address . ', ' . $residence->zip_code . ' ' . $residence->city);
            $price = isset($residence->preview->rent_amount_from) ? esc_html($residence->preview->rent_amount_from) : 'Prix non disponible';
            $picture_url = !empty($residence->pictures) ? esc_url($residence->pictures[0]->url) : 'https://via.placeholder.com/150'; ?>

            <div class="chatbotplugin-card">
            <img src="<?php echo '' . $picture_url . '';?>" alt="' . $title . '" class="chatbotplugin-card-img">
            <div class="chatbotplugin-card-body">
            <h3 class="chatbotplugin-card-title"><?php echo '' . $title . '';?></h3>
            <p class="chatbotplugin-card-address"><?php echo '' . $address . '';?></p>
            <p class="chatbotplugin-card-price"><?php echo '' . $price . '';?> €</p>
            <a href="http://localhost:8084/?page_id=39" id="chatbotplugin-view-details-button" class="chatbotplugin-view-details-button">See More</a>
            </div>
            </div><?php
            $response = ob_get_clean();
        } else {
            $response = "No residence found with the name $query.";
        }
    } else {
        $response = 'I can help with questions about residence cities, budget, or names please press one of the buttons .';
    }

    wp_send_json_success($response);
}
add_action('wp_ajax_chatbotplugin_request', 'chatbotplugin_handle_question');
add_action('wp_ajax_nopriv_chatbotplugin_request', 'chatbotplugin_handle_question');

// function chatbotplugin_display_residence_details($residence_id) {
//    check_ajax_referer('chatbotplugin_nonce', 'nonce');
//     $residences = chatbotplugin_call_api(); // Replace this with the correct API call function
//     $residence = null;

//     foreach ($residences as $res) {
//         if ($res->id == $residence_id) {
//             $residence = $res;
//             break;
//         }
//     }

//     if (!$residence) {
//         echo '<p>Residence not found.</p>';
//         return;
//     }

//     $title = esc_html($residence->title);
//     $address = esc_html($residence->address . ', ' . $residence->zip_code . ' ' . $residence->city);
//     $picture_url = !empty($residence->pictures) ? esc_url($residence->pictures[0]->url) : 'https://via.placeholder.com/150';

//     echo '<div id ="chatbotplugin-details"class="chatbotplugin-details">';
//     echo '<img src="' . $picture_url . '" alt="' . $title . '">';
//     echo '<h2>' . $title . '</h2>';
//     echo '<p>' . $address . '</p>';
//     echo '<h3>Offres</h3>';
//         echo '<ul class="chatbotplugin-details-offers">';
 
//         foreach ($residence->offers as $offer) {
 
//             echo '<li>' . esc_html($offer->optional_comment_equipped) . '</li>';
 
//           }
 
//         echo '</ul>';

//         echo '<h3>Aperçu</h3>';
//         echo '<p>Surface: ' . esc_html($residence->preview->surface_from) . ' - ' . esc_html($residence->preview->surface_to) . ' m²</p>';
//         echo '<p>Loyer à partir de: ' . esc_html($residence->preview->rent_amount_from) . ' €</p>';
//         echo '<p>Nombre de logements: ' . esc_html($residence->preview->quantity) . '</p>';

//         echo '<h3>Services</h3>';
//         echo '<ul class="chatbotplugin-details-services">';
 
//         foreach ($residence->preview->residence_services as $service) {
 
//             echo '<li>' . esc_html($service->title) . ': ' . esc_html($service->description) . ' (' . esc_html($service->price) . ')</li>';
 
//         }
 
//         echo '</ul>';
//         echo '<button onclick="history.back()">Back to Chatbot</button>';


//     echo '</div>';
// }

// // AJAX function to get residence details

//   function chatbotplugin_ajax_residence_details() {

//       check_ajax_referer('chatbotplugin_nonce', 'nonce');
     
//       $residence_id = isset($_POST['residence_id']) ? intval($_POST['residence_id']) : 0;
//       ob_start();
//       chatbotplugin_display_residence_details($residence_id);
//       $output = ob_get_clean();

//       wp_send_json_success($output);
//   }
//  add_action('wp_ajax_residence_details', 'chatbotplugin_ajax_residence_details');
//  add_action('wp_ajax_nopriv_residence_details', 'chatbotplugin_ajax_residence_details');

 

function chatbotplugin_enqueue_scripts() {
    wp_enqueue_script('chatbotplugin-ajax', plugin_dir_url(__FILE__) . 'js/chatbotplugin.js', array('jquery'), null, true);
    
    wp_localize_script('chatbotplugin-ajax', 'chatbotplugin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('chatbotplugin_nonce')
    ));
    wp_enqueue_style('chatbotplugin-styles', plugin_dir_url(__FILE__) . 'css/chatbotplugin.css');

}

add_action('wp_enqueue_scripts', 'chatbotplugin_enqueue_scripts');

function chatbotplugin_enqueue_admin_scripts() {
    wp_enqueue_script('chatbotplugin-ajax', plugin_dir_url(__FILE__) . 'js/chatbotplugin.js', array('jquery'), null, true);
    
    wp_localize_script('chatbotplugin-ajax', 'chatbotplugin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('chatbotplugin_nonce'),
        'is_admin' => true
    ));
}

add_action('admin_enqueue_scripts', 'chatbotplugin_enqueue_admin_scripts');

function chatbotplugin_add_styles() {

    wp_enqueue_style('chatbotplugin-styles', plugin_dir_url(__FILE__) . 'css/chatbotplugin.css');

}

add_action('admin_enqueue_scripts', 'chatbotplugin_add_styles');


function chatbotplugin_render_admin_page() {?>
    <div class="wrap">
    <h1>Chatbot Plugin</h1> <?php
    $residence_id = isset($_GET['residence_id']) ? intval($_GET['residence_id']) : 0;
    if ($residence_id) {

        // Afficher les détails de la résidence
        chatbotplugin_display_residence_details($residence_id);

   } else {
        chatbotplugin_render_chatbot_interface();

   }?>
    </div>
<?php }
?>
