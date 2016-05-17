<?php

/*
Plugin Name: WPU Community
Description: Launch a community
Version: 0.2
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

        $this->pages = array(
            'signin' => array(
                'must_be_logged' => 0,
                'must_not_be_logged' => 1,
                'name' => __('Sign in', 'wpucommunity')
            ),
            'account' => array(
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
        if(current_user_can('upload_files')){
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
        add_rewrite_rule('^signin/?', 'index.php?wpuc=signin', 'top');
        add_rewrite_rule('^account/?', 'index.php?wpuc=account', 'top');
    }

    public function add_query_vars_filter($vars) {
        $vars[] = "wpuc";
        return $vars;
    }

    public function get_url($page_id) {
        return home_url($page_id . '/');
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
        return get_home_url() . '/account/';
    }

    public function get_register_url() {
        return wp_registration_url();
    }

    public function get_login_url() {
        return get_home_url() . '/signin/';
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
