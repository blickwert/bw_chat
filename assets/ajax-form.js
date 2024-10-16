jQuery(document).ready(function ($) {


/*
    // Delegiere das Event von einem bestehenden Element (in diesem Fall document)
    $(document).on('input', '#chat-userinput-entry', function () {
        // Setze die H�he auf "auto", um die H�he zur�ckzusetzen
        $(this).css('height', 'auto');
        // Passe die H�he an den Scrollinhalt an
        $(this).css('height', this.scrollHeight + 'px');
    });
    
*/
    
    
    var BWChat = {
        // Speichere die Chat-spezifischen Variablen aus dem PHP-Backend
        session_id: ajax_form_params.session_id,
        is_chat_live: ajax_form_params.is_chat_live,
        post_sessionid: ajax_form_params.post_sessionid,

        // Initialisierung der Chat-Funktionen
        init: function () {
            this.bindEvents(); // Verkn�pfe die DOM-Events
            this.checkChatStatus(); // �berpr�fe den aktuellen Status des Chats

            // Benutzerdefiniertes Event f�r Chat-Status-�nderungen
            $(document).on('chatStatusUpdated', function () {
                BWChat.checkChatStatus(); // �berpr�fe den Chat-Status, wenn das Event ausgel�st wird
            });

            this.startPolling(); // Starte das Polling, um regelm��ig neue Nachrichten abzurufen
        },

        // Funktion zum Verkn�pfen von DOM-Events mit entsprechenden Funktionen
        bindEvents: function () {
            $('#bw-chat-button').on('click', this.openChat.bind(this)); // Verwende .bind(this), um den Kontext zu bewahren
            $('#bw-chat-close').on('click', this.closeChat.bind(this));
            $('#ajax-form-createchat').on('submit', this.createChat.bind(this));
            $('#ajax-form-contactform').on('submit', this.submitContactForm.bind(this));
            $('#ajax-form-userinput').on('submit', this.sendUserInput.bind(this));
        },

        // Funktion zum �ffnen des Chats
        openChat: function () {
            // Setze ein Cookie, das zeigt, dass der Chat ge�ffnet wurde
            document.cookie = "bw-chat-state-is-open=" + BWChat.session_id + "; path=/; SameSite=Lax";
            // Ver�ndere die Anzeige des Chat-Buttons und des Chat-Fensters
            $('#bw-chat-button').removeClass('chat-show').addClass('chat-hidden');
            $('#bw-chat-window,#bw-chat-step-createchat').removeClass('chat-hidden').addClass('chat-show');
        },

        // Funktion zum Schlie�en des Chats
        closeChat: function () {
            // L�sche das Cookie, das den offenen Zustand des Chats speichert
            document.cookie = "bw-chat-state-is-open=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            // Ver�ndere die Anzeige des Chat-Buttons und verstecke das Chat-Fenster
            $('#bw-chat-button').removeClass('chat-hidden').addClass('chat-show');
            $('#bw-chat-window').addClass('chat-hidden');
        },

        // �berpr�fe den aktuellen Status des Chats und passe die Benutzeroberfl�che an
        checkChatStatus: function () {
            var chatOpenCookie = BWChat.getCookie('bw-chat-state-is-open'); // �berpr�fe, ob das Cookie gesetzt ist

            if (chatOpenCookie) {
                // Der Chat ist ge�ffnet, passe die Oberfl�che an
                $('#bw-chat-button').removeClass('chat-show').addClass('chat-hidden');
                $('#bw-chat-window').removeClass('chat-hidden').addClass('chat-show');

                // Verschiedene Zust�nde des Chats je nach Live-Status und Post-Existenz
                if (!BWChat.is_chat_live) {
                    $('#bw-chat-step-contactform').removeClass('chat-hidden').addClass('chat-show');
                } else if (BWChat.is_chat_live && !BWChat.post_sessionid) {
                    $('#bw-chat-step-createchat').removeClass('chat-hidden').addClass('chat-show');
                } else if (BWChat.is_chat_live && BWChat.post_sessionid) {
                    $('#bw-chat-step-welcometext').removeClass('chat-hidden').addClass('chat-show');
                    $('#chat-items').removeClass('chat-hidden').addClass('chat-show');
                    $('#bw-chat-form input').prop('disabled', false).prop('required', true);
                }
            }
        },

      // Starte das Polling, um alle 2 Sekunden nach neuen Nachrichten zu pr�fen
        startPolling: function () {
            // Polling f�r neue Chat-Nachrichten
            setInterval(function () {
                $.post(ajax_form_params.ajax_url, {
                    action: 'handle_ajax_form_userinput',
                    security: ajax_form_params.nonce_userinput
                }, function (response) {
                    if (response.success) {
                        // Aktualisiere den Chat mit neuen Nachrichten
                        $('#bw-chat-step-createchat').removeClass('chat-show').addClass('chat-hidden');
                        $('#chat-items').html(response.data.message);
                    } else {
                        // Keine neuen Nachrichten
                    }
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    // Behandle Fehler beim AJAX-Aufruf
                    console.error('AJAX Fehler: ', textStatus, errorThrown);
                });
            }, 2000);

            // Zus�tzliches Polling f�r E-Mail-Antworten alle 3 Sekunden
//            if (document.cookie.indexOf("bw-chat-state-is-open") !== -1) {
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
                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Fehler beim E-Mail Polling: ', textStatus, errorThrown);
                    });
                }, 3000);
 //           }
        },



        // Senden des Create-Chat-Formulars und Aktualisierung des Status
        createChat: function (e) {
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
                    // Setze ein Cookie, das zeigt, dass der Chat aktiv ist
                    document.cookie = "bw-chat-state-is-active=" + BWChat.session_id + "; path=/; SameSite=Lax";
                    // Aktualisiere die Benutzeroberfl�che, um den neuen Zustand anzuzeigen
                    $('#bw-chat-step-createchat').removeClass('chat-show').addClass('chat-hidden');
                    $('#bw-chat-step-welcometext').html(response.data).removeClass('chat-hidden').addClass('chat-show');
                    $('#chat-userinput-entry,#chat-userinput-submit').prop('disabled', false).prop('required', true);

                    // L�se das Event aus, dass der Chat-Status aktualisiert wurde
                    $(document).trigger('chatStatusUpdated');
                } else {
                    alert('Fehler: ' + response.data);
                }
            });
        },

