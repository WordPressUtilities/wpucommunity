<?php
global $WPUCommunity;
$wpc_p = $WPUCommunity->get_current_page();
?><nav class="account-menu">
    <a class="<?php echo ($wpc_p == 'account' ? 'current' : ''); ?>" href="<?php echo $WPUCommunity->get_url('account'); ?>"><?php echo __( 'Dashboard', 'wpucommunity' ); ?></a>
    -
    <a class="<?php echo ($wpc_p == 'account-edit' ? 'current' : ''); ?>" href="<?php echo $WPUCommunity->get_url('account-edit'); ?>"><?php echo __( 'Edit', 'wpucommunity' ); ?></a>
    -
    <a href="<?php echo wp_logout_url(); ?>"><?php echo __( 'Log out', 'wpucommunity' ); ?></a>
</nav>
<hr />
