<?php

/*
Plugin Name: WPU Community
Description: Launch a community
Version: 0.10.1
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

    public $opt_rules = 'wpucommunity_rewriterules';

    private $pages = array();
    private $user_fields = array();

    public function __construct() {

        if (!session_id()) {
            session_start();
        }
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

        add_filter('pre_get_document_title', array(&$this,
            'wp_title'
        ), 1000, 2);

        add_filter('wp_title', array(&$this,
            'wp_title'
        ), 1000, 2);

        add_action('pre_get_posts', array(&$this,
            'disable_home_page'
        ), 1);

        add_action('init', array(&$this,
            'postAction'
        ), 10);

        add_filter('body_class', array(&$this,
            'body_classes'
        ));

        add_action('wpucommunity_messages', array(&$this,
            'display_messages'
        ));

        add_action('wp_logout', array(&$this,
            'session_clear'
        ));

        load_plugin_textdomain('wpucommunity', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        $base_role = array(
            'id' => 'wpumember',
            'name' => __('Member', 'wpucommunity'),
            'capabilities' => array(
                'read' => true,
                'level_0' => true
            )
        );

        $this->role = apply_filters('wpucommunity_base_role', $base_role);

        $this->pages = array(
            'register' => array(
                'url' => '/register/',
                'regex' => '^register/?',
                'must_be_logged' => 0,
                'must_not_be_logged' => 1,
                'name' => __('Sign in', 'wpucommunity')
            ),
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

        $this->enable_profile_pages = apply_filters('wpucommunity_enable_profile_pages', true);
        if ($this->enable_profile_pages) {
            $this->pages['profile'] = array(
                'url' => '/profile/',
                'regex' => '^profile/([a-z0-9_-]+)/?',
                'arguments' => array('username'),
                'must_be_logged' => 0,
                'must_not_be_logged' => 0,
                'name' => __('Profile', 'wpucommunity'),
                'title_args' => __('#username#\'s profile', 'wpucommunity')
            );
        }

        $user_sections = array(
            'native' => array(
                'name' => __('My Infos', 'wpucommunity')
            ),
            'new_password' => array(
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
                'required' => 1,
                'name' => __('Email', 'wpucommunity'),
                'type' => 'email'
            ),
            'nickname' => array(
                'section' => 'native',
                'required' => 1,
                'name' => __('Nick name', 'wpucommunity'),
                'type' => 'text'
            ),
            'description' => array(
                'section' => 'native',
                'name' => __('Description', 'wpucommunity'),
                'type' => 'textarea'
            ),
            'new_password' => array(
                'section' => 'new_password',
                'name' => __('New password', 'wpucommunity'),
                'type' => 'password'
            ),
            'new_password2' => array(
                'section' => 'new_password',
                'name' => __('New password (repeat)', 'wpucommunity'),
                'type' => 'password'
            )
        );

        $register_fields = array(
            'user_email' => array(
                'required' => 1,
                'name' => __('Email', 'wpucommunity'),
                'type' => 'email'
            ),
            'user_password' => array(
                'required' => 1,
                'name' => __('Password', 'wpucommunity'),
                'type' => 'password'
            ),
            'user_password2' => array(
                'required' => 1,
                'name' => __('Password (repeat)', 'wpucommunity'),
                'type' => 'password'
            )
        );

        $login_fields = array(
            'user_email' => array(
                'required' => 1,
                'name' => __('Email', 'wpucommunity'),
                'type' => 'email'
            ),
            'user_password' => array(
                'required' => 1,
                'name' => __('Password', 'wpucommunity'),
                'type' => 'password'
            ),
            'remember' => array(
                'name' => __('Remember me', 'wpucommunity'),
                'type' => 'checkbox'
            )
        );

        $this->use_email_as_login = apply_filters('wpucommunity_use_email_as_login', true);

        if (!$this->use_email_as_login) {
            $login_fields['user_email']['type'] = 'text';
            $login_fields['user_email']['name'] = __('Username', 'wpucommunity');
        }

        $this->login_fields = apply_filters('wpucommunity_login_fields', $login_fields);
        $this->register_fields = apply_filters('wpucommunity_register_fields', $register_fields);
        $this->user_sections = apply_filters('wpucommunity_user_sections', $user_sections);
        $this->user_fields = apply_filters('wpucommunity_user_fields', $user_fields);

        foreach ($this->user_fields as $id => $field) {
            if (!isset($field['section'])) {
                $this->user_fields[$id]['section'] = 'default';
            }
            if (!isset($field['type'])) {
                $this->user_fields[$id]['type'] = 'text';
            }
            if (!isset($field['required'])) {
                $this->user_fields[$id]['required'] = false;
            }
        }

        $this->pages = apply_filters('wpucommunity_pages', $this->pages);
    }

    public function init() {
        $prefix = session_id();

        // Set Messages
        $this->transient_prefix = sanitize_title(basename(__FILE__)) . $prefix;
        $this->transient_msg = $this->transient_prefix . '__messages';
    }

    /* ----------------------------------------------------------
      Security
    ---------------------------------------------------------- */

    public function prevent_admin_access() {
        if (!is_user_logged_in() || current_user_can('upload_files')) {
            return;
        }

        // Disable admin bar
        show_admin_bar(false);

        if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
            wp_redirect(home_url());
            die;
        }
    }

    /* ----------------------------------------------------------
      Front-End actions
    ---------------------------------------------------------- */

    public function rewrite_rules() {

        $opt_rules_version = md5(serialize($this->pages));

        if (get_option($this->opt_rules) != $opt_rules_version) {
            update_option($this->opt_rules, $opt_rules_version);
            flush_rewrite_rules();
        }

        foreach ($this->pages as $id => $p) {
            $redirect = 'index.php?wpuc=' . $id;
            if (isset($p['arguments']) && is_array($p['arguments'])) {
                foreach ($p['arguments'] as $i => $arg) {
                    $redirect .= '&' . esc_html($arg) . '=$matches[' . ($i + 1) . ']';
                }
                add_rewrite_rule($p['regex'], $redirect, 'top');
            } else {
                add_rewrite_rule($p['regex'], $redirect, 'top');
            }
        }

    }

    public function add_query_vars_filter($vars) {
        $vars[] = "wpuc";
        $vars[] = "username";
        return $vars;
    }

    public function set_current_page($id) {
        $this->current_page = $id;
    }

    public function get_current_page() {
        return !empty($this->current_page) ? $this->current_page : '';
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

        // Profile pages
        if ($this->enable_profile_pages && $this->current_page == 'profile') {

            // Prevent empty username
            $username = get_query_var('username');
            if (empty($username)) {
                wp_redirect(home_url());
                die;
            }

            // Prevent invalid slugs
            $user = get_user_by('slug', $username);
            if (!is_object($user) || is_wp_error($user)) {
                global $wp_query;
                header("HTTP/1.0 404 Not Found - Archive Empty");
                $wp_query->set_404();
                require locate_template('404.php');
                exit;
            }

            return true;

        }

        foreach ($this->pages as $id_page => $tmp_page) {
            if ($id_page != $this->current_page) {
                continue;
            }

            /* Must be logged : redirect to signin */
            if (!$logged_in && $tmp_page['must_be_logged']) {
                wp_redirect($this->get_url('signin'));
                die;
            }

            /* Must not be logged : redirect to account */
            if ($logged_in && $tmp_page['must_not_be_logged']) {
                wp_redirect($this->get_url('account'));
                die;
            }
        }
    }

    public function wp_title($title) {
        if (empty($this->current_page)) {
            return $title;
        }
        $p = $this->pages[$this->current_page];

        $pagename = $p['name'];
        if (isset($p['title_args'], $p['arguments']) && is_array($p['arguments'])) {
            $pagename = $p['title_args'];
            foreach ($p['arguments'] as $arg) {
                $pagename = str_replace('#' . $arg . '#', get_query_var($arg), $pagename);
            }
        }

        $separator = ' | ';

        $title = $pagename . $separator . get_bloginfo('name');
        $title = apply_filters('wpucommunity_page_title', $title, $pagename, $separator);
        return $title;
    }

    public function disable_home_page($query) {
        if (!$query->is_main_query()) {
            return false;
        }
        if (empty($this->current_page)) {
            return false;
        }
        $query->set('is_home', false);
        $query->is_home = false;
        $query->set('is_front_page', false);
        $query->is_front_page = false;

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
        case 'register':
            $this->postAction_register();
            break;
        case 'login':
            $this->postAction_login();
            break;
        }
    }

    public function postAction_login() {

        // Check validity of values
        if (empty($_POST) || !isset($_POST['user_email'], $_POST['user_password'])) {
            $this->set_message('fail-login-form', __('The form is invalid', 'wpucommunity'), 'error');
            wp_redirect($this->get_url('signin'));
            die;
        }

        $field_type = is_email($_POST['user_email']) ? 'email' : 'slug';

        if ($this->use_email_as_login) {
            if ($field_type != 'email') {
                $this->set_message('fail-login-email', __('The username should be an email', 'wpucommunity'), 'error');
                wp_redirect($this->get_url('signin'));
                die;
            }
        }

        $user = get_user_by($field_type, $_POST['user_email']);

        // Check email does exists
        if (!is_object($user)) {
            $this->set_message('fail-login-exists', __('This username is not linked to an account', 'wpucommunity'), 'error');
            wp_redirect($this->get_url('signin'));
            die;
        }

        $creds = array(
            'user_login' => $_POST['user_email'],
            'user_password' => $_POST['user_password'],
            'remember' => isset($_POST['remember'])
        );

        $signon = wp_signon($creds, false);

        $login_success_url = apply_filters('wpucommunity_login_success_url', $this->get_url('account-edit'));
        if (!is_wp_error($signon)) {
            update_user_meta($signon->ID, 'last_login_time', time());
            wp_redirect($login_success_url);
        } else {
            $this->set_message('fail-login-access', __('The password is invalid.', 'wpucommunity'), 'error');
            wp_redirect($this->get_url('signin'));
        }
        die;

    }

    public function postAction_register() {

        // Check validity of values
        if (empty($_POST) || !isset($_POST['user_email'], $_POST['user_password'], $_POST['user_password2']) || !is_email($_POST['user_email'])) {
            wp_redirect($this->get_url('register'));
            die;
        }

        // Check email does not exists
        $user = get_user_by('email', $_POST['user_email']);
        if (is_object($user)) {
            $this->set_message('fail-register-exists', __('This email is already in use', 'wpucommunity'), 'error');
            wp_redirect($this->get_url('register'));
            die;
        }

        // Check password = password 2
        if ($_POST['user_password'] != $_POST['user_password2']) {
            $this->set_message('fail-register-pass', __('The two password do not match', 'wpucommunity'), 'error');
            wp_redirect($this->get_url('register'));
            die;
        }

        // Create user
        $register_success_url = apply_filters('wpucommunity_register_success_url', $this->get_url('account'));
        $user_login = str_replace(array('.', ' '), '', 'user-' . uniqid());
        $user_id = wp_insert_user(array(
            'user_login' => $user_login,
            'user_email' => $_POST['user_email'],
            'user_pass' => $_POST['user_password'],
            'role' => $this->role['id']
        ));
        if (is_numeric($user_id)) {
            $this->set_message('success-register', __('Your account has been successfully created', 'wpucommunity'));
            wp_signon(array('user_login' => $user_login, 'user_password' => $_POST['user_password']), false);
            wp_redirect($register_success_url);
        } else {
            $this->set_message('fail-register', __('The account could not be created', 'wpucommunity'), 'error');
            wp_redirect($this->get_url('register'));
        }
        die;
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

            if ($field['required'] && empty($value)) {
                $this->set_message('empty-field-' . $id, sprintf(__('The field "%s" is missing', 'wpucommunity'), $field['name']), 'error');
                continue;
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
            die;
        endif;

        return true;
    }

    public function get_form_html($type = 'edit') {

        $html = '<form action="' . esc_url(home_url('/')) . '" method="post" class="wpucommunity-form-' . $type . '"><div>';
        $html .= wp_nonce_field('wpucommunity_form_' . $type, 'wpucommunity_form_' . $type, true, false);
        $html .= '<input type="hidden" name="wpuc-action" value="' . $type . '" />';

        switch ($type) {

        case 'register':
            $html .= '<ul>';
            foreach ($this->register_fields as $id => $field) {
                $html .= '<li>' . $this->get_field_html($id, $field, '') . '</li>';
            }
            $html .= '</ul>';
            $html .= '<p><button type="submit">' . __('Register', 'wpucommunity') . '</button></p>';

            break;

        case 'login':
            $html .= '<ul>';
            foreach ($this->login_fields as $id => $field) {
                $html .= '<li>' . $this->get_field_html($id, $field, '') . '</li>';
            }
            $html .= '</ul>';
            $html .= '<p><button type="submit">' . __('Login', 'wpucommunity') . '</button></p>';

            break;

        default:
            foreach ($this->user_sections as $id => $section) {
                $html .= $this->get_section_html($section['name'], $id);
            }
            $html .= '<p><button type="submit">' . __('Save', 'wpucommunity') . '</button></p>';
        }

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
            $html .= '<li>' . $this->get_field_html($id, $field, $value) . '</li>';
        }

        $html .= '</ul>';
        return $html;

    }

    public function get_field_html($id, $field = array(), $value = '') {
        $name = isset($field['name']) && !empty($field['name']) ? $field['name'] : $id;
        $type = isset($field['type']) && !empty($field['type']) ? $field['type'] : 'text';
        $required = isset($field['required']) && $field['required'] ? ' required="required"' : '';
        $html = '';
        $html .= '<label for="' . $id . '">' . $name . '</label>';
        switch ($type) {
        case 'text':
        case 'url':
        case 'number':
        case 'email':
        case 'password':
            $html .= '<input ' . $required . ' name="' . $id . '" id="' . $id . '" type="' . $type . '" value="' . $value . '" />';
            break;
        case 'checkbox':
            $html = '<input ' . $required . ' name="' . $id . '" id="' . $id . '" type="' . $type . '" ' . ($value == '1' ? 'checked="checked"' : '') . ' />' . $html;
            break;
        case 'textarea':
            $html .= '<textarea ' . $required . ' name="' . $id . '" id="' . $id . '">' . $value . '</textarea>';
            break;
        }
        return $html;
    }

    /* ----------------------------------------------------------
      Public profile
    ---------------------------------------------------------- */

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
        copy($this->get_template_path($page_id), $this->get_template_theme_path($page_id, 1));
    }

    public function load_template_id($page_id) {
        $new_template = $this->get_template_theme_path($page_id);
        if (empty($new_template) || !file_exists($new_template)) {
            $new_template = $this->get_template_path($page_id);
        }
        return $new_template;
    }

    public function get_template_account_menu() {
        return plugin_dir_path(__FILE__) . 'inc/account-menu.php';
    }

    public function get_template_path($page_id) {
        return plugin_dir_path(__FILE__) . 'templates/wpuc-' . $page_id . '.php';
    }

    public function get_template_theme_path($page_id, $force_obtain_path = false) {
        $template = locate_template('/wpuc-' . $page_id . '.php');
        if (empty($template) && $force_obtain_path) {
            $template = get_stylesheet_directory() . '/wpuc-' . $page_id . '.php';
        }
        return $template;
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

    public function session_clear() {
        session_regenerate_id();
        session_destroy();
    }

    /* ----------------------------------------------------------
      User role
    ---------------------------------------------------------- */

    public function add_user_role() {
        $this->remove_user_role();
        add_role($this->role['id'], $this->role['name'], $this->role['capabilities']);
    }

    public function remove_user_role() {
        remove_role($this->role['id']);
    }

    /* ----------------------------------------------------------
      Activation
    ---------------------------------------------------------- */

    public function activation() {
        update_option('users_can_register', 0);
        $this->add_user_role();
    }

    public function deactivation() {
        $this->remove_user_role();
    }

    public function uninstall() {
        $this->remove_user_role();
        delete_option($this->opt_rules);
    }

}

$WPUCommunity = new WPUCommunity();

register_activation_hook(__FILE__, array(&$WPUCommunity, 'activation'));
register_deactivation_hook(__FILE__, array(&$WPUCommunity, 'deactivation'));
