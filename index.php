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
        private $hierarchical_product_categories = null;

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

            // Font Awesome
            wp_enqueue_style('wc_eps_fontawesome', plugins_url('assets/css/font-awesome.min.css', __FILE__));


            // jQuery validate
            wp_enqueue_script('wc_eps_jquery_validate_js', plugins_url('assets/js/jquery.validate.min.js', __FILE__), array(), false, true);

            // jQuery jstree plugin
            wp_enqueue_script('wc_eps_jstree_js', plugins_url('assets/libs/jstree/jstree.min.js', __FILE__), array(), false, true);
            wp_enqueue_style('wc_eps_jstree_css', plugins_url('assets/libs/jstree/themes/default/style.min.css', __FILE__));

            // jQuery Select2 plugin
            wp_enqueue_script('wc_eps_select2_js', plugins_url('assets/libs/select2/js/select2.full.min.js', __FILE__), array(), false, true);
            wp_enqueue_style('wc_eps_select2_css', plugins_url('assets/libs/select2/css/select2.min.css', __FILE__));

            // Search form
            wp_enqueue_script('wc_eps_search_form_js', plugins_url('assets/js/search_form.js', __FILE__), array(), false, true);
            wp_enqueue_style('wc_eps_search_form_css', plugins_url('assets/css/search_form.css', __FILE__));
        }

        private function get_product_categories() {
            $product_categories = get_terms(array(
                'taxonomy' => 'product_cat',
                'orderby' => 'name',
                'order' => 'ASC',
                'hide_empty' => false
            ));

            return $product_categories;
        }

        private function draw_product_category_child_ul($parent) {
            if ( count($this->hierarchical_product_categories[$parent]) ) {
                echo '<ul>';
                foreach ($this->hierarchical_product_categories[$parent] as $category) {
                    echo '<li id="'. $category->term_taxonomy_id .'" data-jstree=\'{"opened": true}\'>' . $category->name;
                    $this->draw_product_category_child_ul($category->term_taxonomy_id);
                    echo '</li>';
                }
                echo '</ul>';
            }
        }

        private function draw_product_category_ul() {
            $product_categories = $this->get_product_categories();

            // establish the hierarchy of the category
            $children = array();
            // first pass - collect children
            foreach ($product_categories as $v ) {
                $pt = $v->parent;
                $list = @$children[$pt] ? $children[$pt] : array();
                array_push( $list, $v );
                $children[$pt] = $list;
            }

            $this->hierarchical_product_categories = $children;

            echo '<ul>';
            foreach ($this->hierarchical_product_categories[0] as $category) {
                echo '<li id="'. $category->term_taxonomy_id .'" data-jstree=\'{"opened": true}\'>' . $category->name;
                $this->draw_product_category_child_ul($category->term_taxonomy_id);
                echo '</li>';
            }
            echo '</ul>';
        }

        private function get_product_attributes() {
            global $wpdb;

            $attributes = $wpdb->get_results('SELECT attribute_name, attribute_label FROM '. $wpdb->prefix .'woocommerce_attribute_taxonomies ORDER BY attribute_label');

            return $attributes;
        }

        private function get_product_attribute_terms($attribute_name) {
            global $wpdb;

            $sql = "SELECT a.`term_taxonomy_id`, t.`name` FROM wp_term_taxonomy AS a"
            . " INNER JOIN wp_terms AS t ON t.`term_id`=a.`term_id`"
            . " WHERE a.`taxonomy`='pa_". $attribute_name ."'"
            . " ORDER BY t.`name`";
            $terms = $wpdb->get_results($sql);

            return $terms;
        }

        public function show_search_page() {
            global $wpdb;

//            var_dump($_POST);

            if( $_POST['wc_eps']['submit_flag'] ) {
                $this->show_search_result();
            } else {
                $this->show_search_form();
            }
        }

        public function show_search_form() {
            $attributes = $this->get_product_attributes();
            ?>
            <div class="wrap">
                <h1><?php _e( 'Enhanced Product Search', 'wc_eps' ); ?></h1>
                <form id="search_form" method="post" action="">
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
                            <th scope="row"><label for="category">Category</label></th>
                            <td>
                                <div id="category">
                                    <?php $this->draw_product_category_ul(); ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Attributes</th>
                            <td>
                                <div class="attribute_to_add">
                                    <div class="col1">
                                        <select id="attribute_terms">
                                            <option></option>
                                            <?php
                                            foreach($attributes as $attribute) {
                                                ?>
                                                <optgroup label="<?php echo $attribute->attribute_label; ?>">
                                                    <?php
                                                    $attribute_terms = $this->get_product_attribute_terms( $attribute->attribute_name );
                                                    foreach($attribute_terms as $term) {
                                                        ?>
                                                        <option data-attrlabel="<?php echo $attribute->attribute_label; ?>" value="<?php echo $term->term_taxonomy_id; ?>"><?php echo $term->name; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </optgroup>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col2"><i class="fa fa-plus-square-o"></i></div>
                                </div>
                                <div class="clear"></div>
                                <div>
                                    <ul id="selected_attributes"></ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="is_on_sale">Is on sale?</label></th>
                            <td><input type="checkbox" name="wc_eps[is_on_sale]" id="is_on_sale" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="price_min">Price</label></th>
                            <td>
                                <input type="input" name="wc_eps[price_min]" id="price_min" class="price" value="" placeholder="Min" />
                                &nbsp;-&nbsp;
                                <input type="input" name="wc_eps[price_max]" id="price_max" class="price" value="" placeholder="Max" />
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <input type="hidden" id="categories" name="wc_eps[categories]" />
                    <input type="hidden" id="attributes" name="wc_eps[attributes]" />
                    <input type="hidden" id="submit_flag" name="wc_eps[submit_flag]" />
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        public function show_search_result() {
            global $wpdb;
            ?>
            <div class="wrap">
                <h1><?php _e( 'Enhanced Product Search', 'wc_eps' ); ?></h1>
            </div>
            <?php
        }
    }

endif;

// Global for backwards compatibility.
$GLOBALS['wc_eps'] = WCImprovedProductManager::instance();
