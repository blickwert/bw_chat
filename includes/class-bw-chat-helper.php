<?php

class BW_Chat_Helper {


    private static $config = 'Standardkonfiguration';
    /**
     * Fügt einem Post ein benutzerdefiniertes Feld (Custom Field) für Chat-Nachrichten hinzu.
     *
     * @param string $session_key Der Session-Key, um den entsprechenden Post zu finden.
     * @param string $meta_key Der Schlüssel für das benutzerdefinierte Feld.
     * @param string $meta_value Der Wert für das benutzerdefinierte Feld.
     * @return bool True, wenn das benutzerdefinierte Feld erfolgreich hinzugefügt wurde, andernfalls false.
     */




public function my_session_id() {
    // Überprüfen, ob das Cookie existiert
    if (isset($_COOKIE['bw_chat_session'])) {
        // Cookie-Wert als Session-ID nutzen
        $session_key = $_COOKIE['bw_chat_session'];
    } else {
        // Falls kein Cookie existiert, eine neue Session-ID generieren
        $session_key = bin2hex(random_bytes(16)); // Sichere zufällige ID generieren
        // Cookie setzen mit der neuen Session-ID
        setcookie('bw_chat_session', $session_key, time() + (86400 * 30), "/"); // Cookie für 30 Tage gültig
    }

    return $session_key;
}
    
    public static function is_session_id() {
        $output =  (self::my_session_id() ? false : true);
        return $output;        
    }


    public static function add_chat_custom_field($session_key, $meta_key, $meta_value) {
        // Finden des Posts anhand des Session-Keys
        $existing_post = self::get_post_by_session_key($session_key);

        if ($existing_post) {
            $post_id = $existing_post->ID;
            // Füge das benutzerdefinierte Feld hinzu
            add_post_meta($post_id, $meta_key, $meta_value);
            return true;
        }

        return false;
    }

    /**
     * Findet einen Post anhand des Session-Keys.
     *
     * @param string $session_key Der Session-Key, um den entsprechenden Post zu finden.
     * @return WP_Post|null Das Post-Objekt, wenn gefunden, andernfalls null.
     */
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
    
    

    public static function is_bw_chat_sessionid_post() {
        $session_key = self::my_session_id();
        $post_id = self::get_post_by_session_key( $session_key );
        return $post_id ? true : false;
    }




    /**
     * Überprüft, ob der Chat aktiviert ist
     *
     * @return bool True
     */
    public static function is_bw_chat_active() {
        $chat_status = get_option('bw_chat_activation', 'false'); // Standardmäßig 'false'
        if ($chat_status !== 'true') {
            return false;
        }
        return true;
    }


    /**
     * Überprüft, ob der Chat aktuell live ist basierend auf den Online-Zeiten und dem Chat-Status.
     *
     * @return bool True, wenn der Chat live ist und die aktuelle Zeit innerhalb der definierten Online-Zeiten liegt, andernfalls false.
     */
    public static function is_bw_chat_live() {
        $chat_status = get_option('bw_chat_status', 'false'); // Standardmäßig 'false'
        if ($chat_status !== 'true') {
            return false;
        }
        
        // Überprüfen, ob die aktuelle Zeit innerhalb der definierten Online-Zeiten liegt
        $is_online = self::onlinetime_is_current_time();
//        error_log("Chat live check: " . var_export($is_online, true)); // Logge den Rückgabewert zur Überprüfung
        return $is_online;
    }


