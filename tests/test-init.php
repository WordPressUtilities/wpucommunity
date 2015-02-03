<?php
class WPUCommunity_Init extends WP_UnitTestCase
{

    public $demo_plugin;

    function setUp() {
        parent::setUp();
        $this->demo_plugin = new WPUCommunity;
    }

    function test_init_plugin() {

        // Simulate WordPress init
        do_action('init');
        $this->assertEquals(10, has_action('init', array(
            $this->demo_plugin,
            'rewrite_rules'
        )));
        $this->assertEquals(10, has_filter('query_vars', array(
            $this->demo_plugin,
            'add_query_vars_filter'
        )));
        $this->assertEquals(99, has_action('parse_query', array(
            $this->demo_plugin,
            'check_current_page'
        )));
        $this->assertEquals(10, has_action('wp', array(
            $this->demo_plugin,
            'postaction'
        )));
        $this->assertEquals(99, has_filter('template_include', array(
            $this->demo_plugin,
            'template_include'
        )));
        $this->assertEquals(99, has_filter('template_redirect', array(
            $this->demo_plugin,
            'template_redirect'
        )));
    }
}
