<?php

class BW_Chat_Frontend {

    private $use_custom_smtp = false;

    public function __construct() {
        add_action('wp_ajax_handle_ajax_form', [$this, 'handle_ajax_form']);
        add_action('wp_ajax_nopriv_handle_ajax_form', [$this, 'handle_ajax_form']);
        add_action('wp_ajax_check_post_content', [$this, 'check_post_content']);
        add_action('wp_ajax_nopriv_check_post_content', [$this, 'check_post_content']);
        add_shortcode('bw_chat_form', [$this, 'render_chat_form']);
        
        // Hook in phpmailer_init für SMTP-Konfiguration
        add_action('phpmailer_init', [$this, 'configure_phpmailer']);
    }

    // Handhabt die AJAX-Anfrage zum Speichern einer Chat-Nachricht in Custom Fields und gibt den gesamten Inhalt des Posts zurück
    public function handle_ajax_form() {
        // Überprüfe zuerst die neuen E-Mails
        do_action('bw_chat_check_emails');
        
        check_ajax_referer('ajax-form-nonce', 'security');

        $name = sanitize_text_field($_POST['name']);
        $session_key = session_id();
        $current_timestamp = current_time('timestamp');

        $existing_post = $this->get_post_by_session_key($session_key);

        if ($existing_post) {
            $post_id = $existing_post->ID;
        } else {
            $post_id = wp_insert_post(array(
                'post_title' => $session_key,
                'post_status' => 'publish',
                'post_type' => 'bw-chat'
            ));
        }

        if ($post_id) {
            // Speichern der Formulareingaben in Custom Fields
            $meta_key = 'bw-chat-entry-' . $current_timestamp;
            add_post_meta($post_id, $meta_key, $name);

            // Benachrichtigung per E-Mail an den Administrator
            $this->send_email_notification($name, $session_key);

            $formatted_content = $this->format_CF_content($post_id);

            // Deaktiviere wpautop und Konvertierung von Zeilenumbrüchen
            remove_filter('the_content', 'wpautop');
            remove_filter('the_content', 'wptexturize');

            $response = array(
                'message' => apply_filters('the_content', $formatted_content)  // Inhalt des gesamten Posts zurückgeben
            );
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Es gab einen Fehler beim Speichern Ihrer Nachricht.');
        }
    }

    // Handhabt die AJAX-Anfrage zum Überprüfen des Postinhalts
    public function check_post_content() {
        check_ajax_referer('ajax-form-nonce', 'security');

        $session_key = session_id();
        $existing_post = $this->get_post_by_session_key($session_key);

        if ($existing_post) {
            $post_id = $existing_post->ID;
            $formatted_content = $this->format_CF_content($post_id);

            // Deaktiviere wpautop und Konvertierung von Zeilenumbrüchen
            remove_filter('the_content', 'wpautop');
            remove_filter('the_content', 'wptexturize');

            $response = array(
                'message' => apply_filters('the_content', $formatted_content)  // Inhalt des gesamten Posts zurückgeben
            );
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Post nicht gefunden.');
        }
    }

    // Formatiert den neuen Inhalt aus den Custom Fields
    private function format_CF_content($post_id) {
        $meta_keys = get_post_meta($post_id);
        $formatted_content = '';

        foreach ($meta_keys as $key => $values) {
            if (strpos($key, 'bw-chat-entry-') === 0) {
                $timestamp = str_replace('bw-chat-entry-', '', $key);
                $datetime = $this->format_date($timestamp);
                $time = $this->format_time($timestamp);
                foreach ($values as $value) {
                    $name = esc_html($value);
                    $formatted_content .= "<div data-date='{$datetime}'>{$time} - {$name}</div>\n";
                }
            }
        }

        return $formatted_content;
    }

    // Formatiert das Datum im gewünschten Format
    private function format_date($timestamp, $format = 'y-m-d H:i') {
        $datetime = new DateTime("@$timestamp");
        return $datetime->format($format);
    }

    // Formatiert die Zeit im gewünschten Format
    private function format_time($timestamp) {
        $datetime = new DateTime("@$timestamp");
        return $datetime->format('H:i');
    }

