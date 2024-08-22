jQuery(document).ready(function($) {
    $('#chatbotplugin').hide();
    var $chatbox = $('#chatbotplugin-chatbox');
    
    // Function to append messages to chatbox
    function appendMessage(message, isBot = false) {
        var messageClass = isBot ? 'bot-message' : 'user-message';
        var $messageElement = $('<div>', {
            class: messageClass,
            text: message
        });
        $chatbox.append($messageElement);
        $chatbox.scrollTop($chatbox[0].scrollHeight); // Scroll to the bottom of the chatbox
    }

    $('#city').on('click', function() {
        appendMessage('Type the city name:',true);
        $('#chatbotplugin-input').attr('placeholder', 'City name...');
      });
    
      $('#name').on('click', function() {
        appendMessage('Type the residence name:',true);
        $('#chatbotplugin-input').attr('placeholder', 'Residence name...');
      });
    
      $('#budget').on('click', function() {
        appendMessage('Type the maximum of your budget:',true);
        $('#chatbotplugin-input').attr('placeholder', 'Maximum budget...');
      });
    $('#chatbotplugin-send').on('click', function() {
        var questionType = $('#chatbotplugin-buttons button.active').data('question');
        var query = $('#chatbotplugin-input').val();
        var nonce = chatbotplugin_ajax.nonce;

        if (query !== '') {
            appendMessage(query, false);

            $.ajax({
                url: chatbotplugin_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'chatbotplugin_request',
                    nonce: nonce,
                    question_type: questionType,
                    query: query
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data=="I can help with questions about residence cities, budget, or names please press one of the buttons ."){
                        appendMessage(response.data, true);}
                        else if (response.data.startsWith("No residences found")){
                            appendMessage(response.data, true);}
                        else{
                            $('#chatbotplugin-chatbox').append('<div class="chatbotplugin-response">' + response.data + '</div>');
                            appendMessage('Click "See More" to view the catalog of residences with their details', true);
                        }
                    } else {
                        appendMessage('Error processing your request.', true);
                    }
                    $('#chatbotplugin-input').val(''); // Clear the input field
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    appendMessage('AJAX Error: ' + textStatus, true);
                }
            });
        }
    });
    // $('#chatbotplugin-view-details-button').on('click', function() {
    //     appendMessage('press "See More" to see catalogue of residences with their details',true);
    // });
    //     window.location.href = 'http://localhost:8084/?page_id=39';
    // });
    // $('.chatbotplugin-view-details-button').attr("href", 'http://localhost:8084/?page_id=39')
    // Handle detail view link click within the chatbot
// $(document).on('click', '.chatbotplugin-view-details-button', function(e) {
//     e.preventDefault();

//     var residenceId = $(this).data('id');

//     $.ajax({
//         url: chatbotplugin_ajax.ajax_url,
//         type: 'POST',
//         data: {
//             action: 'residence_details',
//             residence_id: residenceId,
//             nonce: chatbotplugin_ajax.nonce
//         },
//         success: function(response) {
//             if (response.success) {
//                 $("#chatbotpage").html(response.data);
//             } else {
//                 alert('An error occurred.');
//             }
//         },
//         error: function(jqXHR, textStatus, errorThrown) {
//             console.log("AJAX Error:", textStatus, errorThrown);
//         }
//     });
// });


    $('#chatbotplugin-buttons button').on('click', function() {
        $('#chatbotplugin-buttons button').removeClass('active');
        $(this).addClass('active');
    });

    $('#chatbot-toggle-btn').on('click', function() {
        $('#chatbotplugin').show();
        $(this).hide();
    });

    $('#close-btn').on('click', function() {
        $('#chatbotplugin').hide();
        $('#chatbot-toggle-btn').show();
    });
});
