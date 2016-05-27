<?php
get_header();
include $WPUCommunity->get_template_account_menu();
do_action('wpucommunity_messages');
echo $WPUCommunity->get_form_html('edit');
get_footer();
