<?php
class WPUCommunity_Templateinclude extends WP_UnitTestCase
{

    public $demo_plugin;

    function setUp() {
        parent::setUp();
        $this->demo_plugin = new WPUCommunity;
        do_action('init');
    }

    function test_template_plugin() {
        // Test loading template from a plugin file
        $this->demo_plugin->set_current_page('signin');
        $this->assertContains('templates/wpuc-signin.php', $this->demo_plugin->load_template_id('signin'));
    }

    function test_template_theme() {
        // Test loading template from a theme file
        $this->demo_plugin->set_current_page('signin');
        $this->demo_plugin->create_template('signin');
        $template_path = $this->demo_plugin->load_template_id('signin');
        $this->assertNotContains('templates/wpuc-signin.php', $template_path);
        @unlink($template_path);
    }
}
