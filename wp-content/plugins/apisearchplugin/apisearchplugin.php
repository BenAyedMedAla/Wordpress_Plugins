<?php

/*
Plugin Name: API Search Plugin
Description: Un plugin pour rechercher et afficher des résidences.
Version: 1.0
Author: alabenayed
*/



// Ajouter la page d'administration du plugin

function apisearchplugin_add_admin_page() {
    add_menu_page(
        'API Search Plugin',                 // Titre de la page
        'API Search Plugin',                 // Titre du menu
        'manage_options',                    // Capacité
        'apisearchplugin',                   // Slug du menu
        'apisearchplugin_render_admin_page', // Fonction de callback
        'dashicons-search',                  // Icône du menu
        6                                    // Position du menu
    );
}

add_action('admin_menu', 'apisearchplugin_add_admin_page');



// Ajouter le shortcode

function apisearchplugin_shortcode() {

    ob_start();
    apisearchplugin_render_search_page();
    return ob_get_clean();

}

add_shortcode('apisearchplugin', 'apisearchplugin_shortcode');



// Fonction pour appeler l'API et récupérer les résidences

function call_api_and_get_residences() {
    // Les détails d'authentification et l'URL de l'API
   
    $auth_key = 'wordpress';
    $auth_secret = 'f4ae4d1a35cf653bed2e78623cc1cfd0';
    $api_url = 'https://admin.arpej.fr/api/wordpress/residences/';

    // Configurer la requête vers l'API
   
    $args = array(
        'headers' => array(
            'X-Auth-Key' => $auth_key,
            'X-Auth-Secret' => $auth_secret,
        ),
    );

    // Faire la requête à l'API
    $response = wp_remote_get($api_url, $args);

    // Vérifier si la requête a réussi
   
    if (is_wp_error($response)) {
        return null; // Gérer les erreurs ici si nécessaire
    }

    // Décoder la réponse JSON
   
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    // Vérifier si les données sont valides
   
    if (empty($data) || !is_array($data)) {
        return null;
    }

    // Retourner les données décodées
    return $data;
}



// Fonction pour afficher la barre de recherche et traiter la recherche

function apisearchplugin_render_search_bar() {
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    echo '<form method="GET" id="apisearchplugin-search-form" class="apisearchplugin-search-bar">';
    echo '<input type="hidden" name="page" value="apisearchplugin">';
    echo '<input type="text" name="s" value="' . esc_attr($search_query) . '" placeholder="Rechercher une résidence...">';
    echo '<input type="submit" value="Rechercher">';
    echo '</form>';

    // Container for autocomplete suggestions
    echo '<div id="autocomplete-container"></div>';

    return $search_query;
}





// Fonction pour afficher les résidences en format carte

function apisearchplugin_display_residences($search_query = '') {

    $residences = call_api_and_get_residences();

    if (!$residences || !is_array($residences)) {    //!is_array vérifier si une variable n'est pas un tableau

        echo '<p>Aucune résidence trouvée.</p>';
        return;

    }

    echo '<div class="apisearchplugin-cards-container">';

    foreach ($residences as $residence) {

        if ($search_query && stripos($residence->title, $search_query) === false && stripos($residence->city, $search_query) === false) {  //vérifier si une sous-chaîne n'est pas présente dans une chaîne de caractères, sans tenir compte de la casse (majuscule ou minuscule)

            continue;

        }

        $title = esc_html($residence->title); //convertit les caractères spéciaux en leurs équivalents HTML
        $address = esc_html($residence->address . ', ' . $residence->zip_code . ' ' . $residence->city);
        $price = isset($residence->preview->rent_amount_from) ? esc_html($residence->preview->rent_amount_from) : 'Prix non disponible';
        $picture_url = !empty($residence->pictures) ? esc_url($residence->pictures[0]->url) : 'https://via.placeholder.com/150';

        $details_url = add_query_arg('residence_id', $residence->id, admin_url('admin.php?page=apisearchplugin')); //Syntaxe :add_query_arg($key, $value, $url); construire une URL avec des paramètres de requête


        echo '<div class="apisearchplugin-card">';
         
            echo '<img src="' . $picture_url . '" alt="' . $title . '" class="apisearchplugin-card-img">';
         
            echo '<div class="apisearchplugin-card-body">';
       
                 echo '<h3 class="apisearchplugin-card-title">' . $title . '</h3>';
        
                 echo '<p class="apisearchplugin-card-address">' . $address . '</p>';
        
                 echo '<p class="apisearchplugin-card-price">' . $price . ' €</p>';
        
                 echo '<a href="' . esc_url($details_url) . '" class="apisearchplugin-card-button" data-id="' . esc_attr($residence->id) . '">Voir les détails</a>';
        
             echo '</div>';
        echo '</div>';
    }

    echo '</div>';

}



