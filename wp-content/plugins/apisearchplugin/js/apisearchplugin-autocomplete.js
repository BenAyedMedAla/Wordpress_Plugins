jQuery(document).ready(function($) {
    // Ensure that the script works both in the front-end and admin area
    var isAdmin = typeof apisearchplugin_ajax !== 'undefined' && typeof apisearchplugin_ajax.is_admin !== 'undefined' && apisearchplugin_ajax.is_admin;

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
