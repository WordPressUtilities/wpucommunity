<?php
global $WPUCommunity;
get_header();
do_action('wpucommunity_messages');
wp_login_form(apply_filters('wpucommunity_signin_settings', array(
    'redirect' => $WPUCommunity->get_url('account')
)));
get_footer();
