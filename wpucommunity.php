<?php

/*
Plugin Name: WPU Community
Description: Launch a community
Version: 0.6.2
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUCommunity {
    private $current_page = '';

    private $notices_categories = array(
        'updated',
        'update-nag',
        'error'
    );

    private $pages = array();
    private $user_fields = array();

    public function __construct() {

        add_action('init', array(&$this,
            'init'
        ), 10);

        add_action('init', array(&$this,
            'rewrite_rules'
        ), 10);

        add_action('init', array(&$this,
            'prevent_admin_access'
        ), 10);

        add_filter('query_vars', array(&$this,
            'add_query_vars_filter'
        ), 10);

        add_action('parse_query', array(&$this,
            'check_current_page'
        ), 99);

        add_filter('template_include', array(&$this,
            'template_include'
        ), 99);

        add_filter('template_redirect', array(&$this,
            'template_redirect'
        ), 99);

        add_action('init', array(&$this,
            'postAction'
        ), 10);

        add_filter('body_class', array(&$this,
            'body_classes'
        ));

        add_action('wpucommunity_messages', array(&$this,
            'display_messages'
        ));

        load_plugin_textdomain('wpucommunity', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        $this->pages = array(
            'signin' => array(
                'url' => '/signin/',
                'regex' => '^signin/?',
                'must_be_logged' => 0,
                'must_not_be_logged' => 1,
                'name' => __('Sign in', 'wpucommunity')
            ),
            'account-edit' => array(
                'url' => '/account/edit/',
                'regex' => '^account\/edit/?',
                'must_be_logged' => 1,
                'must_not_be_logged' => 0,
                'name' => __('Infos', 'wpucommunity')
            ),
            'account' => array(
                'url' => '/account/',
                'regex' => '^account/?',
                'must_be_logged' => 1,
                'must_not_be_logged' => 0,
                'name' => __('Account', 'wpucommunity')
            )
        );

        $user_sections = array(
            'native' => array(
                'name' => __('My Infos', 'wpucommunity')
            ),
            'password' => array(
                'name' => __('My Password', 'wpucommunity')
            )
        );

        $user_fields = array(
            'first_name' => array(
                'section' => 'native',
                'name' => __('First name', 'wpucommunity'),
                'type' => 'text'
            ),
            'last_name' => array(
                'section' => 'native',
                'name' => __('Last name', 'wpucommunity'),
                'type' => 'text'
            ),
            'user_email' => array(
                'section' => 'native',
                'name' => __('Email', 'wpucommunity'),
                'type' => 'email'
            ),
            'new_password' => array(
                'section' => 'password',
                'name' => __('New password', 'wpucommunity'),
                'type' => 'password'
            ),
            'new_password2' => array(
                'section' => 'password',
                'name' => __('New password (repeat)', 'wpucommunity'),
                'type' => 'password'
            )
        );

        $this->user_sections = apply_filters('wpucommunity_user_sections', $user_sections);
        $this->user_fields = apply_filters('wpucommunity_user_fields', $user_fields);

        foreach ($this->user_fields as $id => $field) {
            if (!isset($field['section'])) {
                $this->user_fields[$id]['section'] = 'default';
            }
            if (!isset($field['type'])) {
                $this->user_fields[$id]['type'] = 'text';
            }
        }

        $this->pages = apply_filters('wpucommunity_pages', $this->pages);
    }

    public function init() {
        $prefix = '';
        $current_user = wp_get_current_user();
        if (is_object($current_user)) {
            $prefix .= $current_user->ID;
        }

        // Set Messages
        $this->transient_prefix = sanitize_title(basename(__FILE__)) . $prefix;
        $this->transient_msg = $this->transient_prefix . '__messages';
    }

    /* ----------------------------------------------------------
      Security
    ---------------------------------------------------------- */

    public function prevent_admin_access() {
        if (current_user_can('upload_files')) {
            return;
        }

        // Disable admin bar
        show_admin_bar(false);

        if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
            wp_redirect(home_url());
            exit;
        }
    }

    /* ----------------------------------------------------------
      Front-End actions
    ---------------------------------------------------------- */

    public function rewrite_rules() {

        $opt_rules = 'wpucommunity_rewriterules';
        $opt_rules_version = md5(serialize($this->pages));

        if (get_option($opt_rules) != $opt_rules_version) {
            update_option($opt_rules, $opt_rules_version);
            flush_rewrite_rules();
        }

        foreach ($this->pages as $id => $p) {
            add_rewrite_rule($p['regex'], 'index.php?wpuc=' . $id, 'top');
        }

    }

    public function add_query_vars_filter($vars) {
        $vars[] = "wpuc";
        return $vars;
    }

    public function set_current_page($id) {
        $this->current_page = $id;
    }

    public function check_current_page() {
        global $wp_query;

        if (!empty($wp_query->query_vars['wpuc']) && array_key_exists($wp_query->query_vars['wpuc'], $this->pages)) {
            $this->set_current_page($wp_query->query_vars['wpuc']);
        }
    }

    public function template_redirect() {

        $logged_in = is_user_logged_in();

        if (empty($this->current_page)) {
            return;
        }

        foreach ($this->pages as $id_page => $tmp_page) {
            if ($id_page != $this->current_page) {
                continue;
            }

            /* Must be logged : redirect to signin */
            if (!$logged_in && $tmp_page['must_be_logged']) {
                wp_redirect($this->get_url('signin'));
                exit();
            }

            /* Must not be logged : redirect to account */
            if ($logged_in && $tmp_page['must_not_be_logged']) {
                wp_redirect($this->get_url('account'));
                exit();
            }
        }
    }

    public function body_classes($classes) {
        if (empty($this->current_page)) {
            return $classes;
        }
        if (($key = array_search('home', $classes)) !== false) {
            unset($classes[$key]);
        }

        $classes[] = 'wpuc-' . $this->current_page;
        // return the $classes array
        return $classes;
    }

    /* ----------------------------------------------------------
      Actions
    ---------------------------------------------------------- */

    public function postAction() {
        // Only if a form is sent
        if (empty($_POST) || !isset($_POST['wpuc-action'])) {
            return false;
        }

        $action = esc_attr($_POST['wpuc-action']);
        $nonce_id = 'wpucommunity_form_' . $action;
        if (!isset($_POST[$nonce_id]) || !wp_verify_nonce($_POST[$nonce_id], $nonce_id)) {
            return false;
        }

        switch ($action) {
        case 'edit':
            $this->postAction_edit();
            break;
        }
    }

    public function postAction_edit() {
        $current_user = wp_get_current_user();
        $userdata = array();
        $user_id = get_current_user_id();

        /* Change datas */

        foreach ($this->user_fields as $id => $field) {
            if ($field['section'] == 'password') {
                continue;
            }

            // Get initial value
            $value = '';
            if ($field['section'] == 'native') {
                $value = $current_user->$id;
            } else {
                $value = get_user_meta($user_id, $id);
            }

            // Get submitted value
            if (isset($_POST[$id])) {
                $value = $this->validate_field($id, $field, $_POST[$id]);
            }

            // Save value
            if ($field['section'] == 'native') {
                $userdata[$id] = $value;
            } else {
                update_user_meta($user_id, $id, $value);
            }
        }

        if (empty($userdata)) {
            return;
        }

        $userdata['ID'] = $user_id;
        wp_update_user($userdata);

        /* Password */
        $this->change_user_password_from($user_id, $_POST);
        $this->set_message('success-edit', __('Your account has been successfully edited', 'wpucommunity'));
        wp_redirect($this->get_url('account-edit'));
        die;

    }

    public function validate_field($id = '', $field = '', $value = '') {
        $tmp_value = esc_html($value);
        $value = '';
        switch ($field['type']) {
        case 'email':
            if (!empty($tmp_value) && filter_var($tmp_value, FILTER_VALIDATE_EMAIL) !== false) {
                $value = $tmp_value;
            }
            break;
        case 'number':
            if (!empty($tmp_value) && is_numeric($tmp_value)) {
                $value = $tmp_value;
            }
            break;
        case 'url':
            if (!empty($tmp_value) && filter_var($tmp_value, FILTER_VALIDATE_URL) !== false) {
                $value = $tmp_value;
            }
            break;
        default:
            $value = $tmp_value;
        }
        return $value;
    }

    public function change_user_password_from($user_id = 1, $post = array()) {
        $user_info = get_userdata($user_id);

        /* If new password is correctly defined */
        if (!isset($post['new_password'], $post['new_password2'])) {
            return false;
        }
        if (empty($post['new_password'])) {
            return false;
        }
        if ($post['new_password'] != $post['new_password2']) {
            return false;
        }

        /* Change password */
        wp_set_password($post['new_password'], $user_id);
        wp_cache_delete($user_id, 'users');
        wp_cache_delete($user_info->user_login, 'userlogins');
        wp_logout();
        if (wp_signon(array('user_login' => $user_info->user_login, 'user_password' => $post['new_password']), false)):
            wp_redirect($this->get_url('account-edit'));
            exit;
        endif;

        return true;
    }

    public function get_form_html() {

        $html = '<form action="" method="post" class="wpucommunity-form-edit"><div>';
        $html .= wp_nonce_field('wpucommunity_form_edit', 'wpucommunity_form_edit', true, false);
        $html .= '<input type="hidden" name="wpuc-action" value="edit" />';
        foreach ($this->user_sections as $id => $section) {
            $html .= $this->get_section_html($section['name'], $id);
        }

        $html .= '<p><button type="submit">' . __('Save', 'wpucommunity') . '</button></p>';
        $html .= '</div></form>';

        return $html;
    }

    public function get_section_html($name, $section) {
        $html = '<h3 class="legend">' . $name . '</h3><ul>';

        $user_info = wp_get_current_user();
        foreach ($this->user_fields as $id => $field) {
            if ($field['section'] != $section) {
                continue;
            }
            $value = '';
            if ($field['section'] == 'native') {
                $value = $user_info->$id;
            }
            if ($field['section'] != 'password' && $field['section'] != 'native') {
                $value = get_user_meta($user_info->ID, $id, 1);
            }
            $html .= '<li>' . $this->get_field_html($id, $field['name'], $value, $field['type']) . '</li>';
        }

        $html .= '</ul>';
        return $html;

    }

    public function get_field_html($id, $name, $value, $type) {
        $html = '';
        $html .= '<label for="' . $id . '">' . $name . '</label>';
        switch ($type) {
        case 'text':
        case 'url':
        case 'number':
        case 'email':
        case 'password':
            $html .= '<input name="' . $id . '" id="' . $id . '" type="' . $type . '" value="' . $value . '" />';
            break;
        }
        return $html;
    }

    /* ----------------------------------------------------------
      Template functions
    ---------------------------------------------------------- */

    public function template_include($template) {
        if (empty($this->current_page)) {
            return $template;
        }

        return $this->load_template_id($this->current_page);
    }

    public function create_template($page_id) {
        if (!array_key_exists($page_id, $this->pages)) {
            return false;
        }
        copy($this->get_template_path($page_id), $this->get_template_theme_path($page_id));
    }

    public function load_template_id($page_id) {
        $new_template = $this->get_template_theme_path($page_id);
        if (!file_exists($new_template)) {
            $new_template = $this->get_template_path($page_id);
        }
        return $new_template;
    }

    public function get_template_path($page_id) {
        return plugin_dir_path(__FILE__) . 'templates/wpuc-' . $page_id . '.php';
    }

    public function get_template_theme_path($page_id) {
        return get_stylesheet_directory() . '/wpuc-' . $page_id . '.php';
    }

    /* ----------------------------------------------------------
      Links
    ---------------------------------------------------------- */

    public function get_logout_url() {
        return wp_logout_url(home_url());
    }

    public function get_account_url() {
        return $this->get_url('account');
    }

    public function get_register_url() {
        return wp_registration_url();
    }

    public function get_login_url() {
        return $this->get_url('signin');
    }

    public function get_url($id) {
        if (!array_key_exists($id, $this->pages)) {
            return get_home_url();
        }
        return get_home_url() . $this->pages[$id]['url'];
    }

    /* ----------------------------------------------------------
      Messages
    ---------------------------------------------------------- */

    /* Set notices messages */
    public function set_message($id, $message, $group = '') {
        if (defined('DOING_CRON')) {
            return;
        }
        $messages = (array) get_transient($this->transient_msg);
        if (!in_array($group, $this->notices_categories)) {
            $group = $this->notices_categories[0];
        }
        $messages[$group][$id] = $message;
        set_transient($this->transient_msg, $messages);
    }

    public function display_messages() {
        $messages = (array) get_transient($this->transient_msg);
        if (!empty($messages)) {
            foreach ($messages as $group_id => $group) {
                if (is_array($group)) {
                    foreach ($group as $message) {
                        echo '<div class="public-notice ' . $group_id . ' notice is-dismissible"><p>' . $message . '</p></div>';
                    }
                }
            }
        }

        // Empty messages
        delete_transient($this->transient_msg);
    }

    /* ----------------------------------------------------------
      Activation
    ---------------------------------------------------------- */

    public function activation() {
        update_option('users_can_register', 1);
    }

    public function deactivation() {
        update_option('users_can_register', 0);
    }

}

$WPUCommunity = new WPUCommunity();

register_activation_hook(__FILE__, array(&$WPUCommunity, 'activation'));
register_deactivation_hook(__FILE__, array(&$WPUCommunity, 'deactivation'));
