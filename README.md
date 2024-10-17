# bw_chat

## Todo 
- input form erst einblenden wenn startform ausgefüllt.
- x button aktivieren
- farben von admin /user tauschen
- 2. textnachricht auch persionalisieren / weglassen
- chatfensterbreite
- input submit icon drehen
- emojis icludieren



## Abhängigkeiten der Klassen und Funktionen im Plugin

| Klasse              | Funktion                            | Abhängigkeiten                          |
|---------------------|-------------------------------------|-----------------------------------------|
| **BW_Chat_WordPress** |                                     |                                         |
|                     | public function create_bw_chat_cpt  |                                         |
|                     | public function enqueue_scripts     | BW_Chat_Helper::get_post_by_session_key |
|                     | public function enable_native_custom_fields |                                 |
|                     |                                     |                                         |
|  **BW_Chat_Frontend** |                                     |                                         |
|                     | public function handle_ajax_form    | BW_Chat_Helper::get_post_by_session_key |
|                     |                                     | BW_Chat_Helper::cf_user_meta_key        |
|                     |                                     | BW_Chat_Helper::add_chat_custom_field   |
|                     |                                     | $this->send_email_notification          |
|                     |                                     | BW_Chat_Helper::format_CF_content       |
|                     | public function check_chat_custom_fields | BW_Chat_Helper::get_post_by_session_key |
|                     |                                     | BW_Chat_Helper::format_CF_content       |
|                     | private function send_email_notification | BW_Chat_Helper::extract_email_address   |
|                     |                                     | BW_Chat_Helper::extract_name            |
|                     |                                     | BW_Chat_Helper::get_post_by_session_key |
|                     |                                     | BW_Chat_Helper::cf_admin_meta_key       |
|                     |                                     | BW_Chat_Helper::cf_user_meta_key        |
|                     |                                     | BW_Chat_Helper::format_time             |
|                     |                                     | BW_Chat_Helper::format_date             |
|                     | public function configure_phpmailer |                                         |
|                     | public function render_chat_form    |                                         |
|                     |                                     |                                         |
| **BW_Chat_Plugin_Page** |                                     |                                         |
|                     | public function admin_menu          |                                         |
|                     | public function admin_init          |                                         |
|                     | public function email_field_html    |                                         |
|                     | public function reply_to_field_html    |                                         |
|                     | public function chat_activation_field_html    |                                         |
|                     | public function chat_status_field_html   |                                         |
|                     | public function online_times_field_html    |                                         |
|                     | public function company_title_field_html   |                                         |
|                     | public function company_email_field_html    |                                         |
|                     | public function company_phone_field_html    |                                         |
|                     | public function operator_name_field_html   |                                         |
|                     | public function operator_image_field_html    |                                         |
|                     | public function imap_hostname_field_html    |                                         |
|                     | public function imap_username_field_html   |                                         |
|                     | public function imap_password_field_html    |                                         |
|                     | public function smtp_enabled_field_html    |                                         |
|                     | public function smtp_host_field_html    |                                         |
|                     | public function smtp_username_field_html    |                                         |
|                     | public function smtp_password_field_html    |                                         |
|                     | public function settings_page       |                                         |
|                     |                                     |                                         |
| **BW_Chat_Email_Check** |                                     |                                         |
|                     | public function check_reply_emails  | BW_Chat_Helper::extract_replied_text    |
|                     |                                     | BW_Chat_Helper::get_post_by_session_key |
|                     |                                     | BW_Chat_Helper::cf_admin_meta_key       |
|                     |                                     | BW_Chat_Helper::add_chat_custom_field   |
|                     |                                     |                                         |
| **BW_Chat_Helper**   |                                     |                                         |
|                     |  public static function add_chat_custom_field |   self::get_post_by_session_key   |
|                     |  public static function get_post_by_session_key |                                         |
|                     |  public static function is_bw_chat_live         |    self::onlinetime_is_current_time   |
|                     |  public static function onlinetime_is_current_time  |  self::onlinetime_parse   |
|                     |  public static function onlinetime_parse          | self::onlinetime_expand_day_range |
|                     |  private static function onlinetime_expand_day_range  |                                         |
|                     |  public static function extract_email_address   |                                         |
|                     |  public static function extract_name  |                                         |
|                     |  public static function extract_replied_text   |                                         |
|                     | public static function format_CF_content       |                                         |
|                     |                                     |  self::cf_user_meta_key      |
|                     |                                     |  self::cf_admin_meta_key      |
|                     |                                     |  self::post_meta_values      |
|                     |                                     |  self::format_chat_item      |
|                     | public static function format_date   |                                         |
|                     | public static function format_time   |                                         |
|                     | public static function cf_user_meta_key |                                         |
|                     | public static function cf_admin_meta_key |                                         |
|                     | public static function post_meta_values |                            |
|                     | public static function get_chat_userdata |                            |
|                     | public static function get_chat_userdata_salutation |                            |
|                     | public static function get_chat_userdata_name |                            |
|                     | public static function get_chat_userdata_email |                            |
|                     | public static function post_meta_values |                            |
|                     | public static function format_chat_item | self::cf_user_meta_key        |
|                     |                                     | self::cf_admin_meta_key       |
|                     |                                     | self::format_time             |
|                     |                                     | self::format_date             |
|                     |                                     | self::get_chat_userdata_name             |
|                     |                                     | self::get_chat_type             |
