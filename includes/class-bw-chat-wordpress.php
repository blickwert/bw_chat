<?php

class BW_Chat_WordPress {

    public function __construct() {
        add_action('init', [$this, 'create_bw_chat_cpt']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('acf/settings/remove_wp_meta_box', [$this, 'enable_native_custom_fields']);
    }

    // Erstellt den Custom Post Type für Chat-Nachrichten
    public function create_bw_chat_cpt() {
        $labels = array(
            'name' => _x('BW Chat', 'Post Type General Name', 'textdomain'),
            'singular_name' => _x('BW Chat Nachricht', 'Post Type Singular Name', 'textdomain'),
            'menu_name' => _x('BW Chat Nachrichten', 'Admin Menu text', 'textdomain'),
            'name_admin_bar' => _x('BW Chat Nachricht', 'Add New on Toolbar', 'textdomain'),
        );

        $args = array(
            'label' => __('BW Chat Nachricht', 'textdomain'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'custom-fields'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
        );

        register_post_type('bw-chat', $args);
    }

    // Enqueuet die Skripte für das Plugin
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('ajax-form-script', plugin_dir_url(__FILE__) . '../js/ajax-form.js', array('jquery'), null, true);

        $session_key = session_id();
        $existing_post = $this->get_post_by_session_key($session_key);
        $existing_content = $existing_post ? $existing_post->post_content : '';

        wp_localize_script('ajax-form-script', 'ajax_form_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ajax-form-nonce'),
            'existing_content' => $existing_content
        ));
    }

    // Aktiviert die nativen Custom Fields nur für den Custom Post Type 'bw-chat'
    public function enable_native_custom_fields($value) {
        global $post;
        if (isset($post->post_type) && $post->post_type === 'bw-chat') {
            return false;
        }
        return $value;
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
}
