<?php
get_header();
$user_info = wp_get_current_user();
$username = $user_info->user_login;
?>
<form action="" method="post" class="wpucommunity-form-edit">
    <div>
        <?php wp_nonce_field( 'wpucommunity_form_edit', 'wpucommunity_form_edit' ); ?>
        <input type="hidden" name="wpuc-action" value="edit" />
        <ul>
            <li>
                <label for="first_name"><?php echo __('First name', 'wpucommunity'); ?></label>
                <input name="first_name" id="first_name" type="text" value="<?php echo esc_attr($user_info->first_name); ?>" />
            </li>
            <li>
                <label for="last_name"><?php echo __('Last name', 'wpucommunity'); ?></label>
                <input name="last_name" id="last_name" type="text" value="<?php echo esc_attr($user_info->last_name); ?>" />
            </li>
            <li>
                <label for="user_email"><?php echo __('Email', 'wpucommunity'); ?></label>
                <input name="user_email" id="user_email" type="email" value="<?php echo esc_attr($user_info->user_email); ?>" />
            </li>
            <li>
                <button type="submit"><?php echo __( 'Save', 'wpucommunity' ); ?></button>
            </li>
        </ul>
    </div>
</form>
<?php
get_footer();?>
