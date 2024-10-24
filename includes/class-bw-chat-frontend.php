<?php

class BW_Chat_Frontend {

    private $use_custom_smtp = false;

    public function __construct() {
//        $this->session_key = session_id();
        add_action('wp_loaded', ['BW_Chat_Helper', 'create_sessioncookie_id'], 1); // Setzt das Cookie frühzeitig
//        add_action('init', [$this, 'initialize_session'], 1); // Füge die Session-Initialisierung hinzu
//    add_action('init', ['BW_Chat_Helper', 'my_session_id'], 1); // Ruft die Session-Initialisierung direkt auf

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        
        // Registriere die AJAX-Aktionen für angemeldete Benutzer
        add_action('wp_ajax_check_reply_emails', [$this, 'check_reply_emails']);
        add_action('wp_ajax_handle_ajax_form_start', [$this, 'handle_ajax_form_start']);
        add_action('wp_ajax_handle_ajax_form_userinput', [$this, 'handle_ajax_form_userinput']);
        add_action('wp_ajax_handle_ajax_form_contact', [$this, 'handle_ajax_form_contact']);
        
        // Registriere die AJAX-Aktionen für nicht angemeldete Benutzer
        add_action('wp_ajax_nopriv_check_reply_emails', [$this, 'check_reply_emails']);
        add_action('wp_ajax_nopriv_handle_ajax_form_start', [$this, 'handle_ajax_form_start']);
        add_action('wp_ajax_nopriv_handle_ajax_form_userinput', [$this, 'handle_ajax_form_userinput']);
        add_action('wp_ajax_nopriv_handle_ajax_form_contact', [$this, 'handle_ajax_form_contact']);
        
        // Shortcode-Registrierung
        add_shortcode('bw_chat_form', [$this, 'template_chat']);
        
        // Konfiguriere PHPMailer für SMTP
        add_action('phpmailer_init', [$this, 'configure_phpmailer']);
        
        
    }



/*
    public function initialize_session() {
        BW_Chat_Helper::my_session_id(); // Ruft my_session_id() auf, bevor die Ausgabe beginnt
    }
*/


public function enqueue_scripts() {
    // Lade das jQuery-Skript        
    wp_enqueue_style('bw-billing-frontend', plugin_dir_url(__FILE__) . '../assets/frontend-styles.css');
    wp_enqueue_script('bw-billing-frontend', plugin_dir_url(__FILE__) . '../assets/frontend-scripts.js', array('jquery'), null, true);
    wp_enqueue_script('ajax-form', plugin_dir_url(__FILE__) . '../assets/ajax-form.js', array('jquery'), null, true);

    // Übergabe der PHP-Variablen an das JavaScript
    wp_localize_script('ajax-form', 'ajax_form_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ajax-form-nonce'),  // Nonce für heck_reply_emails
        'nonce_start'    => wp_create_nonce('ajax-form-start-nonce'),  // Nonce für Start-Formular
        'nonce_contact'  => wp_create_nonce('ajax-form-contact-nonce'), // Nonce für Kontaktformular
        'nonce_userinput' => wp_create_nonce('ajax-form-userinput-nonce'), // Nonce für Benutzereingaben
        'session_id' => BW_Chat_Helper::my_session_id(),
        'is_chat_live' => BW_Chat_Helper::is_bw_chat_live(),
        'is_chat_personolize' => BW_Chat_Helper::is_bw_chat_personolize(),
        'post_sessionid' => BW_Chat_Helper::is_bw_chat_sessionid_post(),
    ));
}






