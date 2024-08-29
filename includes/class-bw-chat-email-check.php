<?php

class BW_Chat_Email_Check {

    public function __construct() {
        add_action('admin_init', [$this, 'check_reply_emails']);
    }

    public function check_reply_emails() {
        $hostname = get_option('bw_chat_imap_hostname');
        $username = get_option('bw_chat_imap_username');
        $password = get_option('bw_chat_imap_password');

        $inbox = imap_open($hostname, $username, $password) or die('Cannot connect to IMAP server: ' . imap_last_error());

        $session_key = session_id();
        $search_criteria = 'UNSEEN BODY "' . $session_key . '"';
        $emails = imap_search($inbox, $search_criteria);

        $results = [];

        if ($emails) {
            foreach ($emails as $email_number) {
                $overview = imap_fetch_overview($inbox, $email_number, 0);
                $message = imap_fetchbody($inbox, $email_number, 1);

                if (!empty($overview) && isset($overview[0]->subject)) {
                    error_log('E-Mail Betreff: ' . $overview[0]->subject);
                }

                $replied_text = BW_Chat_Helper::extract_replied_text($message);
                BW_Chat_Helper::create_reply_custom_field($session_key, $replied_text);
            }
        } else {
            error_log('Keine neuen E-Mails gefunden für Session Key: ' . $session_key);
        }

        imap_close($inbox);
        
        return $results;
    }
}