    /**
     * Überprüft, ob die aktuelle Serverzeit innerhalb der angegebenen Online-Zeiten liegt.
     *
     * @return bool True, wenn die aktuelle Zeit innerhalb der Online-Zeiten liegt, andernfalls false.
     */
    public static function onlinetime_is_current_time() {
        // Abrufen der Online-Zeiten aus den Plugin-Optionen
        $online_times_string = get_option('bw_chat_online_times');

        // Wenn keine Zeiten definiert sind, ist der Chat offline
        if (empty($online_times_string)) {
            return false;
        }

        // Parst die Online-Zeiten in ein Array
        $online_times = self::onlinetime_parse($online_times_string);

        // Holen Sie sich den aktuellen Server-Tag und die Zeit
        $current_day = strtoupper(current_time('D', false)); // z.B., "Mon" -> "MO"
        $current_time = current_time('H.i', false); // Aktuelle Serverzeit im Format "H.i" (z.B., "14.30" für 14:30 Uhr)
        
        // Map PHP date 'D' format to our custom format
        $day_map = [
            'MON' => 'MO',
            'TUE' => 'DI',
            'WED' => 'MI',
            'THU' => 'DO',
            'FRI' => 'FR',
            'SAT' => 'SA',
            'SUN' => 'SO',
        ];
        if (!isset($day_map[$current_day])) {
            return false; // Der Tag wird nicht erkannt
        }

        $current_day_key = $day_map[$current_day];

        // Überprüfen, ob für den aktuellen Tag Online-Zeiten definiert sind
        if (!isset($online_times[$current_day_key])) {
            return false;
        }

        // Über jede Zeitperiode für den aktuellen Tag iterieren
        foreach ($online_times[$current_day_key] as $time_range) {
            // Split the time range into start and end
            list($start_time, $end_time) = explode('-', $time_range);

            // Konvertiere die Zeiten zu DateTime-Objekten
            $current = DateTime::createFromFormat('H.i', $current_time);
            $start = DateTime::createFromFormat('H.i', $start_time);
            $end = DateTime::createFromFormat('H.i', $end_time);

            // Wenn die aktuelle Zeit innerhalb des Bereichs liegt
            if ($current >= $start && $current <= $end) {
                return true;
            }
        }

        return false; // Keine passende Zeitspanne gefunden
    }

    /**
     * Parst die Eingabe der Online-Zeiten und gibt ein assoziatives Array zurück.
     * 
     * @param string $input Die Eingabezeichenfolge aus dem bw_chat_online_times-Feld.
     * @return array Die geparsten Online-Zeiten als assoziatives Array.
     */
    public static function onlinetime_parse($input) {
        $online_times = [];

        // Split the input by semicolon to separate different day ranges
        $day_ranges = explode(';', $input);

        foreach ($day_ranges as $range) {
            // Trim any extra whitespace
            $range = trim($range);

            // Split the range into days and times
            if (preg_match('/^(.*?)(\d)/', $range, $matches)) {
                $days_part = trim($matches[1]);
                $times_part = substr($range, strlen($matches[1]));
                $times = array_map('trim', explode('und', $times_part));

                // Parse the days part to find the range of days
                if (strpos($days_part, '-') !== false) {
                    // Handle day ranges (e.g., MO-DO)
                    list($start_day, $end_day) = explode('-', $days_part);
                    $start_day = trim($start_day);
                    $end_day = trim($end_day);
                    $days = self::onlinetime_expand_day_range($start_day, $end_day);
                } else {
                    // Handle single days (e.g., FR)
                    $days = [trim($days_part)];
                }

                // Add the times to each day in the range
                foreach ($days as $day) {
                    if (!isset($online_times[$day])) {
                        $online_times[$day] = [];
                    }
                    $online_times[$day] = array_merge($online_times[$day], $times);
                }
            }
        }

        return $online_times;
    }

    /**
     * Erweitert einen Tagesbereich (z.B. MO-DO) in ein Array von Tagen (z.B. ['MO', 'DI', 'MI', 'DO']).
     * 
     * @param string $start_day Der Starttag des Bereichs.
     * @param string $end_day Der Endtag des Bereichs.
     * @return array Der erweiterte Bereich der Tage.
     */
    private static function onlinetime_expand_day_range($start_day, $end_day) {
        $days_order = ['MO', 'DI', 'MI', 'DO', 'FR', 'SA', 'SO'];
        $start_index = array_search($start_day, $days_order);
        $end_index = array_search($end_day, $days_order);
        
        if ($start_index === false || $end_index === false) {
            return []; // Ungültige Tage
        }

        return array_slice($days_order, $start_index, $end_index - $start_index + 1);
    }

