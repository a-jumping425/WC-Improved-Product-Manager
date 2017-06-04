<?php
/**
 * Plugin Name: WooCommerce - Improved Product Manager
 * Plugin URI: https://wordpress.org/plugins/
 * Description: Enhance WooCommerce product search and view.
 * Version: 1.0
 * Author: Jumping
 * License: GPL
 */

if ( ! class_exists( 'WCImprovedProductManager' ) ) :

    /**
     * "WooCommerce Improved Product Manager" Main Class
     *
     * @class WCImprovedProductManager
     * @version	1.0
     */
    final class WCImprovedProductManager {
        protected static $_instance = null;

        /**
         * Instance
         *
         * Ensures only one instance of WCImprovedProductManager is loaded or can be loaded.
         *
         * @since 1.0
         * @static
         * @return WCImprovedProductManager - instance
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor.
         * @access public
         * @return WCImprovedProductManager
         */
        public function __construct() {
            // Plugin Activation
            register_activation_hook( __FILE__, array($this, 'activate') );

            // Plugin Deactivation
            register_deactivation_hook( __FILE__, array($this, 'deactivate') );
        }

        public function activate() {

        }

        public function deactivate() {

        }
    }

endif;

// Global for backwards compatibility.
$GLOBALS['wc_eps'] = WCImprovedProductManager::instance();
