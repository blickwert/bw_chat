<?php

class BW_Chat_Helper {

    // Formatiert den neuen Inhalt aus den Custom Fields
    public static function format_CF_content($post_id) {
        $meta_keys = get_post_meta($post_id);
        $formatted_content = '';

        foreach ($meta_keys as $key => $values) {
            if (strpos($key, 'bw-chat-entry-') === 0) {
                $timestamp = str_replace('bw-chat-entry-', '', $key);
                $datetime = self::format_date($timestamp);
                $time = self::format_time($timestamp);
                foreach ($values as $value) {
                    $name = esc_html($value);
                    $formatted_content .= "<div data-date='{$datetime}'>{$time} - {$name}</div>\n";
                }
            }
        }

        return $formatted_content;
    }

    // Formatiert das Datum im gewünschten Format
    public static function format_date($timestamp, $format = 'y-m-d H:i') {
        $datetime = new DateTime("@$timestamp");
        return $datetime->format($format);
    }

    // Formatiert die Zeit im gewünschten Format
    public static function format_time($timestamp) {
        $datetime = new DateTime("@$timestamp");
        return $datetime->format('H:i');
    }

    // Extrahiert die E-Mail-Adresse aus einem String im Format "Name <email@domain.com>"
    public static function extract_email_address($email_string) {
        if (preg_match('/<(.+)>/', $email_string, $matches)) {
            return $matches[1];
        }
        return trim($email_string);
    }

    // Extrahiert den Namen aus einem String im Format "Name <email@domain.com>"
    public static function extract_name($email_string) {
        if (preg_match('/(.+?)\s*<.+>/', $email_string, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    // Findet einen Post anhand des Session-Keys
    public static function get_post_by_session_key($session_key) {
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

    // Extrahiert den geantworteten Text aus einer E-Mail-Nachricht
    public static function extract_replied_text($message) {
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

    // Erstellt ein Custom Field für die Antwortnachricht und aktualisiert den Post-Inhalt
    public static function create_reply_custom_field($session_key, $replied_text) {
        $existing_post = self::get_post_by_session_key($session_key);

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
}
