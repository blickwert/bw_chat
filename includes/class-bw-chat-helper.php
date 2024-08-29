<?php

class BW_Chat_Helper {

    // Formatiert das Datum im gewŸnschten Format
    public static function format_date($timestamp, $format = 'y-m-d H:i') {
        $datetime = new DateTime("@$timestamp");
        return $datetime->format($format);
    }

    // Formatiert die Zeit im gewŸnschten Format
    public static function format_time($timestamp) {
        $datetime = new DateTime("@$timestamp");
        return $datetime->format('H:i');
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

    // Extrahiert die E-Mail-Adresse aus einem String wie "Name <email@domain.com>"
    public static function extract_email_address($email_string) {
        if (preg_match('/<(.+)>/', $email_string, $matches)) {
            return $matches[1];
        }
        return trim($email_string);
    }

    // Extrahiert den Namen aus einem String wie "Name <email@domain.com>"
    public static function extract_name($email_string) {
        if (preg_match('/(.+?)\s*<.+>/', $email_string, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
}