/*

    - handle_ajax_form_start()
    - handle_ajax_form_contact()
    - handle_ajax_form_userinput()
    
    - send_email_notification()
    
    - template_chat()
    - - templatepart_chat_button()
    - - templatepart_chat_canvas()
    
    - - beforechat_form_contact()
    - - beforechat_form_start()
*/






    public function check_reply_emails() {
      // Überprüfen des Nonce
      check_ajax_referer('ajax-form-nonce', 'security');
        // Überprüfen, ob das Cookie gesetzt ist

        $session_key = BW_Chat_Helper::my_session_id();
//        error_log($session_key);
        if ($session_key) {
            // SMTP/IMAP Serverdaten aus den Plugin-Optionen
            $hostname = get_option('bw_chat_imap_hostname');
            $username = get_option('bw_chat_imap_username');
            $password = get_option('bw_chat_imap_password');

            // Verbindung zum IMAP-Server herstellen
            $inbox = imap_open($hostname, $username, $password) or die('Cannot connect to IMAP server: ' . imap_last_error());

            // Alle ungelesenen E-Mails durchsuchen, die die Session-ID im Text enthalten
            $emails = imap_search($inbox, 'UNSEEN BODY "' . $session_key . '"');
            //error_log($session_key);

            if ($emails) {
                foreach ($emails as $email_number) {
                    $overview = imap_fetch_overview($inbox, $email_number, 0);
                    $message = imap_fetchbody($inbox, $email_number, 1);

                    // Extrahiere den geantworteten Text aus der Nachricht
                    $replied_text = BW_Chat_Helper::extract_replied_text($message);

                    if ($replied_text) {
                        // Holen Sie sich den zugehörigen Post basierend auf der Session-ID
                        $existing_post = BW_Chat_Helper::get_post_by_session_key($session_key);

                        if ($existing_post) {
                            $post_id = $existing_post->ID;
                            $current_timestamp = current_time('timestamp');

                            // Erstelle das benutzerdefinierte Feld für die Administrator-Eingabe
                            $meta_key = BW_Chat_Helper::cf_admin_meta_key($current_timestamp);
                            add_post_meta($post_id, $meta_key, sanitize_text_field($replied_text));
                            //error_log($meta_key);

                            // Markiere die E-Mail als gelesen
                            imap_setflag_full($inbox, $email_number, "\\Seen");
                        }
                    }
                }
            }

            // Verbindung zum IMAP-Server schließen
            imap_close($inbox);

            // Erfolgsantwort senden
            wp_send_json_success('E-Mails erfolgreich überprüft.');
        } else {
            wp_send_json_error('Keine Emails Vorhanden.');
        }
        
        wp_die();
    }
    
    
    
    
    

    public function handle_ajax_form_start() {
        // Überprüfe die Nonce, um sicherzustellen, dass die Anfrage gültig ist
        check_ajax_referer('ajax-form-start-nonce', 'security');
        // Starte die Session und erhalte den Session Key
        $session_key = BW_Chat_Helper::my_session_id();
        $is_chat_personolize = BW_Chat_Helper::is_bw_chat_personolize();

        // Suche nach einem bestehenden Post mit dem Session-Key
        $existing_post = BW_Chat_Helper::get_post_by_session_key($session_key);
        
        // Wenn kein bestehender Post gefunden wurde, erstelle einen neuen
        if (!$existing_post) {
            $post_id = wp_insert_post(array(
                'post_title' => $session_key,
                'post_status' => 'publish',
                'post_type' => 'bw-chat'
            ));
        } else {
            $post_id = $existing_post->ID;
        }

        if ($is_chat_personolize) {
            // Prüfe, ob die Felder übergeben wurden
            if (!isset($_POST['bw-chat-userprofile-name']) || !isset($_POST['bw-chat-userprofile-email'])) {
                wp_send_json_error('Fehlende Felder.');
                wp_die();
            }
        
            // Sanitize die Eingabedaten
            $name = sanitize_text_field($_POST['bw-chat-userprofile-name']);
            $email = sanitize_text_field($_POST['bw-chat-userprofile-email']);
            
        }
        
    
    
        // Wenn der Post erfolgreich erstellt oder gefunden wurde
        if ($post_id) {
            // Aktualisiere oder füge die benutzerdefinierten Felder hinzu
            if ($is_chat_personolize) {
                update_post_meta($post_id, 'bw-chat-userprofile-name', $name);
                update_post_meta($post_id, 'bw-chat-userprofile-email', $email);
            }
            update_post_meta($post_id, 'bw-chat-userprofile-sessionid', $session_key);
            
            // Sende die Erfolgsmeldung
            $response = $this->response_ajax_form_start(); // Stelle sicher, dass diese Funktion existiert!
            wp_send_json_success($response);
        } else {
            // Fehlerfall: Sende eine Fehlermeldung, wenn der Post nicht erstellt werden konnte
            wp_send_json_error('Es gab einen Fehler beim Speichern Ihrer Nachricht.');
        }
    
        wp_die(); // Beendet die AJAX-Anfrage korrekt
    }



