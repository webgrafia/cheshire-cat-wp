jQuery(document).ready(function($) {
    $('#cheshire-chat-send').click(function() {
        var message = $('#cheshire-chat-input').val();
        $('#cheshire-chat-input').val(''); // Clear the input field

        $.ajax({
            url: cheshire_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'cheshire_plugin_ajax',
                message: message
            },
            success: function(response) {
                if (response.success) {
                    // Append the response to the chat messages
                    $('#cheshire-chat-messages').append('<p>Response: ' + response.data + '</p>');
                } else {
                    // Handle the error
                    $('#cheshire-chat-messages').append('<p>Error: ' + response.data + '</p>');
                }
            },
            error: function(error) {
                // Handle the error
                $('#cheshire-chat-messages').append('<p>Error: ' + error.statusText + '</p>');
            }
        });
    });
});