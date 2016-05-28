<?php
global $WPUCommunity;
get_header();
do_action('wpucommunity_messages');
echo $WPUCommunity->get_form_html('login');
get_footer();