public function response_ajax_form_start() { 
    
    $post = BW_Chat_Helper::get_post_by_session_key(BW_Chat_Helper::my_session_id());
    $post_id = $post->ID;
    $is_chat_personolize = BW_Chat_Helper::is_bw_chat_personolize();
    $name = BW_Chat_Helper::get_bw_chat_personolize_username($post_id);
    ob_start();
    ?>
        Sch&ouml;n, dass Sie da sind. Sie k&ouml;nnen uns gerne eine Nachricht schreiben, w&auml;hrend wir uns für Sie bereit machen.
    <?php 
    return ob_get_clean();
}
        




    
public function handle_ajax_form_contact() {
    // Überprüfe den Nonce für Sicherheit
    check_ajax_referer('ajax-form-contact-nonce', 'security');
    // Sanitize und validiere die Eingabedaten
    $name = sanitize_text_field($_POST['bw-chat-contact-name']);
    $email = sanitize_email($_POST['bw-chat-contact-email']);
    $subject = sanitize_text_field($_POST['bw-chat-contact-subject']);
    $message_body = sanitize_textarea_field($_POST['bw-chat-contact-message']);
    $privacy = isset($_POST['bw-chat-contact-privacy']) ? sanitize_text_field($_POST['bw-chat-contact-privacy']) : '';

    // Prüfen, ob die Datenschutzerklärung akzeptiert wurde
    if (!$privacy) {
        wp_send_json_error('Bitte stimmen Sie der Datenschutzerklärung zu.');
    }

    // E-Mail versenden
    $mail_sent = $this::send_custom_wp_mail($message_body, $subject);

    // Beispiel für eine Antwort zum Testen
    $response = array(
        'message' => 'Die Nachricht wurde erfolgreich versendet! Wir melden uns in Kürze bei Ihnen.',
    );
        
    if ($mail_sent) {
        wp_send_json_success($response);
    } else {
        wp_send_json_error('Es gab einen Fehler beim Senden der Nachricht.');
    }

    wp_die(); // Beende die AJAX-Verarbeitung
}







public function handle_ajax_form_userinput() {
    // Überprüft den Nonce, um sicherzustellen, dass die Anfrage sicher ist
    check_ajax_referer('ajax-form-userinput-nonce', 'security');

    // Sanitize der Benutzereingabe, um Sicherheitsprobleme wie XSS zu verhindern
    $chat_entry = isset($_POST['chat-userinput-entry']) ? sanitize_text_field($_POST['chat-userinput-entry']) : false;   
    
    // Startet die Session und erhält den Session-Schlüssel
    $session_key = BW_Chat_Helper::my_session_id();
    
    // Holt den aktuellen Zeitstempel in GMT (kann je nach Bedarf angepasst werden)
    $current_timestamp = current_time('timestamp');

    // Sucht nach einem bestehenden Post anhand des Session-Schlüssels
    $existing_post = BW_Chat_Helper::get_post_by_session_key($session_key);
    
    // Überprüft, ob ein Post gefunden wurde und ob die ID existiert
    if ($existing_post && isset($existing_post->ID)) {
        // Weist die Post-ID der Variable zu
        $post_id = $existing_post->ID;

        // Generiert den Meta-Schlüssel mit dem Zeitstempel
        $meta_key = BW_Chat_Helper::cf_user_meta_key($current_timestamp);

       if ($chat_entry) {
            // Fügt ein benutzerdefiniertes Feld mit dem Session-Schlüssel, Meta-Schlüssel und der Benutzereingabe hinzu
            BW_Chat_Helper::add_chat_custom_field($session_key, $meta_key, $chat_entry);
            
            // Sendet eine E-Mail-Benachrichtigung an den Administrator mit der Benutzereingabe und dem Session-Schlüssel
            $this->send_email_notification($chat_entry, $session_key);
        }
        
        // Formatiert den Chat-Inhalt für die Ausgabe
        $formatted_content = BW_Chat_Helper::format_CF_content($post_id);
        // Gibt die formatierten Daten als JSON-Antwort zurück und signalisiert Erfolg
        $response = array(
            'message' => $formatted_content
        );
        wp_send_json_success($response);
    } else {
        // Sendet eine Fehlermeldung zurück, falls kein Post gefunden wurde
        wp_send_json_error('Es gab einen Fehler beim Speichern Ihrer Nachricht.');
    }

    // Beendet die AJAX-Verarbeitung sicher
    wp_die();
}




