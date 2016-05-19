<?php

/*
Plugin Name: WPU Community
Description: Launch a community
Version: 0.3.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUCommunity {
    private $current_page = '';

    private $pages = array();

    public function __construct() {

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

        foreach ($this->pages as $id => $page) {
            add_rewrite_rule($page['regex'], 'index.php?wpuc=' . $id, 'top');
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

        $page = $this->current_page;
        $logged_in = is_user_logged_in();

        if (empty($page)) {
            return;
        }

        foreach ($this->pages as $id_page => $tmp_page) {
            if ($id_page != $page) {
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
        $userdata = array();
        $fields = array(
            'first_name' => array(
                'name' => 'First name',
                'type' => 'text'
            ),
            'last_name' => array(
                'name' => 'Last name',
                'type' => 'text'
            ),
            'user_email' => array(
                'name' => 'Email',
                'type' => 'email'
            )
        );

        $current_user = wp_get_current_user();
        foreach ($fields as $id => $field) {

            $value = $current_user->$id;
            if (isset($_POST[$id])) {
                $tmp_value = esc_html($_POST[$id]);
                switch ($field['type']) {
                case 'email':
                    if (!empty($tmp_value) && filter_var($tmp_value, FILTER_VALIDATE_EMAIL) !== false) {
                        $value = $tmp_value;
                    }
                    break;
                default:
                    $value = $tmp_value;
                }
            }
            $userdata[$id] = $value;
        }

        if (empty($userdata)) {
            return;
        }

        $userdata['ID'] = get_current_user_id();
        wp_update_user($userdata);
        wp_redirect($this->get_url('account-edit'));
        die;

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
