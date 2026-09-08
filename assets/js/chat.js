/**
 * Cheshire Cat Chatbot - Frontend JavaScript
 *
 * Handles the chat interface functionality including sending messages,
 * receiving responses, and updating the UI.
 */
jQuery(document).ready(function ($) {
    'use strict';

    /**
     * Scroll the chat window to the bottom to show the latest messages.
     */
    function scrollToBottom() {
        var chatMessages = $('#cheshire-chat-messages');
        chatMessages.scrollTop(chatMessages.prop('scrollHeight'));
    }

    /**
     * Get a cookie value by name.
     *
     * @param {string} name - The name of the cookie to retrieve
     * @return {string|null} The cookie value or null if not found
     */
    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        if (match) return match[2];
        return null;
    }

    /**
     * Get context information about the current page using an AJAX call to the server.
     * This uses the PHP get_context_information() method directly instead of reimplementing it in JavaScript.
     *
     * @param {function} callback - Function to call with the context information when the AJAX call completes
     * @return {void}
     */
    function getContextInformation(callback) {
        $.ajax({
            url: cheshire_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'cheshire_get_context_information',
                nonce: cheshire_ajax_object.nonce,
                page_id: cheshire_ajax_object.page_id || '',
                page_url: window.location.href
            },
            success: function (response) {
                if (response.success) {
                    callback(response.data);
                } else {
                    console.error('Error getting context information:', response);
                    // Return an empty context if there's an error
                    callback('');
                }
            },
            error: function (error) {
                console.error('AJAX error when getting context information:', error);
                // Return an empty context if there's an error
                callback('');
            }
        });
    }

    /**
     * Safely encode HTML entities to prevent XSS attacks.
     *
     * @param {string} text - The text to encode
     * @return {string} The encoded text
     */
    function encodeHTML(text) {
        return $('<div>').text(text).html();
    }

    /**
     * Sanitize HTML to allow certain safe tags while removing potentially dangerous ones.
     *
     * @param {string} html - The HTML to sanitize
     * @return {string} The sanitized HTML
     */
    function sanitizeHTML(html) {
        if (!html) {
            return '';
        }

        // Create a new div element
        var tempDiv = $('<div></div>');

        // Set the HTML content
        tempDiv.html(html);

        // Remove potentially dangerous tags and attributes
        tempDiv.find('script, iframe, object, embed, style').remove();

        // Remove dangerous attributes from all elements
        tempDiv.find('*').each(function () {
            var element = $(this);
            var attrs = element[0].attributes;
            var attrsToRemove = [];

            // Collect attributes to remove
            for (var i = 0; i < attrs.length; i++) {
                var attrName = attrs[i].name.toLowerCase();
                if (attrName.indexOf('on') === 0 || // event handlers
                    attrName === 'href' && /^\s*javascript:/i.test(attrs[i].value) || // javascript: URLs
                    attrName === 'src' && /^\s*javascript:/i.test(attrs[i].value) || // javascript: URLs
                    attrName === 'formaction' || // form action override
                    attrName === 'xlink:href') { // SVG xlink:href can be used for JavaScript execution
                    attrsToRemove.push(attrName);
                }
            }

            // Remove the collected attributes
            for (var j = 0; j < attrsToRemove.length; j++) {
                element.removeAttr(attrsToRemove[j]);
            }
        });

        // Get the sanitized HTML
        return tempDiv.html();
    }

    /**
     * Get related link HTML from declarative memory items
     *
     * @param {Object} data - The response data containing declarative memory items
     * @param {boolean} isWebSocket - Whether this is for WebSocket mode
     * @param {string} currentPostId - The ID of the current post (if in a post detail view)
     * @return {string} The HTML for the related links, or empty string if none found
     */
    function getRelatedLinkHtml(data, isWebSocket = false, currentPostId = null) {
        // console.log(currentPostId);
        // Check if related links are enabled
        if (!cheshire_ajax_object || cheshire_ajax_object.enable_related_links !== 'on') {
            return '';
        }

        // Get the declarative memory items - check both possible structures
        let declarativeItems = null;

        if (data.why && data.why.memory && data.why.memory.declarative &&
            data.why.memory.declarative.length > 0) {
            declarativeItems = data.why.memory.declarative;
        }

        // If no declarative items found, return empty string
        if (!declarativeItems) {
            return '';
        }

        // Get minimum score from settings
        const minimumScore = parseFloat(cheshire_ajax_object.minimum_link_score) || 0.8;

        // Find all WordPress items with score above minimum
        let relatedItems = [];

        for (let item of declarativeItems) {
            // Skip items that have the same ID as the current post
            if (currentPostId && item.metadata && item.metadata.wp_id &&
                item.metadata.wp_id === currentPostId) {
                continue;
            }

            if (item.metadata &&
                item.metadata.origin === "WordPress" &&
                item.metadata.url &&
                item.metadata.title &&
                item.score >= minimumScore) {
                relatedItems.push(item);
            }
        }

        // If we found suitable items, create links with their URLs and titles
        if (relatedItems.length > 0) {
            // For WebSocket mode, use a simpler format
            if (isWebSocket) {
                const linkText = cheshire_ajax_object.link_text || 'Related links';
                let html = `<br><br><div class="cheshire-related-links" data-title="${linkText}">`;

                for (let item of relatedItems) {
                    html += `<span class="cheshire-related-links-tag"><a href="${item.metadata.url}" >${item.metadata.title}</a></span>`;
                }

                html += '</div>';
                return html;
            } else {
                // For regular mode, use the same format
                const linkText = cheshire_ajax_object.link_text || 'Related links';
                let html = `<br><br><div class="cheshire-related-links" data-title="${linkText}">`;

                for (let item of relatedItems) {
                    html += `<span class="cheshire-related-links-tag"><a href="${item.metadata.url}" >${item.metadata.title}</a></span>`;
                }

                html += '</div>';
                return html;
            }
        }

        return '';
    }

    /**
     * Extract content from the API response.
     *
     * @param {Object|string} data - The response data
     * @return {string} The extracted content
     */
    function extractContent(data) {
        var content = '';

        if (!data) {
            return '';
        }

        if (typeof data === 'object') {
            if (data.text) {
                content = data.text;
            }

            //TODO: WARNING: tenere commentata in produzione, molto pericolosa perchè espone il token
            //console.log('cheshire_ajax_object', cheshire_ajax_object);

            // Add related link HTML if available
            const relatedLinkHtml = getRelatedLinkHtml(data, false, cheshire_ajax_object.page_id);
            if (relatedLinkHtml) {
                content += relatedLinkHtml;
            }
        } else {
            content = data;
        }

        // Handle code blocks with backticks
        content = content.replace(/```(\w*)\n([\s\S]*?)\n```/g, function (match, language, code) {
            return '<pre><code class="language-' + language + '">' + code + '</code></pre>';
        });

        // Handle markdown-style formatting if not already in HTML
        if (content.indexOf('<strong>') === -1 && content.indexOf('<em>') === -1) {
            // Bold text with double asterisks or double underscores
            content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            content = content.replace(/__(.*?)__/g, '<strong>$1</strong>');

            // Italic text with single asterisk or single underscore
            content = content.replace(/\*(.*?)\*/g, '<em>$1</em>');
            content = content.replace(/_(.*?)_/g, '<em>$1</em>');
        }

        return content;
    }

    /**
     * Store chat messages in localStorage.
     *
     * @param {Array} messages - Array of message objects to store
     */
    function storeMessages(messages) {
        localStorage.setItem('cheshire_chat_messages', JSON.stringify(messages));
    }

    /**
     * Get stored chat messages from localStorage.
     *
     * @return {Array} Array of message objects
     */
    function getStoredMessages() {
        var messages = localStorage.getItem('cheshire_chat_messages');
        return messages ? JSON.parse(messages) : [];
    }

    /**
     * Display a message in the chat window.
     *
     * @param {string} message - The message to display
     * @param {string} type - The type of message ('user', 'bot', or 'error')
     * @param {boolean} store - Whether to store the message (default: true)
     */
    function displayMessage(message, type, store = true) {
        var cssClass = '';
        var processedMessage = '';

        switch (type) {
            case 'user':
                cssClass = 'user-message';
                processedMessage = encodeHTML(message);
                break;
            case 'bot':
                cssClass = 'bot-message';
                // For bot messages, sanitize HTML instead of completely encoding it
                processedMessage = sanitizeHTML(message);
                // Convert line breaks to <br> tags for proper display
                processedMessage = processedMessage.replace(/\n/g, '<br>');
                break;
            case 'error':
                cssClass = 'error-message';
                processedMessage = encodeHTML('Error: ' + message);
                break;
            default:
                cssClass = 'bot-message';
                processedMessage = sanitizeHTML(message);
                processedMessage = processedMessage.replace(/\n/g, '<br>');
        }

        $('#cheshire-chat-messages').append(
            '<div class="' + cssClass + '"><p>' + processedMessage + '</p></div>'
        );

        // Store the message in localStorage if requested
        if (store) {
            var messages = getStoredMessages();
            messages.push({
                message: message,
                type: type,
                timestamp: new Date().getTime()
            });
            storeMessages(messages);
        }

        scrollToBottom();
    }

    /**
     * Show the loading indicator.
     */
    function showLoader() {
        $('#cheshire-chat-messages').append('<div class="loader" id="cheshire-loader"></div>');
        scrollToBottom();
    }

    /**
     * Hide the loading indicator.
     */
    function hideLoader() {
        $('#cheshire-loader').remove();
    }

    /**
     * Variables for tracking streaming message state
     */
    var currentStreamingMessage = '';
    var streamingMessageElement = null;
    var isStreaming = false;
    var justFinishedStreaming = false;

    /**
     * Update the streaming message with new token content
     *
     * @param {string} content - The token content to append
     */
    function updateStreamingMessage(content) {
        // If this is the first token, create a new message element
        if (!isStreaming) {
            // Hide loading indicator
            hideLoader();

            // Create a new bot message
            $('#cheshire-chat-messages').append(
                '<div class="bot-message"><p></p></div>'
            );

            // Store reference to the message element
            streamingMessageElement = $('#cheshire-chat-messages .bot-message:last-child p');

            // Set streaming flag
            isStreaming = true;

            // Re-enable the send button and restore the original icon
            $('#cheshire-chat-send').prop('disabled', false).html('<i class="far fa-arrow-alt-circle-right"></i>');
        }

        // Append the new content to the current message
        currentStreamingMessage += content;

        // Update the message element with the current content
        // Sanitize the HTML and convert line breaks
        var processedMessage = sanitizeHTML(currentStreamingMessage);
        processedMessage = processedMessage.replace(/\n/g, '<br>');
        streamingMessageElement.html(processedMessage);

        // Scroll to bottom to show the updated message
        scrollToBottom();
    }

    /**
     * Finalize the streaming message when complete
     */
    function finalizeStreamingMessage() {
        if (isStreaming) {
            // Store the complete message in localStorage
            var messages = getStoredMessages();
            messages.push({
                message: currentStreamingMessage,
                type: 'bot',
                timestamp: new Date().getTime()
            });
            storeMessages(messages);

            // Reset streaming state
            currentStreamingMessage = '';
            streamingMessageElement = null;
            isStreaming = false;

            // Set flag to indicate we just finished streaming
            justFinishedStreaming = true;

            // Reset the flag after a short delay to handle any future messages correctly
            // setTimeout(function() {
            //     justFinishedStreaming = false;
            // }, 1000);
        }
    }

    /**
     * WebSocket connection instance
     */
    var websocket = null;

    /**
     * Close WebSocket connection if it exists
     */
    function closeWebSocket() {
        if (websocket && websocket.readyState === WebSocket.OPEN) {
            websocket.close();
            websocket = null;
            console.log('WebSocket connection closed by user');
        }
    }

    /**
     * Initialize WebSocket connection
     */
    function initWebSocket() {
        // Check if WebSocket is enabled, URL is provided, and chatbot is enabled on this page
        if (cheshire_ajax_object.enable_websocket !== 'on' ||
            !cheshire_ajax_object.cheshire_url ||
            !cheshire_ajax_object.is_chatbot_enabled ||
            cheshire_ajax_object.is_preview) {
            return false;
        }

        var wsUrl;

        // Use custom WebSocket URL if provided, otherwise convert HTTP URL to WebSocket URL
        if (cheshire_ajax_object.websocket_url) {
            wsUrl = cheshire_ajax_object.websocket_url;
        } else {
            // Convert HTTP URL to WebSocket URL
            wsUrl = cheshire_ajax_object.cheshire_url.replace(/^http/, 'ws');
        }

        // Make sure the URL ends with a slash
        if (!wsUrl.endsWith('/')) {
            wsUrl += '/';
        }

        // Add the WebSocket endpoint
        wsUrl += 'ws';

        // Add authentication query parameter based on the Cat version:
        // - v1 uses ?token=  (Bearer token)
        // - v2 uses ?apiKey= (API Key)
        if (cheshire_ajax_object.cat_version === 'v2' && cheshire_ajax_object.api_key) {
            wsUrl += '?apiKey=' + encodeURIComponent(cheshire_ajax_object.api_key);
        } else if (cheshire_ajax_object.token) {
            wsUrl += '?token=' + encodeURIComponent(cheshire_ajax_object.token);
        }

        try {
            // Create WebSocket connection
            websocket = new WebSocket(wsUrl);

            // Connection opened
            websocket.onopen = function (event) {
                console.log('WebSocket connection established');
            };

            // Listen for messages
            websocket.onmessage = function (event) {
                try {
                    var response = JSON.parse(event.data);
                    // Check if this is a token message
                    if (response.type === 'chat_token') {

                        // This is a token with content, update the streaming message
                        updateStreamingMessage(response.content.replace(/\n/g, '<br>'));
                        return;
                    }

                    // If we get here, this is not a token message
                    // If we were streaming, finalize the previous message
                    if (isStreaming) {

                        if (response.type === 'chat') {
                            //  console.log(response);
                            // console.log(cheshire_ajax_object);
                            // Add related link HTML if available
                            const relatedLinkHtml = getRelatedLinkHtml(response, true, cheshire_ajax_object.page_id);
                            if (relatedLinkHtml) {
                                updateStreamingMessage(relatedLinkHtml);
                            }
                            finalizeStreamingMessage();
                        }
                    }

                    // Hide loading indicator
                    hideLoader();

                    // Re-enable the send button and restore the original icon
                    $('#cheshire-chat-send').prop('disabled', false).html('<i class="far fa-arrow-alt-circle-right"></i>');

                    // Only display the complete message if we haven't just finished streaming
                    // This prevents duplicate messages when the server sends both tokens and a complete message
                    if (!justFinishedStreaming) {
                        // Extract and display the content for non-token messages
                        var content = extractContent(response);
                        displayMessage(content, 'bot');
                    }
                } catch (e) {
                    console.error('Error parsing WebSocket message:', e);
                    displayMessage('Error processing response', 'error');

                    // Reset streaming state on error
                    if (isStreaming) {
                        finalizeStreamingMessage();
                    }
                }
            };

            // Connection closed
            websocket.onclose = function (event) {
                console.log('WebSocket connection closed');

                // Clean up streaming state if we were in the middle of streaming
                if (isStreaming) {
                    finalizeStreamingMessage();
                }

                websocket = null;
            };

            // Connection error
            websocket.onerror = function (error) {
                console.error('WebSocket error:', error);
                displayMessage('WebSocket connection error', 'error');

                // Clean up streaming state if we were in the middle of streaming
                if (isStreaming) {
                    finalizeStreamingMessage();
                }

                websocket = null;
            };

            return true;
        } catch (e) {
            console.error('Error creating WebSocket:', e);
            return false;
        }
    }

    /**
     * Send a message to the Cheshire Cat API and handle the response.
     */
    function sendMessage() {
        var message = $('#cheshire-chat-input').val();

        // Don't send empty messages or if in preview mode
        if (message.trim() === '' || cheshire_ajax_object.is_preview) {
            return;
        }

        // Get the send button reference
        var sendButton = $('#cheshire-chat-send');

        // Disable the send button and change the icon to spinning
        sendButton.prop('disabled', true);
        sendButton.html('<i class="fas fa-spinner fa-spin"></i>');

        // Clear the input field
        $('#cheshire-chat-input').val('');

        // Display the user's message
        displayMessage(message, 'user');

        // Show loading indicator
        showLoader();

        // Check if we should use WebSocket (only for regular chat, not for editor or prompt tester)
        if (cheshire_ajax_object.enable_websocket === 'on' && websocket && websocket.readyState === WebSocket.OPEN) {
            // Get user ID - try to use the same logic as in PHP
            var userId = 'wp';

            // Check if there's a cookie for user ID
            var userIdCookie = getCookie('cheshire_cat_user_id');
            if (userIdCookie) {
                userId = userIdCookie;
            }

            // Function to prepare and send the WebSocket message
            function prepareAndSendWebSocketMessage(messageText, contextInfo) {
                // Start with the original message
                var modifiedMessage = messageText;

                // Add context information if provided
                if (contextInfo) {
                    modifiedMessage += "\n\n" + contextInfo;
                }

                // Check if reinforcement message is enabled
                var enableReinforcement = cheshire_ajax_object.enable_reinforcement || 'off';

                // Append reinforcement message to the message if enabled
                if (enableReinforcement === 'on') {
                    var reinforcementMessage = cheshire_ajax_object.reinforcement_message || '';
                    if (reinforcementMessage) {
                        modifiedMessage += "\n\n#IMPORTANT\n" + reinforcementMessage + "\n";
                    }
                }

                // Prepare the message payload with only the essential information
                var payload = {
                    text: modifiedMessage,
                    user_id: userId
                };

                // Send the message via WebSocket
                websocket.send(JSON.stringify(payload));
            }

            var enableContext = cheshire_ajax_object.enable_context || 'off';

            // If context is enabled, get it via AJAX and then send the message
            if (enableContext === 'on') {
                getContextInformation(function (contextInfo) {
                    prepareAndSendWebSocketMessage(message, contextInfo);
                });
            } else {
                // If context is not enabled, just send the message without context
                prepareAndSendWebSocketMessage(message, null);
            }



        } else {
            // Fallback to AJAX if WebSocket is not available or not enabled
            $.ajax({
                url: cheshire_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'cheshire_plugin_ajax',
                    message: message,
                    nonce: cheshire_ajax_object.nonce,
                    page_id: cheshire_ajax_object.page_id || '',
                    page_url: window.location.href
                },
                success: function (response) {
                    // Hide loading indicator
                    hideLoader();

                    // Re-enable the send button and restore the original icon
                    sendButton.prop('disabled', false);
                    sendButton.html('<i class="far fa-arrow-alt-circle-right"></i>');

                    if (response.success) {
                        // Extract and display the content
                        var content = extractContent(response.data);
                        displayMessage(content, 'bot');
                    } else {
                        // Handle error response
                        displayMessage(response.data || 'Unknown error', 'error');
                    }
                },
                error: function (error) {
                    // Hide loading indicator
                    hideLoader();

                    // Re-enable the send button and restore the original icon
                    sendButton.prop('disabled', false);
                    sendButton.html('<i class="far fa-arrow-alt-circle-right"></i>');

                    // Handle AJAX error
                    displayMessage(error.statusText || 'Connection error', 'error');
                }
            });
        }
    }

    /**
     * Load stored messages from localStorage and display them in the chat.
     */
    function loadStoredMessages() {
        var messages = getStoredMessages();

        // Clear existing welcome message if we have stored messages
        if (messages.length > 0) {
            $('#cheshire-chat-messages').empty();
        }

        // Display each stored message
        messages.forEach(function (msgObj) {
            // Use store=false to avoid re-storing the messages
            displayMessage(msgObj.message, msgObj.type, false);
        });
    }

    /**
     * Clear chat history from localStorage and reset the chat window.
     */
    function clearChatHistory() {
        // Clear localStorage
        localStorage.removeItem('cheshire_chat_messages');

        // Clear chat window
        $('#cheshire-chat-messages').empty();

        // Display welcome message again
        $.ajax({
            url: cheshire_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'cheshire_get_welcome_message',
                nonce: cheshire_ajax_object.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#cheshire-chat-messages').html(response.data);
                } else {
                    // If AJAX fails, add a default welcome message
                    $('#cheshire-chat-messages').html('<div class="bot-message"><p>Hello! How can I help you?</p></div>');
                }
            },
            error: function () {
                // If AJAX fails, add a default welcome message
                $('#cheshire-chat-messages').html('<div class="bot-message"><p>Hello! How can I help you?</p></div>');
            }
        });
    }

    /**
     * Display predefined responses as clickable tags.
     */
    function displayPredefinedResponses() {
        if (cheshire_ajax_object.is_preview) {
            return;
        }

        // Check if predefined responses container exists, if not create it
        if ($('#cheshire-predefined-responses').length === 0) {
            $('#cheshire-chat-input-container').before('<div id="cheshire-predefined-responses"></div>');
        }

        // Get predefined responses
        $.ajax({
            url: cheshire_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'cheshire_get_predefined_responses',
                nonce: cheshire_ajax_object.nonce,
                page_id: cheshire_ajax_object.page_id || '',
                is_product_category: cheshire_ajax_object.is_product_category || false,
                product_category_id: cheshire_ajax_object.product_category_id || 0
            },
            success: function (response) {
                if (response.success && response.data) {
                    var responses = response.data;
                    var tagsHtml = '';

                    // Create a tag for each predefined response
                    responses.forEach(function (response) {
                        tagsHtml += '<span class="predefined-response-tag">' + encodeHTML(response) + '</span>';
                    });

                    // Add the tags to the container
                    $('#cheshire-predefined-responses').html(tagsHtml);
                }
            }
        });
    }

    /**
     * Handle click on predefined response tag.
     */
    $(document).on('click', '.predefined-response-tag', function () {
        var message = $(this).text();
        $('#cheshire-chat-input').val(message);

        // If this is a content response tag, make sure the chat is open
        if ($(this).hasClass('content-response-tag')) {
            // Open the chat if it's closed
            if ($('#cheshire-chat-container').hasClass('cheshire-chat-closed')) {
                $('#cheshire-chat-container').removeClass('cheshire-chat-closed').addClass('cheshire-chat-open');
                // Update localStorage
                localStorage.setItem('cheshire_chat_state', 'open');
                // Initialize WebSocket when chat is opened
                initWebSocket();
            }
        }

        sendMessage();
    });

    // Initialize the chat interface

    // Add icon to the send button
    $('#cheshire-chat-send').html('<i class="far fa-arrow-alt-circle-right"></i>');

    // Display predefined responses
    displayPredefinedResponses();

    // Set up event handlers

    // Send message on click
    $('#cheshire-chat-send').click(function () {
        sendMessage();
    });

    // Send message on Enter key press
    $('#cheshire-chat-input').keypress(function (event) {
        if (event.which === 13) {
            sendMessage();
            return false; // Prevent default behavior (form submission)
        }
    });

    // Close chat on X button click
    $('#cheshire-chat-close').click(function () {
        // Don't hide the chat on the playground page
        if ($('#cheshire-chat-container').hasClass('playground')) {
            return;
        }

        $('#cheshire-chat-container').removeClass('cheshire-chat-open').addClass('cheshire-chat-closed');
        // Store the state in localStorage so it persists across page loads
        localStorage.setItem('cheshire_chat_state', 'closed');
        // Close WebSocket connection when chat is closed
        closeWebSocket();
    });

    // Start new conversation on "New" button click
    $('#cheshire-chat-new').click(function () {
        clearChatHistory();
    });

    // Open chat when avatar is clicked
    $(document).on('click', '#cheshire-chat-avatar', function () {
        if ($('#cheshire-chat-container').hasClass('cheshire-chat-closed')) {
            $('#cheshire-chat-container').removeClass('cheshire-chat-closed').addClass('cheshire-chat-open');
            // Update localStorage
            localStorage.setItem('cheshire_chat_state', 'open');
            // Initialize WebSocket when chat is opened
            initWebSocket();
        }
    });

    // Check if chat should be opened on page load
    $(document).ready(function () {
        // Always show the chat on the playground page
        if ($('#cheshire-chat-container').hasClass('playground')) {
            $('#cheshire-chat-container').removeClass('cheshire-chat-closed').addClass('cheshire-chat-open');
            // Initialize WebSocket when chat is opened
            initWebSocket();
            return;
        }

        // If in preview mode, prepare the preview UI
        if (cheshire_ajax_object.is_preview) {
            $('#cheshire-chat-messages').empty();
            $('#cheshire-chat-messages').append('<div class="bot-message"><p>' + (cheshire_ajax_object.preview_text || 'Please log in to use the chatbot.') + '</p></div>');
            $('#cheshire-chat-input-container').hide();
            $('#cheshire-chat-new').hide();
            $('#cheshire-predefined-responses').hide();
            // Don't load stored messages in preview mode
            return;
        }

        // Check localStorage for saved state
        var savedState = localStorage.getItem('cheshire_chat_state');

        // If saved state is 'open' or default state is 'open' and no saved state, open the chat
        if (savedState === 'open' || (!savedState && cheshire_ajax_object.default_state === 'open')) {
            $('#cheshire-chat-container').removeClass('cheshire-chat-closed').addClass('cheshire-chat-open');
            // Initialize WebSocket when chat is opened
            initWebSocket();
        }
        // Otherwise, it stays closed (which is the default in HTML now)

        // For backward compatibility, check the old localStorage key and update to new format
        if (localStorage.getItem('cheshire_chat_hidden') === 'true') {
            // Ensure chat stays closed
            $('#cheshire-chat-container').removeClass('cheshire-chat-open').addClass('cheshire-chat-closed');
            localStorage.setItem('cheshire_chat_state', 'closed');
            // Remove old localStorage key
            localStorage.removeItem('cheshire_chat_hidden');
        }

        // Load stored messages from localStorage
        loadStoredMessages();
    });
});
