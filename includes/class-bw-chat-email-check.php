<?php

class BW_Chat_Email_Check {

    public function __construct() {
        // Registriere den Aufruf von check_reply_emails() über admin_init
        add_action('admin_init', [$this, 'check_reply_emails']);
    }

    // Funktion zum Scannen des Posteingangs auf neue Nachrichten
    public function check_reply_emails() {
        $hostname = get_option('bw_chat_imap_hostname');
        $username = get_option('bw_chat_imap_username');
        $password = get_option('bw_chat_imap_password');

        $inbox = imap_open($hostname, $username, $password) or die('Cannot connect to IMAP server: ' . imap_last_error());

        $session_key = session_id(); // Beispiel-Session-Key
        $search_criteria = 'UNSEEN BODY "' . $session_key . '"';
        $emails = imap_search($inbox, $search_criteria);

        $results = [];

        if ($emails) {
            foreach ($emails as $email_number) {
                $overview = imap_fetch_overview($inbox, $email_number, 0);
                $message = imap_fetchbody($inbox, $email_number, 1);

                // Betreff der E-Mail im Log ausgeben
                if (!empty($overview) && isset($overview[0]->subject)) {
                    error_log('E-Mail Betreff: ' . $overview[0]->subject);
                }

                $this->process_reply_email($overview, $message, $session_key);
            }
        } else {
//            error_log('Keine neuen E-Mails gefunden für Session Key: ' . $session_key);
        }

        imap_close($inbox);
        
        return $results;
    }

    // Funktion zum Verarbeiten der Antwort-E-Mail
    private function process_reply_email($overview, $message, $session_key) {
        // Extrahiere den geantworteten Text aus dem E-Mail-Body
        $replied_text = $this->extract_replied_text($message);

        // Erstelle ein Custom Field im Post mit der Session-ID und aktualisiere den Post-Inhalt
        $this->create_reply_custom_field($session_key, $replied_text);
    }

    // Funktion zum Extrahieren des geantworteten Textes
private function extract_replied_text($message) {
    // Entferne HTML-Tags aus der Nachricht
    $message = strip_tags($message);

    // Entferne überflüssige Leerzeichen und normalisiere Zeilenumbrüche
    $message = preg_replace('/\r\n|\r|\n/', "\n", trim($message));
    
    // Splitte die Nachricht in einzelne Zeilen
    $lines = explode("\n", $message);

    // Zeichenfolgen für Signatur und zitierten Text filtern
    $replied_text = [];
    $signature_found = false;
    $empty_line_count = 0;

    foreach ($lines as $line) {
        // Überprüfen auf zitierten Text
        if (preg_match('/^\s*>/', $line)) {
            continue; // Zitierten Text überspringen
        }

        // Überprüfen auf leere Zeilen (für Signaturerkennung)
        if (trim($line) === '') {
            $empty_line_count++;
            if ($empty_line_count >= 2) {
                $signature_found = true; // Signatur gefunden
                break;
            }
        } else {
            $empty_line_count = 0; // Zurücksetzen, wenn keine leere Zeile
        }

        $replied_text[] = $line;
    }

    // Join der gefilterten Zeilen und trimmen von Leerzeichen
    $filtered_message = implode(" ", $replied_text);
    return trim($filtered_message);
}

    // Funktion zum Erstellen eines Custom Fields für die Antwortnachricht und Aktualisieren des Post-Inhalts
    private function create_reply_custom_field($session_key, $replied_text) {
        $existing_post = $this->get_post_by_session_key($session_key);

        if ($existing_post) {
            $post_id = $existing_post->ID;
            $timestamp = current_time('timestamp');
            $meta_key = 'bw-chat-entry-' . $timestamp;
            add_post_meta($post_id, $meta_key, $replied_text);

            // Aktualisiere den Post-Inhalt
            $updated_content = $existing_post->post_content . "\n\nAntwort:\n" . $replied_text;
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $updated_content
            ));
        }
    }

    // Funktion zum Finden eines Posts anhand des Session-Keys
    private function get_post_by_session_key($session_key) {
        $query = new WP_Query(array(
            'title' => $session_key,
            'post_type' => 'bw-chat',
            'post_status' => 'publish',
            'posts_per_page' => 1,
        ));

        if ($query->have_posts()) {
            return $query->posts[0];
        }

        return null;
    }
}