public function send_email_notification($name, $session_key) {
    $existing_post = BW_Chat_Helper::get_post_by_session_key($session_key);
    $is_chat_personolize = BW_Chat_Helper::is_bw_chat_personolize(); // Überprüft, ob der Chat live ist


    if ($existing_post) {
        $post_id = $existing_post->ID;
        $meta_keys = get_post_meta($post_id);
//        $user_name = BW_Chat_Helper::get_post_meta_from_sessionid('bw-chat-userprofile-name');
        // Benutzerinformationen abrufen
        $username = BW_Chat_Helper::get_bw_chat_personolize_username($post_id); // Ruft den Namen des Benutzers ab
        $adminname = get_option('bw_chat_operator_name');  // Operator Name
        $date_format = get_option( 'date_format' );
        $post_create_date = get_the_date( 'H:i d.M', $post_id );
        if ($is_chat_personolize) {
            $subject = 'Chat mit ' . $user_name;            
        } else {
            $subject = 'Chat vom ' . $post_create_date;            
            
        }
        $message_body = "Bisheriger Chat-Verlauf:\n\n";


        // Verwende cf_admin_meta_key und cf_user_meta_key, um die Metadaten zu filtern
        $meta_key_admin = BW_Chat_Helper::cf_admin_meta_key();
        $meta_key_user = BW_Chat_Helper::cf_user_meta_key();

        // Erstelle ein Array, um die gefilterten Meta-Daten zu speichern
        $filtered_meta_keys = [];

        // Filtere die relevanten Meta-Daten
        foreach ($meta_keys as $key => $values) {
            if (strpos($key, $meta_key_admin) === 0 || strpos($key, $meta_key_user) === 0) {
                $filtered_meta_keys[$key] = $values;
            }
        }

        // Kehre die Reihenfolge der gefilterten Meta-Daten um
        $filtered_meta_keys = array_reverse($filtered_meta_keys, true);

        // Verarbeite die umgekehrten Meta-Daten
        foreach ($filtered_meta_keys as $key => $values) {
            $timestamp = str_replace([$meta_key_admin, $meta_key_user], '', $key);
            $time = BW_Chat_Helper::format_time($timestamp);
            $datetime = BW_Chat_Helper::format_date($timestamp, 'd.m H:i');
            foreach ($values as $value) {
                $formatted_value = esc_html($value);
                $type = BW_Chat_Helper::get_chat_type($key);
                if( $type=='admin' ? $name=$adminname : $name=$username);
                // Formatierter Chat-Eintrag
                $message_body .= "{$datetime} | {$name} | {$formatted_value}\n\n";

            }
        }
    }

    $message_body .= "\nNachrichten ID: " . $session_key;

    return $this::send_custom_wp_mail($message_body, $subject);
}








public function send_custom_wp_mail($message_body, $subject) {
    
    $send_to_raw = get_option('bw_chat_notification_email');
    $send_from_raw = get_option('bw_chat_reply_to_email');
    $send_to = BW_Chat_Helper::extract_email_address($send_to_raw);
    $send_to_name = BW_Chat_Helper::extract_name($send_to_raw);
    $send_from = BW_Chat_Helper::extract_email_address($send_from_raw);
    $send_from_name = BW_Chat_Helper::extract_name($send_from_raw);




    // SMTP aktivieren, falls konfiguriert
    $smtp_enabled = get_option('bw_chat_smtp_enabled');
    if ($smtp_enabled === 'Ja') {
        $this->use_custom_smtp = true;
    }

    $headers = array('Content-Type: text/plain; charset=UTF-8');
    if ($send_from) {
        $headers[] = 'From: ' . $send_from_name . ' <' . $send_from . '>';
    }

    $mail_sent = wp_mail($send_to, $subject, $message_body, $headers);

    // SMTP wieder deaktivieren
    $this->use_custom_smtp = false;

    return $mail_sent;
    
}