submitContactForm: function (e) {
    e.preventDefault();

    var formData = {
        action: 'handle_ajax_form_contact',
        security: ajax_form_params.nonce_contact,
        'bw-chat-contact-name': $('#bw-chat-contact-name').val(),
        'bw-chat-contact-email': $('#bw-chat-contact-email').val(),
        'bw-chat-contact-subject': $('#bw-chat-contact-subject').val(),
        'bw-chat-contact-message': $('#bw-chat-contact-message').val(),
        'bw-chat-contact-privacy': $('#bw-chat-contact-privacy').is(':checked')
    };

    $.post(ajax_form_params.ajax_url, formData, function (response) {
        console.log('AJAX Antwort:', response); // Zum Debuggen der gesamten Antwort

        var resultElement = $('#bw-chat-contactform-result');

        if (response.success) {
            // Erfolgsfall - pr�fen, ob response.data und response.data.message existieren
            if (response.data && response.data.message) {
                resultElement.html('<div class="notice success">' + response.data.message + '</div>'); // Erfolgreiche Nachricht anzeigen
            } else {
                resultElement.html('<div class="notice success">Erfolg, aber keine Nachricht vorhanden.</div>');
                console.error('Antwortstruktur unerwartet:', response);
            }

            // Formularfelder leeren
            $('#bw-chat-contact-name').val('');
            $('#bw-chat-contact-email').val('');
            $('#bw-chat-contact-subject').val('');
            $('#bw-chat-contact-message').val('');
            $('#bw-chat-contact-privacy').prop('checked', false);
        } else {
            // Fehlerfall - auch hier pr�fen, ob response.data.message existiert
            var errorMessage = response.data && response.data.message ? response.data.message : 'Undefinierter Fehler.';
            resultElement.html('<div class="notice error">Fehler: ' + errorMessage + '</div>');
            console.error('Fehlerhafte Antwort:', response);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        // Fehler im Netzwerk oder auf dem Server
        console.error('AJAX Fehler:', textStatus, errorThrown);
        $('#bw-chat-contactform-result').html('<div class="notice error">AJAX Fehler: ' + textStatus + '</div>');
    });
},

        // Senden der Benutzereingaben im Chat und Aktualisierung der Oberfl�che
        sendUserInput: function (e) {
            e.preventDefault();
            var formData = {
                action: 'handle_ajax_form_userinput',
                security: ajax_form_params.nonce_userinput,
                'chat-userinput-entry': $('#chat-userinput-entry').val()
            };
            $.post(ajax_form_params.ajax_url, formData, function (response) {
                if (response.success) {
                    // Aktualisiere die Chat-Nachrichtenanzeige und l�sche das Eingabefeld
                     $('#chat-items').removeClass('chat-hidden').addClass('chat-show');

                    $('#chat-items').html(response.data.message);
                    $('#chat-userinput-entry').val('');
                    // L�se das Event aus, dass der Chat-Status aktualisiert wurde
                    $(document).trigger('chatStatusUpdated');
                } else {
                    alert('Fehler beim Senden der Nachricht.');
                }
            });
        },

        // Hilfsfunktion zum Abrufen eines Cookies
        getCookie: function (name) {
            var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? match[2] : null; // Gibt den Wert des Cookies zur�ck oder `null`, wenn es nicht existiert
        }
    };

    // Initialisiere den BWChat
    BWChat.init();
});