// Fonction pour afficher les détails d'une résidence

function apisearchplugin_display_residence_details($residence_id) {
    $residences = call_api_and_get_residences();
    $residence = null;

    foreach ($residences as $res) {
 
        if ($res->id == $residence_id) {
            $residence = $res;
            break;
 
        }
    }

    if (!$residence) {
 
        echo '<p>Résidence non trouvée.</p>';
        return;
 
    }

    $title = esc_html($residence->title);
    $address = esc_html($residence->address . ', ' . $residence->zip_code . ' ' . $residence->city);
    $picture_url = !empty($residence->pictures) ? esc_url($residence->pictures[0]->url) : 'https://via.placeholder.com/150';

    echo '<div class="apisearchplugin-details">';
    
        echo '<img src="' . $picture_url . '" alt="' . $title . '">';
        echo '<h2>' . $title . '</h2>';
        echo '<p>' . $address . '</p>';
        echo '<h3>Offres</h3>';
        echo '<ul class="apisearchplugin-details-offers">';
 
        foreach ($residence->offers as $offer) {
 
            echo '<li>' . esc_html($offer->optional_comment_equipped) . '</li>';
 
          }
 
        echo '</ul>';

        echo '<h3>Aperçu</h3>';
        echo '<p>Surface: ' . esc_html($residence->preview->surface_from) . ' - ' . esc_html($residence->preview->surface_to) . ' m²</p>';
        echo '<p>Loyer à partir de: ' . esc_html($residence->preview->rent_amount_from) . ' €</p>';
        echo '<p>Nombre de logements: ' . esc_html($residence->preview->quantity) . '</p>';

        echo '<h3>Services</h3>';
        echo '<ul class="apisearchplugin-details-services">';
 
        foreach ($residence->preview->residence_services as $service) {
 
            echo '<li>' . esc_html($service->title) . ': ' . esc_html($service->description) . ' (' . esc_html($service->price) . ')</li>';
 
        }
 
        echo '</ul>';

    echo '</div>';

}


// Fonction pour rendre la page d'administration du plugin

function apisearchplugin_render_admin_page() {

    echo '<div class="wrap">';
         echo '<h1>API Search Plugin</h1>';

        // Récupérer l'ID de la résidence depuis les paramètres de l'URL
         $residence_id = isset($_GET['residence_id']) ? intval($_GET['residence_id']) : 0;

         if ($residence_id) {

             // Afficher les détails de la résidence
             apisearchplugin_display_residence_details($residence_id);

        } else {

             // Afficher la barre de recherche et la liste des résidences
             $search_query = apisearchplugin_render_search_bar();
             apisearchplugin_display_residences($search_query);
    
        }
    echo '</div>';
}


// Fonction pour rendre la page de recherche

function apisearchplugin_render_search_page() {

    echo '<div id="apisearchplugin">';
       
        $search_query = apisearchplugin_render_search_bar();
        apisearchplugin_display_residences($search_query);
    
    echo '</div>';

}

function apisearchplugin_admin_enqueue_scripts() {
    // Enqueue the new JS file for autocomplete
    wp_enqueue_script('apisearchplugin-autocomplete', plugin_dir_url(__FILE__) . 'apisearchplugin-autocomplete.js', array('jquery'), null, true);
    
    // Localize the script for AJAX
    wp_localize_script('apisearchplugin-autocomplete', 'apisearchplugin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('apisearchplugin_nonce'),
        'is_admin' => true // Set a flag to indicate admin area
    ));
    
}

add_action('admin_enqueue_scripts', 'apisearchplugin_admin_enqueue_scripts');

