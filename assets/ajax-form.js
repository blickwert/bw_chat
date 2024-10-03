jQuery(document).ready(function ($) {

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
            $('#bw-chat-button').on('click', this.openChat); // �ffnet den Chat, wenn der Button geklickt wird
            $('#bw-chat-close').on('click', this.closeChat); // Schlie�t den Chat
            $('#ajax-form-createchat').on('submit', this.createChat); // Senden des Create-Chat-Formulars
            $('#ajax-form-contactform').on('submit', this.submitContactForm); // Senden des Kontaktformulars
            $('#ajax-form-userinput').on('submit', this.sendUserInput); // Senden der Benutzereingaben im Chat
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
            setInterval(function () {
                $.post(ajax_form_params.ajax_url, {
                    action: 'handle_ajax_form_userinput',
                    security: ajax_form_params.nonce_userinput
                }, function (response) {
                    if (response.success) {
                        // Aktualisiere den Chat mit neuen Nachrichten
                        $('#chat-items').html(response.data.message);
                    } else {
                    //    console.log('Keine neuen Nachrichten.');
                    }
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    // Behandle Fehler beim AJAX-Aufruf
                    console.error('AJAX Fehler: ', textStatus, errorThrown);
                });
            }, 2000);
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

// Senden des Kontaktformulars und potenzielle Statusaktualisierung
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

        if (response.success) {
            // Erfolgsfall - pr�fen, ob response.data und response.data.message existieren
            if (response.data && response.data.message) {
                alert(response.data.message); // Zeigt die Nachricht "foo" an
            } else {
                alert('Erfolg, aber keine Nachricht vorhanden.');
                console.error('Antwortstruktur unerwartet:', response);
            }
        } else {
            // Fehlerfall - auch hier pr�fen, ob response.data.message existiert
            alert('Fehler: ' + (response.data && response.data.message ? response.data.message : 'Undefinierter Fehler.'));
            console.error('Fehlerhafte Antwort:', response);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        // Fehler im Netzwerk oder auf dem Server
        console.error('AJAX Fehler:', textStatus, errorThrown);
        alert('AJAX Fehler: ' + textStatus);
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
