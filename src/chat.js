jQuery(document).ready(function($) {
    // Gestisce l'invio del messaggio
    jQuery('#cheshire-plugin-form').submit(function(event) {
        event.preventDefault(); // Interrompe l'invio del modulo
        // Mostra il loader
        jQuery('#loader-container').show();

        var message = jQuery('#message').val();
        // svuoto il campo input
        jQuery('#message').val('');

        jQuery('#cheshire-plugin-result').append(' <div class="message">\n' +
            '        <div class="message-author">Human</div>\n' +
            '        <div class="message-content">' + message + '</div>\n' +
            '    </div>');

        // Invia il messaggio tramite AJAX
        jQuery.ajax({
            url: ajaxurl, // URL della pagina AJAX di WordPress
            type: 'post',
            data: {
                action: 'cheshire_plugin_ajax', // Azione AJAX da eseguire
                message: message, // Valore del campo di testo
            },
            success: function(response) {
                // Nascondi il loader
                jQuery('#loader-container').hide();
                // il response è un json, di cui recupero il valore della chiave "content" dopo aver fatto il decode dei caratteri speciali
                var response = response.replace(/&quot;/g, '"');
                var response = decodeURIComponent(response);

             //   console.log(response);

                var content = JSON.parse(response).content;

                // Visualizza la risposta nel div di risultato
                jQuery('#cheshire-plugin-result').append(' <div class="message">\n' +
                    '        <div class="message-author">Bot</div>\n' +
                    '        <div class="message-content">' + content + '</div>\n' +
                    '    </div>');
            },
            error: function(xhr, status, error) {
                console.log(error);

                // Nascondi il loader
                jQuery('#loader-container').hide();
            },
            beforeSend: function() {
                // Mostra il loader prima dell'invio della richiesta AJAX
                jQuery('#loader-container').show();
            },
        });
    });
});