public function configure_phpmailer($phpmailer) {
    if ($this->use_custom_smtp) {
        $smtp_host_config = get_option('bw_chat_smtp_host');
        $smtp_host = '';
        $smtp_port = 587;
        $smtp_secure = 'tls';

        if ($smtp_host_config) {
            $host_parts = explode(':', $smtp_host_config);
            $smtp_host = $host_parts[0];

            if (isset($host_parts[1])) {
                $port_secure = explode('/', $host_parts[1]);
                $smtp_port = isset($port_secure[0]) ? $port_secure[0] : 587;
                $smtp_secure = isset($port_secure[1]) ? $port_secure[1] : 'tls';
            }
        }

        $phpmailer->isSMTP(); // Verwende SMTP
        $phpmailer->Host = $smtp_host; // SMTP-Server Adresse
        $phpmailer->SMTPAuth = true; // Aktiviere SMTP Authentifizierung
        $phpmailer->Port = $smtp_port; // SMTP Port
        $phpmailer->SMTPSecure = ($smtp_secure === 'ssl') ? 'ssl' : 'tls'; // Verschlüsselung (SSL/TLS)
        $phpmailer->Username = get_option('bw_chat_smtp_username'); // SMTP Benutzername
        $phpmailer->Password = get_option('bw_chat_smtp_password'); // SMTP Passwort

        // Optional: Debugging aktivieren
        $phpmailer->SMTPDebug = 0; // Stufe 2 für detailliertes Debugging
        $phpmailer->Debugoutput = function($str, $level) {
            error_log("SMTP Debug: " . $str);
        };
    }
}








    
 
 
 

/**
 * Template für den Chat, welches den Chatbutton und/oder das Chatfenster rendert.
 */
public function template_chat() {
    ob_start();
    $is_chat_acivation = BW_Chat_Helper::is_bw_chat_active(); // Überprüft, ob der Chat aktiv ist
    $is_session_id = BW_Chat_Helper::is_session_id(); // Überprüft, ob eine Chat-Sitzung existiert
    $is_chat_live = BW_Chat_Helper::is_bw_chat_live(); // Überprüft, ob der Chat live ist    ob_start(); 
    $is_admin_logged_in = is_user_logged_in() && current_user_can('administrator'); // Überprüft, ob ein Administrator eingeloggt ist

    // Zeigt den Chat-Button und das Chat-Fenster nur an, wenn der Chat aktiviert ist und keine Sitzung existiert
    if ($is_chat_acivation && $is_admin_logged_in ) { 
        echo self::templatepart_chatbutton();
        echo self::templatepart_chatcanvas();
    } 
    // Wenn der Chat aktiv ist und eine Sitzung existiert, zeige direkt das Chat-Fenster an
    elseif ($is_chat_acivation ) {
        echo self::templatepart_chatcanvas();
    }

    return ob_get_clean(); // Gibt den Inhalt des Output Buffers zurück
}

private function templatepart_chatbutton() {
    // Rendert den Button mit dem Operator-Bild
    $url = get_option('bw_chat_operator_image');
    $img = wp_get_attachment_image( attachment_url_to_postid($url), 'medium' );
    $img_url = plugin_dir_url(__DIR__)."assets/chat-icon.svg";
    $img = "<img alt='chat-button-icon' src='{$img_url}' />";
    $session_key = BW_Chat_Helper::my_session_id(); // Überprüft, ob eine Chat-Sitzung existiert
    $output = "<div id='bw-chat-button' class='chat-show'>{$img}</div>";
    
    return $output;
    
}    

    
/**
 * Rendert das Haupt-Chatfenster (Chat-Canvas) mit Profilinformationen und dem Chatbereich.
 */