    /**
     * Extrahiert die E-Mail-Adresse aus einem String im Format "Name <email@domain.com>"
     *
     * @param string $email_string Die Eingabezeichenfolge, die die E-Mail-Adresse enthält.
     * @return string Die extrahierte E-Mail-Adresse.
     */
    public static function extract_email_address($email_string) {
        if (preg_match('/<(.+)>/', $email_string, $matches)) {
            return $matches[1];
        }
        return trim($email_string);
    }

    /**
     * Extrahiert den Namen aus einem String im Format "Name <email@domain.com>"
     *
     * @param string $email_string Die Eingabezeichenfolge, die den Namen enthält.
     * @return string Der extrahierte Name.
     */
    public static function extract_name($email_string) {
        if (preg_match('/(.+?)\s*<.+>/', $email_string, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    /**
     * Extrahiert den geantworteten Text aus einer E-Mail-Nachricht.
     *
     * @param string $message Die Nachricht, aus der der geantwortete Text extrahiert werden soll.
     * @return string Der extrahierte geantwortete Text.
     */
public static function extract_replied_text($message) {
    // Dekodiert den quoted-printable MIME-encoded Text
    $message = quoted_printable_decode($message);

    // Entferne HTML-Tags aus der Nachricht
    $message = strip_tags($message);

    // Dekodiert HTML-Entities und Sonderzeichen (einschließlich UTF-8 Emojis)
    $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

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
    $trim_filtered_message = trim($filtered_message);

    // Entferne jegliche unsichere oder unerwünschte HTML und formatiere den Text als reine Zeichenfolge
    return sanitize_text_field($trim_filtered_message);
}



    
    /**
     * Formatiert den neuen Inhalt aus den Custom Fields.
     *
     * @param int $post_id Die ID des Posts, für den die Custom Fields formatiert werden sollen.
     * @return string Der formatierte Inhalt.
     */
public static function format_CF_content($post_id) {
    $meta_key_user = self::cf_user_meta_key();
    $meta_key_admin = self::cf_admin_meta_key();

    // Hole alle relevanten Meta-Keys, die mit 'bw-chat-' beginnen
    $meta_items = self::post_meta_values($post_id, 'bw-chat-entry-');
    
    // Umkehre die Reihenfolge der Meta-Einträge
    $meta_items = array_reverse($meta_items, true); // true beibehält die Array-Schlüssel

    $formatted_content = '';

    // Schleife durch die umgekehrten Meta-Einträge
    foreach ($meta_items as $key => $value) {
        $formatted_content .= self::format_chat_item($key, $value, $post_id);
    }

    return $formatted_content;
}

    /**
     * Formatiert das Datum im gewünschten Format.
     *
     * @param int $timestamp Der Unix-Timestamp, der formatiert werden soll.
     * @param string $format Das gewünschte Datumsformat.
     * @return string Das formatierte Datum.
     */
    public static function format_date($timestamp, $format = 'y-m-d H:i') {
        $datetime = new DateTime("@$timestamp");
        return $datetime->format($format);
    }

    /**
     * Formatiert die Zeit im gewünschten Format.
     *
     * @param int $timestamp Der Unix-Timestamp, der formatiert werden soll.
     * @return string Die formatierte Zeit.
     */
    public static function format_time($timestamp) {
        $datetime = new DateTime("@$timestamp");
        return $datetime->format('H:i');
    }




    /**
     * Generiert den Meta-Schlüssel für eine Benutzereingabe (Formular/AJAX).
     *
     * @param int $timestamp Der Unix-Timestamp für den Zeitpunkt der Eingabe.
     * @return string Der generierte Meta-Schlüssel für die Benutzereingabe.
     */
    public static function cf_user_meta_key($timestamp = '') {
        return 'bw-chat-entry-user-' . $timestamp;
    }

    /**
     * Generiert den Meta-Schlüssel für eine Benutzereingabe (Formular/AJAX).
     *
     * @param int $timestamp Der Unix-Timestamp für den Zeitpunkt der Eingabe.
     * @return string Der generierte Meta-Schlüssel für die Benutzereingabe.
     */
    public static function cf_admin_meta_key($timestamp = '') {
        return 'bw-chat-entry-admin-' . $timestamp;
    }
    
    
    
    public static function post_meta_values($post_id, $strpos_key) {
        $meta_keys = get_post_meta($post_id);
        $filtered_meta = [];
    
        foreach ($meta_keys as $key => $values) {
            if (strpos($key, $strpos_key) === 0) {
                foreach ($values as $value) {
                    $filtered_meta[$key] = $value;
                }
            }
        }
    
        return $filtered_meta;
    }



    public static function get_chat_userdata($post_id) {
        global $post;
        $output = NULL;
        $output[form_of_address] = get_post_meta( $post_id, 'bw-chat-userprofile-form_of_address' );
        $output[name] = get_post_meta( $post_id, 'bw-chat-userprofile-name' );
//        $output['name'] = (!empty($output['name']) ? $output['name'] : false);
        $output[email] = get_post_meta( $post_id, 'bw-chat-userprofile-email' );

        return $output;
    }




/**
 * Bestimmt den Chat-Typ basierend auf dem Meta-Key.
 * Unterscheidet zwischen 'admin' und 'user'.
 */
public static function get_chat_type($meta_key) {
    $meta_key_user = self::cf_user_meta_key();  // Meta-Schlüssel für Benutzer
    $meta_key_admin = self::cf_admin_meta_key();  // Meta-Schlüssel für Administrator

    // Überprüfe, ob der Meta-Schlüssel dem Benutzer oder dem Administrator zugeordnet ist
    if (strpos($meta_key, $meta_key_user) === 0) {
        return 'user';  // Der Eintrag gehört dem Benutzer
    } elseif (strpos($meta_key, $meta_key_admin) === 0) {
        return 'admin';  // Der Eintrag gehört dem Administrator
    }

    return 'unknown';  // Fallback, falls der Typ nicht erkennbar ist
}


    public function get_post_meta_from_sessionid($key) {
        $post = self::get_post_by_session_key(self::my_session_id());
        $post_id = $post->ID;
        $name = get_post_meta($post_id, $key, true );
        return $name;
    }    

    
    public static function get_chat_userdata_salutation($post_id) {
        return get_post_meta($post_id, 'bw-chat-userprofile-form_of_address', true); // Einzelner Wert
    }
    
    public static function get_chat_userdata_name($post_id) {
        return get_post_meta($post_id, 'bw-chat-userprofile-name', true); // Einzelner Wert
    }
    
    public static function get_chat_userdata_email($post_id) {
        return get_post_meta($post_id, 'bw-chat-userprofile-email', true); // Einzelner Wert
    }
    
    public static function format_chat_item($meta_key, $value, $post_id) {
        $meta_key_user = self::cf_user_meta_key();
        $meta_key_admin = self::cf_admin_meta_key();
        $timestamp = str_replace([$meta_key_user, $meta_key_admin], '', $meta_key);
        $datetime = self::format_date($timestamp);
        $time = self::format_time($timestamp);
        
        // Benutzerinformationen abrufen
        $name = self::get_chat_userdata_name($post_id); // Ruft den Namen des Benutzers ab
    
        // Bereinige die Chat-Nachricht
        $chat_message = sanitize_text_field($value);
    
        // Bestimme den Typ basierend auf dem Meta-Key
        $type = self::get_chat_type($meta_key);
    
        // Formatierter Chat-Eintrag
        return "<div class='chat-item' data-chat-{$type}><div class='name'>{$name}</div><div class='chat-message'>{$chat_message}</div><div class='time'>{$time}</div></div>\n";
    }




}

