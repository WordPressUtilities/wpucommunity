<?php
get_header();
do_action('wpucommunity_messages');
echo $WPUCommunity->get_form_html('edit');
get_footer();