// Enqueue the JavaScript file for AJAX

function apisearchplugin_enqueue_scripts() {
    wp_enqueue_script('apisearchplugin-ajax', plugin_dir_url(__FILE__) . 'apisearchplugin.js', array('jquery'), null, true);
    wp_localize_script('apisearchplugin-ajax', 'apisearchplugin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('apisearchplugin_nonce')
    ));

    // Enqueue front-end styles
    wp_enqueue_style('apisearchplugin-styles', plugin_dir_url(__FILE__) . 'front_apisearchplugin.css');
}
add_action('wp_enqueue_scripts', 'apisearchplugin_enqueue_scripts');




function apisearchplugin_autocomplete_residences() {
    check_ajax_referer('apisearchplugin_nonce', 'nonce');

    $search_query = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
    $residences = call_api_and_get_residences();
    $suggestions = array();

    if ($residences && is_array($residences)) {
        foreach ($residences as $residence) {
            if (stripos($residence->title, $search_query) !== false || stripos($residence->city, $search_query) !== false) {
                $suggestions[] = esc_html($residence->title);
            }
        }
    }

    wp_send_json_success($suggestions);
}

add_action('wp_ajax_autocomplete_residences', 'apisearchplugin_autocomplete_residences');
add_action('wp_ajax_nopriv_autocomplete_residences', 'apisearchplugin_autocomplete_residences');



// AJAX function to search for residences
function apisearchplugin_ajax_search_residences() {
    check_ajax_referer('apisearchplugin_nonce', 'nonce');
    $search_query = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
    $residences = call_api_and_get_residences();
    if (!$residences || !is_array($residences)) {
        wp_send_json_error('No residences found.');
        return;
    }
    ob_start();
    foreach ($residences as $residence) {
        if ($search_query && stripos($residence->title, $search_query) === false && stripos($residence->city, $search_query) === false) {
            continue;
        }
        $title = esc_html($residence->title);
        $address = esc_html($residence->address . ', ' . $residence->zip_code . ' ' . $residence->city);
        $picture_url = !empty($residence->pictures) ? esc_url($residence->pictures[0]->url) : 'https://via.placeholder.com/150';
        echo '<div class="apisearchplugin-card">';
        echo '<img src="' . $picture_url . '" alt="' . $title . '" class="apisearchplugin-card-img">';
        echo '<div class="apisearchplugin-card-body">';
        echo '<h3 class="apisearchplugin-card-title">' . $title . '</h3>';
        echo '<p class="apisearchplugin-card-address">' . $address . '</p>';
        echo '<a href="#" class="apisearchplugin-card-button" data-id="' . esc_attr($residence->id) . '">Voir les détails</a>';
        echo '</div>';
        echo '</div>';
    }
    $output = ob_get_clean();
    wp_send_json_success($output);
}


add_action('wp_ajax_search_residences', 'apisearchplugin_ajax_search_residences');
add_action('wp_ajax_nopriv_search_residences', 'apisearchplugin_ajax_search_residences');


// AJAX function to get residence details

function apisearchplugin_ajax_residence_details() {

    check_ajax_referer('apisearchplugin_nonce', 'nonce');

    $residence_id = isset($_POST['residence_id']) ? intval($_POST['residence_id']) : 0;
    ob_start();
    apisearchplugin_display_residence_details($residence_id);
    $output = ob_get_clean();

    wp_send_json_success($output);
}

add_action('wp_ajax_residence_details', 'apisearchplugin_ajax_residence_details');
add_action('wp_ajax_nopriv_residence_details', 'apisearchplugin_ajax_residence_details');



// Enqueue admin styles

function apisearchplugin_add_styles() {

    wp_enqueue_style('apisearchplugin-admin-styles', plugin_dir_url(__FILE__) . 'front_apisearchplugin.css');

}

add_action('admin_enqueue_scripts', 'apisearchplugin_add_styles');



// Enqueue front-end styles

function api_searchplugin_add_styles() {

    wp_enqueue_style('apisearchplugin-styles', plugin_dir_url(__FILE__) . 'front_apisearchplugin.css');

}

add_action('wp_enqueue_scripts', 'api_searchplugin_add_styles');

?>