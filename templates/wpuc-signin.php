<?php
global $WPUCommunity;
get_header();
wp_login_form(array(
    'redirect' => $WPUCommunity->get_url('account')
));
get_footer();
