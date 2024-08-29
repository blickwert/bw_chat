<?php
/*
Plugin Name: BW Chat
Description: Ein Plugin zum Erstellen von Chat-Nachrichten und Sitzungen.
Version: 1.0
Author: Ihr Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
require_once plugin_dir_path(__FILE__) . 'includes/class-bw-chat-wordpress.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bw-chat-frontend.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bw-chat-plugin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bw-chat-email-check.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bw-chat-helper.php';

class BW_Chat {

    public function __construct() {
        add_action('init', [$this, 'start_session'], 1);
        new BW_Chat_WordPress();
        new BW_Chat_Frontend();
        new BW_Chat_Plugin_Page();
        new BW_Chat_Email_Check();
        new BW_Chat_Helper();
    }

    // Startet die Sitzung, wenn sie noch nicht gestartet wurde
    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }
}

new BW_Chat();
