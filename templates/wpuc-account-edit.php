<?php
get_header();
$user_info = get_userdata(get_current_user_id());
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
                <label for="last_name"><?php echo __('last name', 'wpucommunity'); ?></label>
                <input name="last_name" id="last_name" type="text" value="<?php echo esc_attr($user_info->last_name); ?>" />
            </li>
            <li>
                <button type="submit"><?php echo __( 'Save', 'wpucommunity' ); ?></button>
            </li>
        </ul>
    </div>
</form>
<?php
get_footer();?>