private function templatepart_chatcanvas() {
    $profile_company = get_option('bw_chat_company_title'); // Holt den Titel des Unternehmens
    $profile_name = get_option('bw_chat_operator_name'); 
    $profile_phone = get_option('bw_chat_company_phone');
    $profile_email = get_option('bw_chat_company_email');
    $profile_name_link = "<a href='mailto:{$profile_email}' >{$profile_name}</a>";
    $profile_company_link = "<a href='mailto:{$profile_email}' >{$profile_company}</a>";
    $profile_imgurl = get_option('bw_chat_operator_image'); 
    $img = wp_get_attachment_image( attachment_url_to_postid($profile_imgurl), 'thumbnail' );
    
    ob_start(); ?>
    <div id="bw-chat-window" class="chat-hidden">
        <div id="bw-chat-header">
            <div class="profile-wrap">
                <span id="profile-img" ><?php echo $img;?></span>       
                <div class="wrap">
                    <strong id="profile-name" ><?php echo $profile_name_link; ?></strong>
                    <span id="profile-company" ><?php echo $profile_company_link; ?></span>
                </div>
            </div>
    
            <div class="icon-wrap">
                <a href='https://wa.me/<?php echo $profile_phone; ?>'><img id="profile-contact-whatsapp" src="<?php echo plugin_dir_url(__DIR__). 'assets/whatapp-icon.svg'; ?>" alt="Whatsapp"/></a>
                <a href='mailto:<?php echo $profile_email; ?>'><img id="profile-contact-mail" src="<?php echo plugin_dir_url(__DIR__).'assets/mail-icon.svg'; ?>" alt="Email"/></a>
            </div>
<!--             <img id="bw-chat-close" src="<?php echo plugin_dir_url(__DIR__).'assets/close.svg'; ?>" alt="Close" /> -->
            <div id="bw-chat-close"> <span>Chat Schließen</span> <img src="<?php echo plugin_dir_url(__DIR__).'assets/close.svg'; ?>" alt="Close" /></div>

        </div>
        
        <!-- Chat-Canvas Bereich -->
        <div id="chat-canvas" class="">
            <?php  echo self::templatepart_chatcanvas_questions(); ?>

            <div id="chat-items" class="chat-hidden">
                <?php //  AJAX CALL templatepart_ajax_chatitems(); ?>
            </div>

        </div>
        <div id="bw-chat-form">
            <?php  echo self::templatepart_ajax_userinput(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean(); // Gibt den gerenderten Chat-Canvas zurück
}
    













    
/**
 * Rendert das Chatfragen-Formular oder das Kontaktformular, je nachdem, ob der Chat aktiv ist.
 */
private function template_text_createchat() {

    $is_chat_personolize = BW_Chat_Helper::is_bw_chat_personolize(); // Überprüft, ob der Chat live ist
    $title = get_option('bw_chat_company_title');
    if ($is_chat_personolize) {
        $personalized_text = "Um Ihnen bestmöglich weiterzuhelfen, geben Sie bitte Ihren Namen und Ihre E-Mail-Adresse an.";
    } else {
        $personalized_text = false;
    }
    ob_start(); 
    echo "<p class='template_text'>Willkommen in unserem Chat. <br /> Wir freuen uns auf Ihre Anfrage! {$personalized_text}</p>";
    return ob_get_clean(); // Gibt den gerenderten Chat-Canvas zurück
}  

private function template_text_contactform() {
    $display_onlinetimes = false;
    $content_onlinetimes = ($display_onlinetimes ? '<br /> Unsere regulären Chat-Onlinezeiten sind: '.get_option('bw_chat_online_times') : false);
    ob_start(); ?>
    <p class="template_text">
    <strong>Chat momentan nicht besetzt</strong> <br />
    Vielen Dank für Ihr Interesse! Der Chat ist aktuell nicht verfügbar. <?php echo $content_onlinetimes; ?> <br/>
    Bitte nutzen Sie das Kontaktformular, wir melden uns schnellstmöglich bei Ihnen.<br />
    Vielen Dank, wir freuen uns auf Ihre Anfrage!
    </p>
    <?php
    return ob_get_clean(); // Gibt den gerenderten Chat-Canvas zurück
}  





public function templatepart_chatcanvas_questions() {
    ob_start(); 
    $is_session_id = BW_Chat_Helper::is_session_id(); // Überprüft, ob eine Sitzung existiert
    $is_chat_live = BW_Chat_Helper::is_bw_chat_live(); // Überprüft, ob der Chat live ist
    $is_chat_personolize = BW_Chat_Helper::is_bw_chat_personolize(); // Überprüft, ob der Chat live ist
    $session_postid = BW_Chat_Helper::is_bw_chat_sessionid_post(); // Überprüft, ob ein post zum der Session existiert
    $response_form_start= self::response_ajax_form_start();
    ?>

        <?php  if ($is_chat_live) { ?>            
        <div id="bw-chat-step-createchat" data-chat-admin class="chat-item">
            <?php echo self::template_text_createchat();?>
            
            <form id="ajax-form-createchat" method="post"> 
                <?php  if ($is_chat_personolize) { ?>            
                <div data-form-wrap >
                    <input data-form-style-w50 type="text" id="bw-chat-userprofile-name" name="bw-chat-userprofile-name"  placeholder="Name" required />
                    <input data-form-style-w50 type="email" id="bw-chat-userprofile-email" name="bw-chat-userprofile-email"  placeholder="Email" required />
                </div>
                <?php } ?>
                <div data-form-wrap >
                    <label data-form-style-w100 for="privacy">
                        <input type="checkbox" id="bw-chat-userprofile-privacy" name="bw-chat-userprofile-privacy" required />
                        Ich stimme der Verarbeitung im Rahmen der DSGVO zu. 
                    </label>
                </div>
                <input type="submit" value="Start">
            </form>
            <div id="ajax-form-step1-result"></div>
        </div>
        <?php  } ?>            
        <?php  if (!$is_chat_live) { ?>            
        
        <div id="bw-chat-step-contactform" class="bw-chat-noticebox chat-hidden">
            <?php echo self::template_text_contactform();?>
            <form id="ajax-form-contactform" method="post">
                <div data-form-wrap >
                    <input data-form-style-w50 type="text" id="bw-chat-contact-name" name="bw-chat-contact-name"  placeholder="Name" required />
                    <input data-form-style-w50 type="email" id="bw-chat-contact-email" name="bw-chat-contact-email"  placeholder="Email" required />
                </div>
                <div data-form-wrap >
                    <input data-form-style-w100 type="text" id="bw-chat-contact-subject" name="bw-chat-contact-subject"  placeholder="Betreff" required />
                </div>
                <div data-form-wrap >
                    <textarea data-form-style-w100 rows="5" id="bw-chat-contact-message" name="bw-chat-contact-message"  placeholder="Nachricht" required></textarea>
                </div>
                <div data-form-wrap >
                    <label data-form-style-w100 for="privacy">
                        <input type="checkbox" id="privacy" name="bw-chat-contact-privacy" required />
                    Ich stimme der Datenschutzerklärung zu 
                    </label>
                </div>

                <input type="submit" value="Abschicken">
            </form>
            <div id="bw-chat-contactform-result"></div>
        </div>
        <?php  } ?>            
        
    <?php 
    // Zeigt eine Begrüßungsnachricht an, wenn der Chat live ist und eine Sitzung existiert
//    } elseif ($is_session_id && $is_chat_live) { 
    ?>

        <div id="bw-chat-step-welcometext" data-chat-admin class="chat-item chat-hidden">
            <?php if ($session_postid) echo self::response_ajax_form_start(); ?>
        </div>

    <?php 
//    } 
    ?>
<!--     <hr /> -->
    <?php 
    return ob_get_clean(); // Gibt den gerenderten Inhalt zurück
}    
    
    
    


/**
 * Template für die Benutzereingabe im Chat.
 */
public function templatepart_ajax_userinput() {
    $session_postid = BW_Chat_Helper::is_bw_chat_sessionid_post(); // Überprüft, ob ein post zum der Session existiert
    $is_session_id = BW_Chat_Helper::is_session_id(); // Überprüft, ob eine Sitzung existiert
    $is_chat_live = BW_Chat_Helper::is_bw_chat_live(); // Überprüft, ob der Chat live ist
    if (!$is_chat_live ) return false;
    ob_start(); ?>
    <div id="bw-chat-userinput">
        <form id="ajax-form-userinput" method="post">
<!--             <textarea id="chat-userinput-entry" name="chat-entry" placeholder="..." rows="1" disabled></textarea> -->
           <input type="text" id="chat-userinput-entry" class="chat-hidden" name="chat-entry" placeholder="Gib deine Nachricht ein...">
           <input type="submit" id="chat-userinput-submit" class="chat-hidden" value="Abschicken">
        </form>
        <div id="bw-chat-userinput-result"></div>
    </div>
    <?php
    return ob_get_clean(); // Gibt das Benutzereingabe-Formular zurück
}









}
