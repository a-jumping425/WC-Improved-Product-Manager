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

            if ( is_admin() ) { // admin actions
                // Admin Page Left Menu Button
                add_action( 'admin_menu', array($this, 'register_admin_menu') );

                // Add search scripts and styles
                add_action( 'admin_enqueue_scripts', array( $this, 'register_search_scripts_and_styles' ) );
            }
        }

        public function activate() {

        }

        public function deactivate() {

        }

        /**
         * Register admin menu of plugin
         */
        public function register_admin_menu() {
            $menu = add_menu_page(
                __('Enhanced product search', 'wc_eps'),
                __('Enhanced product search', 'wc_eps'),
                'manage_options',
                'wc_eps',
                array($this, 'show_search_page'),
                'dashicons-search'
            );
        }

        /**
         * Register scripts and styles to search page
         */
        public function register_search_scripts_and_styles($hook) {
            // Load only on ?page=wc_eps
            if( $hook != 'toplevel_page_wc_eps' ) {
                return;
            }

            wp_enqueue_script('wc_eps_jquery_validate_js', plugins_url('assets/js/jquery.validate.min.js', __FILE__), array(), false, true);
            wp_enqueue_script('wc_eps_search_form_js', plugins_url('assets/js/search_form.js', __FILE__), array(), false, true);
        }

        public function show_search_page() {
            ?>
            <div class="wrap">
                <h1><?php _e( 'Enhanced Product Search', 'wc_eps' ); ?></h1>
                <form method="post" action="">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row"><label for="name">Name</label></th>
                            <td><input type="input" name="wc_eps[name]" id="name" class="regular-text" value="" placeholder="Product name" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="sku">SKU</label></th>
                            <td><input type="input" name="wc_eps[sku]" id="sku" class="regular-text" value="" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="is_on_sale">Is on sale?</label></th>
                            <td><input type="checkbox" name="wc_eps[is_on_sale]" id="is_on_sale" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="price_min">Price</label></th>
                            <td>
                                <input type="input" name="wc_eps[price_min]" id="price_min" value="" />
                                &nbsp;-&nbsp;
                                <input type="input" name="wc_eps[price_max]" id="price_max" value="" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="category">Category</label></th>
                            <td>
                                <select name="wc_eps[category]" id="category">
                                    <option>- Category -</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Attributes</th>
                            <td>

                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }
    }

endif;

// Global for backwards compatibility.
$GLOBALS['wc_eps'] = WCImprovedProductManager::instance();
