<?php

/**
 * Plugin Name: Zenda Checkout
 * Description: Zenda Shipping Method for WooCommerce
 * Version: 1.0.0
 * Author: Zenda, powered by British Airways
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /languages
 * Text Domain: zenda
 */
if (!defined('WPINC')) { // Exit if accessed directly
    die;
}
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    class ZendaShipping {

        /**
         * @var       Plugin version
         * @since     1.0.0
         */
        public $version = '1.0.0';

        /**
         * @access      private
         * @var        obj $instance The one true zendashipping
         * @since       1.0.0
         */
        private static $instance = null;

        /**
         * Local path to this plugins root directory
         * @access      public
         * @since       1.0.0
         */
        public $plugin_path = null;

        /**
         * Web path to this plugins root directory
         * @access      public
         * @since       1.0.0
         */
        public $plugin_url = null;

        public static function instance() {

            if (is_null(self::$instance))
                self::$instance = new self();

            return self::$instance;
        }

        public function __construct() {

            //Set global variables
            $this->plugin_path = plugin_dir_path(__FILE__);
            $this->plugin_url = plugins_url('/', __FILE__);

            // Include core files
            $this->includes();
            //initialize hooks
            do_action('noptin_loaded');
        }

        private function includes() {

            require_once $this->plugin_path . 'functions.php';
        }

    }

    function ZendaShipping() {

        return ZendaShipping::instance();
    }

    ZendaShipping();

    add_action('plugins_loaded', 'ZendaShipping_init');

    function ZendaShipping_init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', 'zendashipping_wc_notice');
            return;
        }
    }
	
	add_action('admin_menu', 'add_zenda_menu_page');

	function add_zenda_menu_page() {
	  
	  add_submenu_page( 'woocommerce', __( 'Zenda Checkout', 'zenda' ), __( 'Zenda Checkout', 'zenda' ), 'manage_options', 'wc-settings&tab=shipping&section=zenda', 'dashicons-forms' );
	}

    function zendashipping_wc_notice() {
        echo '<div class="error"><p><strong>' . sprintf(esc_html__('Zenda checkout requires WooCommerce to be installed and active. You can download %s here.', 'zenda'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
    }

    register_activation_hook(__FILE__, 'zendashipping_registration_hook');
}