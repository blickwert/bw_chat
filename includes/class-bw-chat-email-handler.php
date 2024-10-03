<?php

class BW_Chat_Email_Handler {

    private $use_custom_smtp = false;

    public function __construct() {
        // Hook in phpmailer_init fŸr SMTP-Konfiguration
        add_action('phpmailer_init', [$this, 'configure_phpmailer']);
    }

/*
    // Sendet eine E-Mail-Benachrichtigung an den Administrator
    private function send_email_notification($name, $session_key) {
        $send_to_raw = get_option('bw_chat_notification_email');
        $send_from_raw = get_option('bw_chat_reply_to_email');
    
        $send_to = BW_Chat_Helper::extract_email_address($send_to_raw);
        $send_to_name = BW_Chat_Helper::extract_name($send_to_raw);
    
        $send_from = BW_Chat_Helper::extract_email_address($send_from_raw);
        $send_from_name = BW_Chat_Helper::extract_name($send_from_raw);
    
        $subject = 'Neue Chat-Nachricht - ' . $session_key;
    
        $existing_post = BW_Chat_Helper::get_post_by_session_key($session_key);
        $message_body = "Bisheriger Chat-Verlauf:\n\n";
        
        if ($existing_post) {
            $post_id = $existing_post->ID;
            $meta_items = BW_Chat_Helper::post_meta_values($post_id, 'bw-chat-');
    
            foreach ($meta_items as $key => $value) {
                $meta_key_user = self::cf_user_meta_key();
                $meta_key_admin = self::cf_admin_meta_key();
                $timestamp = str_replace([$meta_key_user, $meta_key_admin], '', $meta_key);
                $time = BW_Chat_Helper::format_time($timestamp);
                $date = BW_Chat_Helper::format_date($timestamp, 'd. M');
                $formatted_value = esc_html($value);
                $type = strpos($key, $meta_key_admin) === 0 ? 'admin' : 'user';
                $message_body .= "- {$time} {$date} | {$type} {$formatted_value}\n";
            }
        }
    
        $message_body .= "\nNachrichten ID: " . $session_key;
    
        $smtp_enabled = get_option('bw_chat_smtp_enabled');
    
        if ($smtp_enabled === 'Ja') {
            $this->use_custom_smtp = true;
        }
    
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        if ($send_from) {
            $headers[] = 'From: ' . $send_from_name . ' <' . $send_from . '>';
        }
        wp_mail($send_to, $subject, $message_body, $headers);
    
        $this->use_custom_smtp = false;
    }
*/



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
