<?php

class BW_Chat_Plugin_Page {

    private $email_option_name = 'bw_chat_notification_email';
    private $reply_to_option_name = 'bw_chat_reply_to_email';
    private $imap_hostname_option_name = 'bw_chat_imap_hostname';
    private $imap_username_option_name = 'bw_chat_imap_username';
    private $imap_password_option_name = 'bw_chat_imap_password';
    private $smtp_enabled_option_name = 'bw_chat_smtp_enabled';
    private $smtp_host_option_name = 'bw_chat_smtp_host';
    private $smtp_username_option_name = 'bw_chat_smtp_username';
    private $smtp_password_option_name = 'bw_chat_smtp_password';
    
    // Neue Optionen hinzufügen
    private $chat_activation_option_name = 'bw_chat_activation';
    private $chat_status_option_name = 'bw_chat_status';
    private $online_times_option_name = 'bw_chat_online_times';
    private $company_title_option_name = 'bw_chat_company_title';
    private $company_email_option_name = 'bw_chat_company_email';
    private $company_phone_option_name = 'bw_chat_company_phone';
    private $operator_name_option_name = 'bw_chat_operator_name';
    private $operator_image_option_name = 'bw_chat_operator_image';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
    }

    // Fügt das Plugin-Menü zur Admin-Seite hinzu
    public function admin_menu() {
        add_options_page(
            'BW Chat Einstellungen',
            'BW Chat',
            'manage_options',
            'bw-chat-settings',
            [$this, 'settings_page']
        );
    }

    // Initialisiert die Einstellungen
    public function admin_init() {
        register_setting('bw_chat_settings_group', $this->email_option_name);
        register_setting('bw_chat_settings_group', $this->reply_to_option_name);
        register_setting('bw_chat_settings_group', $this->imap_hostname_option_name);
        register_setting('bw_chat_settings_group', $this->imap_username_option_name);
        register_setting('bw_chat_settings_group', $this->imap_password_option_name);
        register_setting('bw_chat_settings_group', $this->smtp_enabled_option_name);
        register_setting('bw_chat_settings_group', $this->smtp_host_option_name);
        register_setting('bw_chat_settings_group', $this->smtp_username_option_name);
        register_setting('bw_chat_settings_group', $this->smtp_password_option_name);
        
        // Registriere die neuen Optionen
        register_setting('bw_chat_settings_group', $this->chat_activation_option_name);
        register_setting('bw_chat_settings_group', $this->chat_status_option_name);
        register_setting('bw_chat_settings_group', $this->online_times_option_name);
        register_setting('bw_chat_settings_group', $this->company_title_option_name);
        register_setting('bw_chat_settings_group', $this->company_email_option_name);
        register_setting('bw_chat_settings_group', $this->company_phone_option_name);
        register_setting('bw_chat_settings_group', $this->operator_name_option_name);
        register_setting('bw_chat_settings_group', $this->operator_image_option_name);

        // E-Mail Benachrichtigungen
        add_settings_section(
            'bw_chat_settings_section',
            'E-Mail Benachrichtigungen',
            null,
            'bw-chat-settings'
        );

        add_settings_field(
            'bw_chat_notification_email',
            'Send To',
            [$this, 'email_field_html'],
            'bw-chat-settings',
            'bw_chat_settings_section'
        );

        add_settings_field(
            'bw_chat_reply_to_email',
            'Send From',
            [$this, 'reply_to_field_html'],
            'bw-chat-settings',
            'bw_chat_settings_section'
        );

        // Übersicht
        add_settings_section(
            'bw_chat_overview_section',
            'Übersicht',
            null,
            'bw-chat-settings'
        );

        add_settings_field(
            'bw_chat_activation',
            'Chat aktivieren',
            [$this, 'chat_activation_field_html'],
            'bw-chat-settings',
            'bw_chat_overview_section'
        );

        add_settings_field(
            'bw_chat_status',
            'Chat Status',
            [$this, 'chat_status_field_html'],
            'bw-chat-settings',
            'bw_chat_overview_section'
        );

        // Profil
        add_settings_section(
            'bw_chat_profile_section',
            'Profil',
            null,
            'bw-chat-settings'
        );

        add_settings_field(
            'bw_chat_online_times',
            'Online Zeiten',
            [$this, 'online_times_field_html'],
            'bw-chat-settings',
            'bw_chat_profile_section'
        );

        add_settings_field(
            'bw_chat_company_title',
            'Titel',
            [$this, 'company_title_field_html'],
            'bw-chat-settings',
            'bw_chat_profile_section'
        );

        add_settings_field(
            'bw_chat_company_email',
            'Email',
            [$this, 'company_email_field_html'],
            'bw-chat-settings',
            'bw_chat_profile_section'
        );

        add_settings_field(
            'bw_chat_company_phone',
            'Telefon',
            [$this, 'company_phone_field_html'],
            'bw-chat-settings',
            'bw_chat_profile_section'
        );

        add_settings_field(
            'bw_chat_operator_name',
            'Operator Name',
            [$this, 'operator_name_field_html'],
            'bw-chat-settings',
            'bw_chat_profile_section'
        );

        add_settings_field(
            'bw_chat_operator_image',
            'Operator Profilbild',
            [$this, 'operator_image_field_html'],
            'bw-chat-settings',
            'bw_chat_profile_section'
        );

        // Mailserver
        add_settings_section(
            'bw_chat_mailserver_section',
            'Mailserver',
            null,
            'bw-chat-settings'
        );

        add_settings_field(
            'bw_chat_imap_hostname',
            'IMAP Host',
            [$this, 'imap_hostname_field_html'],
            'bw-chat-settings',
            'bw_chat_mailserver_section'
        );

        add_settings_field(
            'bw_chat_imap_username',
            'IMAP Benutzername',
            [$this, 'imap_username_field_html'],
            'bw-chat-settings',
            'bw_chat_mailserver_section'
        );

        add_settings_field(
            'bw_chat_imap_password',
            'IMAP Passwort',
            [$this, 'imap_password_field_html'],
            'bw-chat-settings',
            'bw_chat_mailserver_section'
        );

        add_settings_field(
            'bw_chat_smtp_enabled',
            'SMTP aktivieren',
            [$this, 'smtp_enabled_field_html'],
            'bw-chat-settings',
            'bw_chat_mailserver_section'
        );

        add_settings_field(
            'bw_chat_smtp_host',
            'SMTP Host',
            [$this, 'smtp_host_field_html'],
            'bw-chat-settings',
            'bw_chat_mailserver_section'
        );

        add_settings_field(
            'bw_chat_smtp_username',
            'SMTP Benutzername',
            [$this, 'smtp_username_field_html'],
            'bw-chat-settings',
            'bw_chat_mailserver_section'
        );

        add_settings_field(
            'bw_chat_smtp_password',
            'SMTP Passwort',
            [$this, 'smtp_password_field_html'],
            'bw-chat-settings',
            'bw_chat_mailserver_section'
        );
    }

    // HTML für das Eingabefeld der Benachrichtigungs-E-Mail (Send To)
    public function email_field_html() {
        $email = get_option($this->email_option_name);
        ?>
        <input type="text" name="<?php echo $this->email_option_name; ?>" value="<?php echo esc_attr($email); ?>" placeholder="Name <email@domain.com>" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld der Reply-To-E-Mail (Send From)
    public function reply_to_field_html() {
        $reply_to = get_option($this->reply_to_option_name);
        ?>
        <input type="text" name="<?php echo $this->reply_to_option_name; ?>" value="<?php echo esc_attr($reply_to); ?>" placeholder="Name <email@domain.com>" class="regular-text">
        <?php
    }

    // HTML für das Dropdown zur Aktivierung des Chats
    public function chat_activation_field_html() {
        $activation = get_option($this->chat_activation_option_name);
        ?>
        <select name="<?php echo $this->chat_activation_option_name; ?>">
            <option value="Nein" <?php selected($activation, 'Nein'); ?>>Nein</option>
            <option value="Ja" <?php selected($activation, 'Ja'); ?>>Ja</option>
        </select>
        <?php
    }

    // HTML für das Dropdown zum Status des Chats
    public function chat_status_field_html() {
        $status = get_option($this->chat_status_option_name);
        ?>
        <select name="<?php echo $this->chat_status_option_name; ?>">
            <option value="Offline" <?php selected($status, 'Offline'); ?>>Offline</option>
            <option value="Live" <?php selected($status, 'Live'); ?>>Live</option>
        </select>
        <?php
    }

    // HTML für das Eingabefeld der Online-Zeiten
    public function online_times_field_html() {
        $online_times = get_option($this->online_times_option_name);
        ?>
        <input type="text" name="<?php echo $this->online_times_option_name; ?>" value="<?php echo esc_attr($online_times); ?>" placeholder="MO-DO 9.00-12.00 und 14.00-16.00; FR 9.00-12.00" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld des Titels
    public function company_title_field_html() {
        $title = get_option($this->company_title_option_name);
        ?>
        <input type="text" name="<?php echo $this->company_title_option_name; ?>" value="<?php echo esc_attr($title); ?>" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld der Email
    public function company_email_field_html() {
        $email = get_option($this->company_email_option_name);
        ?>
        <input type="email" name="<?php echo $this->company_email_option_name; ?>" value="<?php echo esc_attr($email); ?>" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld der Telefonnummer
    public function company_phone_field_html() {
        $phone = get_option($this->company_phone_option_name);
        ?>
        <input type="text" name="<?php echo $this->company_phone_option_name; ?>" value="<?php echo esc_attr($phone); ?>" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld des Operator-Namens
    public function operator_name_field_html() {
        $operator_name = get_option($this->operator_name_option_name);
        ?>
        <input type="text" name="<?php echo $this->operator_name_option_name; ?>" value="<?php echo esc_attr($operator_name); ?>" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld des Operator-Profilbildes
    public function operator_image_field_html() {
        $operator_image = get_option($this->operator_image_option_name);
        ?>
        <input type="text" id="operator_image" name="<?php echo $this->operator_image_option_name; ?>" value="<?php echo esc_attr($operator_image); ?>" class="regular-text">
        <button type="button" class="upload_image_button button">Bild auswählen</button>
        <button type="button" class="remove_image_button button">Bild entfernen</button>
        <script type="text/javascript">
            jQuery(document).ready(function($){
                var mediaUploader;
                $('.upload_image_button').click(function(e) {
                    e.preventDefault();
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: 'Wähle ein Profilbild',
                        button: {
                            text: 'Bild auswählen'
                        }, multiple: false });
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#operator_image').val(attachment.url);
                    });
                    mediaUploader.open();
                });
                $('.remove_image_button').click(function() {
                    $('#operator_image').val('');
                });
            });
        </script>
        <?php
    }

    // HTML für das Eingabefeld des IMAP Hostnames
    public function imap_hostname_field_html() {
        $hostname = get_option($this->imap_hostname_option_name);
        ?>
        <input type="text" name="<?php echo $this->imap_hostname_option_name; ?>" value="<?php echo esc_attr($hostname); ?>" placeholder="imap.domain.com" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld des IMAP Benutzernamens
    public function imap_username_field_html() {
        $username = get_option($this->imap_username_option_name);
        ?>
        <input type="text" name="<?php echo $this->imap_username_option_name; ?>" value="<?php echo esc_attr($username); ?>" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld des IMAP Passworts
    public function imap_password_field_html() {
        $password = get_option($this->imap_password_option_name);
        ?>
        <input type="password" name="<?php echo $this->imap_password_option_name; ?>" value="<?php echo esc_attr($password); ?>" class="regular-text">
        <?php
    }

    // HTML für das Dropdown zur Aktivierung von SMTP
    public function smtp_enabled_field_html() {
        $enabled = get_option($this->smtp_enabled_option_name);
        ?>
        <select name="<?php echo $this->smtp_enabled_option_name; ?>">
            <option value="Nein" <?php selected($enabled, 'Nein'); ?>>Nein</option>
            <option value="Ja" <?php selected($enabled, 'Ja'); ?>>Ja</option>
        </select>
        <?php
    }

    // HTML für das Eingabefeld des SMTP Hosts
    public function smtp_host_field_html() {
        $smtp_host = get_option($this->smtp_host_option_name);
        ?>
        <input type="text" name="<?php echo $this->smtp_host_option_name; ?>" value="<?php echo esc_attr($smtp_host); ?>" placeholder="smtp.domain.com:587/tls" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld des SMTP Benutzernamens
    public function smtp_username_field_html() {
        $smtp_username = get_option($this->smtp_username_option_name);
        ?>
        <input type="text" name="<?php echo $this->smtp_username_option_name; ?>" value="<?php echo esc_attr($smtp_username); ?>" class="regular-text">
        <?php
    }

    // HTML für das Eingabefeld des SMTP Passworts
    public function smtp_password_field_html() {
        $smtp_password = get_option($this->smtp_password_option_name);
        ?>
        <input type="password" name="<?php echo $this->smtp_password_option_name; ?>" value="<?php echo esc_attr($smtp_password); ?>" class="regular-text">
        <?php
    }

    // Rendert die Einstellungsseite
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>BW Chat Einstellungen</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('bw_chat_settings_group');
                do_settings_sections('bw-chat-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
