<?php

class BW_Chat_Email_Handler {

    private $use_custom_smtp = false;

    public function __construct() {
        // Hook in phpmailer_init fŸr SMTP-Konfiguration
        add_action('phpmailer_init', [$this, 'configure_phpmailer']);
    }

    // Sendet eine E-Mail-Benachrichtigung an den Administrator
    public function send_email_notification($name, $session_key) {
        $send_to_raw = get_option('bw_chat_notification_email'); // Send To (kann Name <email@domain.com> sein)
        $send_from_raw = get_option('bw_chat_reply_to_email'); // Send From (kann Name <email@domain.com> sein)

        // Extrahiere Name und E-Mail-Adresse fŸr Send To
        $send_to = BW_Chat_Helper::extract_email_address($send_to_raw);
        $send_to_name = BW_Chat_Helper::extract_name($send_to_raw);

        // Extrahiere Name und E-Mail-Adresse fŸr Send From
        $send_from = BW_Chat_Helper::extract_email_address($send_from_raw);
        $send_from_name = BW_Chat_Helper::extract_name($send_from_raw);

        $subject = 'Neue Chat-Nachricht - ' . $session_key;

        // Nachricht formatieren mit den bestehenden Chat-EintrŠgen
        $existing_post = BW_Chat_Helper::get_post_by_session_key($session_key);
        $message_body = "Bisheriger Chat-Verlauf:\n\n";

        if ($existing_post) {
            $post_id = $existing_post->ID;
            $meta_keys = get_post_meta($post_id);

            foreach ($meta_keys as $key => $values) {
                if (strpos($key, 'bw-chat-entry-') === 0) {
                    $timestamp = str_replace('bw-chat-entry-', '', $key);
                    $time = BW_Chat_Helper::format_time($timestamp);
                    $date = BW_Chat_Helper::format_date($timestamp, 'd. M');
                    foreach ($values as $value) {
                        $formatted_value = esc_html($value);
                        $message_body .= "- {$time} {$date} | {$formatted_value}\n";
                    }
                }
            }
        }

        // FŸge die Session-ID am Ende der Nachricht hinzu
        $message_body .= "\nNachrichten ID: " . $session_key;

        // †berprŸfen, ob SMTP aktiviert ist
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

        // ZurŸcksetzen des Flags nach dem Senden der E-Mail
        $this->use_custom_smtp = false;
    }

    // Konfiguriert PHPMailer, wenn benutzerdefiniertes SMTP aktiviert ist
    public function configure_phpmailer($phpmailer) {
        if ($this->use_custom_smtp) {
            // SMTP-Konfigurationen laden
            $smtp_host_config = get_option('bw_chat_smtp_host');
            $smtp_host = '';
            $smtp_port = 587;  // Standardport
            $smtp_secure = 'tls';  // StandardverschlŸsselung

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
}