    // Sendet eine E-Mail-Benachrichtigung an den Administrator
    private function send_email_notification($name, $session_key) {
        $send_to_raw = get_option('bw_chat_notification_email'); // Send To (kann Name <email@domain.com> sein)
        $send_from_raw = get_option('bw_chat_reply_to_email'); // Send From (kann Name <email@domain.com> sein)

        // Extrahiere Name und E-Mail-Adresse für Send To
        $send_to = $this->extract_email_address($send_to_raw);
        $send_to_name = $this->extract_name($send_to_raw);

        // Extrahiere Name und E-Mail-Adresse für Send From
        $send_from = $this->extract_email_address($send_from_raw);
        $send_from_name = $this->extract_name($send_from_raw);

        $subject = 'Neue Chat-Nachricht - ' . $session_key;

        // Nachricht formatieren mit den bestehenden Chat-Einträgen
        $existing_post = $this->get_post_by_session_key($session_key);
        $message_body = "Bisheriger Chat-Verlauf:\n\n";

        if ($existing_post) {
            $post_id = $existing_post->ID;
            $meta_keys = get_post_meta($post_id);

            foreach ($meta_keys as $key => $values) {
                if (strpos($key, 'bw-chat-entry-') === 0) {
                    $timestamp = str_replace('bw-chat-entry-', '', $key);
                    $time = $this->format_time($timestamp);
                    $date = $this->format_date($timestamp, 'd. M');
                    foreach ($values as $value) {
                        $formatted_value = esc_html($value);
                        $message_body .= "- {$time} {$date} | {$formatted_value}\n";
                    }
                }
            }
        }

        // Füge die Session-ID am Ende der Nachricht hinzu
        $message_body .= "\nNachrichten ID: " . $session_key;

        // Überprüfen, ob SMTP aktiviert ist
        $smtp_enabled = get_option('bw_chat_smtp_enabled'); // Ja oder Nein

        if ($smtp_enabled === 'Ja') {
            // Setze das Flag zur Verwendung von benutzerdefiniertem SMTP
            $this->use_custom_smtp = true;
        }

        // Senden der E-Mail
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        if ($send_from) {
            $headers[] = 'From: ' . $send_from_name . ' <' . $send_from . '>';
        }
        wp_mail($send_to, $subject, $message_body, $headers);

        // Zurücksetzen des Flags nach dem Senden der E-Mail
        $this->use_custom_smtp = false;
    }

    // Konfiguriert PHPMailer, wenn benutzerdefiniertes SMTP aktiviert ist
    public function configure_phpmailer($phpmailer) {
        if ($this->use_custom_smtp) {
            // SMTP-Konfigurationen laden
            $smtp_host_config = get_option('bw_chat_smtp_host');
            $smtp_host = '';
            $smtp_port = 587;  // Standardport
            $smtp_secure = 'tls';  // Standardverschlüsselung

            if ($smtp_host_config) {
                $host_parts = explode(':', $smtp_host_config);
                $smtp_host = $host_parts[0];
                
                if (isset($host_parts[1])) {
                    $port_secure = explode('/', $host_parts[1]);
                    $smtp_port = isset($port_secure[0]) ? $port_secure[0] : 587;
                    $smtp_secure = isset($port_secure[1]) ? $port_secure[1] : 'tls';
                }
            }

            // Setze PHPMailer-Einstellungen
            $phpmailer->isSMTP();
            $phpmailer->Host = $smtp_host;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Port = $smtp_port;
            $phpmailer->SMTPSecure = ($smtp_secure === 'ssl') ? 'ssl' : 'tls';
            $phpmailer->Username = get_option('bw_chat_smtp_username');
            $phpmailer->Password = get_option('bw_chat_smtp_password');
        }
    }

    // Hilfsfunktionen zum Extrahieren von Name und E-Mail-Adresse
    private function extract_email_address($email_string) {
        if (preg_match('/<(.+)>/', $email_string, $matches)) {
            return $matches[1];
        }
        return trim($email_string);
    }

    private function extract_name($email_string) {
        if (preg_match('/(.+?)\s*<.+>/', $email_string, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    // Findet einen Post anhand des Session-Keys
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

    // Rendert das Chat-Formular
    public function render_chat_form() {
        ob_start(); ?>
        <form id="ajax-form" method="post">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
            <input type="submit" value="Abschicken">
        </form>

        <div id="form-result"></div>
        <?php
        return ob_get_clean();
    }
}
