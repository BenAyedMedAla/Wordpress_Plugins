jQuery(document).ready(function($) {
    // Ensure that the script works both in the front-end and admin area
    var isAdmin = typeof apisearchplugin_ajax !== 'undefined' && typeof apisearchplugin_ajax.is_admin !== 'undefined' && apisearchplugin_ajax.is_admin;

    // Handle search form submission
    $('#apisearchplugin-search-form').on('submit', function(e) {
        e.preventDefault();
        
        var searchQuery = $(this).find('input[name="s"]').val();
        var nonce = apisearchplugin_ajax.nonce;

        $.ajax({
            url: apisearchplugin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'search_residences',
                s: searchQuery,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Use different selectors for admin and frontend if necessary
                    var targetElement = isAdmin ? '#apisearchplugin' : '#apisearchplugin';
                    $(targetElement).html(response.data);
                } else {
                    alert('Une erreur est survenue.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("AJAX Error:", textStatus, errorThrown);
            }
        });
    });

    // Handle detail view link click
    $(document).on('click', '.apisearchplugin-card-button', function(e) {
        e.preventDefault();
        
        var residenceId = $(this).data('id');
        var nonce = apisearchplugin_ajax.nonce;

        $.ajax({
            url: apisearchplugin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'residence_details',
                residence_id: residenceId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    var targetElement = isAdmin ? '#apisearchplugin' : '#apisearchplugin';
                    $(targetElement).html(response.data);
                } else {
                    alert('Une erreur est survenue.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("AJAX Error:", textStatus, errorThrown);
            }
        });
    });

    // Handle autocomplete
    $('#apisearchplugin-search-form input[name="s"]').on('input', function() {
        var searchQuery = $(this).val();
        var nonce = apisearchplugin_ajax.nonce;

        if (searchQuery.length >= 2) {
            $.ajax({
                url: apisearchplugin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'autocomplete_residences',
                    s: searchQuery,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Display autocomplete suggestions
                        var suggestions = response.data;
                        var suggestionsList = '<ul class="autocomplete-suggestions">';
                        suggestions.forEach(function(suggestion) {
                            suggestionsList += '<li>' + suggestion + '</li>';
                        });
                        suggestionsList += '</ul>';
                        $('#autocomplete-container').html(suggestionsList);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log("AJAX Error:", textStatus, errorThrown);
                }
            });
        } else {
            $('#autocomplete-container').html('');
        }
    });

    // Handle autocomplete suggestion click
    $(document).on('click', '.autocomplete-suggestions li', function() {
        var suggestion = $(this).text();
        $('#apisearchplugin-search-form input[name="s"]').val(suggestion);
        $('#autocomplete-container').html('');
    });
});
