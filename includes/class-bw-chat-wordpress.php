<?php

class BW_Chat_WordPress {

    public function __construct() {
        add_action('init', [$this, 'create_bw_chat_cpt']);
        add_filter('acf/settings/remove_wp_meta_box', [$this, 'enable_native_custom_fields']);
    }

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



    public function enable_native_custom_fields($value) {
        global $post;
        if (isset($post->post_type) && $post->post_type === 'bw-chat') {
            return false;
        }
        return $value;
    }
}
