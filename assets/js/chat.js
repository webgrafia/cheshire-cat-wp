jQuery(document).ready(function($) {
    $('#cheshire-chat-send').click(function() {
        var message = $('#cheshire-chat-input').val();
        $('#cheshire-chat-input').val(''); // Clear the input field

        // Display the user's message
        $('#cheshire-chat-messages').append('<div class="user-message"><p>' + message + '</p></div>');

        // Display the loader
        $('#cheshire-chat-messages').append('<div class="loader" id="cheshire-loader"></div>');

        $.ajax({
            url: cheshire_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'cheshire_plugin_ajax',
                message: message
            },
            success: function(response) {
                console.log('AJAX Success:', response);
                // Remove the loader
                $('#cheshire-loader').remove();
                if (response.success) {
                    // Append the response to the chat messages
                    $('#cheshire-chat-messages').append('<div class="bot-message"><p>' + response.data + '</p></div>');
                } else {
                    // Handle the error
                    $('#cheshire-chat-messages').append('<div class="error-message"><p>Error: ' + response.data + '</p></div>');
                }
            },
            error: function(error) {
                console.error('AJAX Error:', error);
                // Remove the loader
                $('#cheshire-loader').remove();
                // Handle the error
                $('#cheshire-chat-messages').append('<div class="error-message"><p>Error: ' + error.statusText + '</p></div>');
            }
        });
    });
});