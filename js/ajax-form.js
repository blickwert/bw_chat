jQuery(document).ready(function ($) {
    // Setze die Variablen
    var is_session_id = ajax_form_params.is_session_id;
    var is_chat_live = ajax_form_params.is_chat_live;


    // Cookie setzen und entfernen, wenn der Chat geöffnet oder geschlossen wird
    $('#bw-chat-button').on('click', function () {
        document.cookie = "bw-chat-state-is-open=" + is_session_id + "; path=/; SameSite=Lax";
        $('#bw-chat-button').removeClass('chat-show').addClass('chat-hidden');
        $('#bw-chat-window,#bw-chat-step-createchat').removeClass('chat-hidden').addClass('chat-show');
    });

    $('#bw-chat-close').on('click', function () {
        document.cookie = "bw-chat-state-is-open=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        $('#bw-chat-button').removeClass('chat-hidden').addClass('chat-show');
        $('#bw-chat-window').addClass('chat-hidden');
    });

    // Überprüfen, ob das Cookie gesetzt ist und Chat-Status basierend darauf anzeigen
    function checkChatStatus() {
        var chatOpenCookie = document.cookie.match(/bw-chat-state-is-open=(\w+)/);
        if (chatOpenCookie) {
            $('#bw-chat-button').addClass('chat-hidden');
            $('#bw-chat-window').removeClass('chat-hidden').addClass('chat-show');
            // Anzeigen des entsprechenden Formulars basierend auf den Variablen
            if (!is_chat_live) {
                $('#bw-chat-step-contactform').removeClass('chat-hidden').addClass('chat-show');
            } else if (is_chat_live && !is_session_id) {
                $('#bw-chat-step-createchat').removeClass('chat-hidden').addClass('chat-show');
            } else if (is_chat_live && is_session_id) {
                $('#bw-chat-step-welcometext').removeClass('chat-hidden').addClass('chat-show');
                $('#chat-item').removeClass('chat-hidden').addClass('chat-show');
                $('#bw-chat-form input').prop('disabled', false).prop('required', true);
            }
        }
    }

    checkChatStatus();

    // Polling alle 3 Sekunden, wenn das Cookie gesetzt ist
    if (document.cookie.indexOf("bw-chat-state-is-open") !== -1) {
        setInterval(function () {
            $.post(ajax_form_params.ajax_url, {
                action: 'check_reply_emails',
                security: ajax_form_params.nonce
            }, function (response) {
                if (response.success) {
                    console.log(response.data.message);
                    $('#chat-items').html(response.data.message);
                } else {
                    console.log(response.data);
                }
            });
        }, 3000);
    }

    // Senden des Create-Chat-Formulars
    $('#ajax-form-createchat').on('submit', function (e) {
        e.preventDefault();
        var formData = {
            action: 'handle_ajax_form_start',
            security: ajax_form_params.nonce_start,
            'bw-chat-userprofile-name': $('#bw-chat-userprofile-name').val(),
            'bw-chat-userprofile-email': $('#bw-chat-userprofile-email').val(),
            'bw-chat-userprofile-privacy': $('#bw-chat-userprofile-privacy').is(':checked')
        };
        $.post(ajax_form_params.ajax_url, formData, function (response) {
            if (response.success) {
                $('#bw-chat-step-createchat').removeClass('chat-show').addClass('chat-hidden');
                $('#bw-chat-step-welcometext').html(response.data).removeClass('chat-hidden').addClass('chat-show');
                $('#chat-userinput-entry,#chat-userinput-submit').prop('disabled', false).prop('required', true);


            } else {
                alert('Fehler: ' + response.data);
            }
        });
    });


    // Senden des Kontaktformulars
    $('#ajax-form-contactform').on('submit', function (e) {
        e.preventDefault();
        var formData = {
            action: 'handle_ajax_form_contact',
            security: ajax_form_params.nonce_contact,  // Verwende die Nonce für das Kontaktformular
            'bw-chat-contact-name': $('#bw-chat-contact-name').val(),
            'bw-chat-contact-email': $('#bw-chat-contact-email').val(),
            'bw-chat-contact-subject': $('#bw-chat-contact-subject').val(),
            'bw-chat-contact-message': $('#bw-chat-contact-message').val(),
            'bw-chat-contact-privacy': $('#bw-chat-contact-privacy').is(':checked')
        };
        $.post(ajax_form_params.ajax_url, formData, function (response) {
            if (response.success) {
                alert('Kontaktformular erfolgreich gesendet.');
            } else {
                alert('Fehler: ' + response.data);
            }
        });
    });
    
    
    
    // Senden des Benutzer-Chat-Eingabeformulars
    $('#ajax-form-userinput').on('submit', function (e) {
        e.preventDefault();
        var formData = {
            action: 'handle_ajax_form_userinput',
            security: ajax_form_params.nonce_userinput,
            'chat-userinput-entry': $('#chat-userinput-entry').val()
        };
        $.post(ajax_form_params.ajax_url, formData, function (response) {
            if (response.success) {
                $('#chat-items').html(response.data.message);
                $('#chat-userinput-entry').val('');
            } else {
                alert('Fehler beim Senden der Nachricht.');
            }
        });
    });
    
        console.log(is_session_id);
});
