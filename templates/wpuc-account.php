<?php
get_header();
do_action('wpucommunity_messages');
$current_user = wp_get_current_user();

echo '<a href="'.wp_logout_url().'">Log out</a>';

echo '<pre>';
echo 'Username: ' . $current_user->user_login . "\n";
echo 'User email: ' . $current_user->user_email . "\n";
echo 'User first name: ' . $current_user->user_firstname . "\n";
echo 'User last name: ' . $current_user->user_lastname . "\n";
echo 'User display name: ' . $current_user->display_name . "\n";
echo 'User ID: ' . $current_user->ID . "\n";
echo '</pre>';

get_footer(); ?>
