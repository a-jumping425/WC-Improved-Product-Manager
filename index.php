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
                session_start();

                // Admin Page Left Menu Button
                add_action( 'admin_menu', array($this, 'register_admin_menu') );

                // Add search scripts and styles
                add_action( 'admin_enqueue_scripts', array( $this, 'register_search_scripts_and_styles' ) );

                // Add datatables ajax action
                add_action( 'wp_ajax_wc_ipm_searched_products', array($this, 'get_products_with_search_conditions') );
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

            if( $_POST['wc_eps']['submit_flag'] ) {
                // jQuery datatables plugin
                wp_enqueue_script('wc_eps_datatables_js', plugins_url('assets/libs/datatables/datatables.min.js', __FILE__), array(), false, true);
                wp_enqueue_style('wc_eps_datatables_css', plugins_url('assets/libs/datatables/datatables.min.css', __FILE__));

                // Search result page
                wp_enqueue_script('wc_eps_search_result_js', plugins_url('assets/js/search_result.js', __FILE__), array(), false, true);
                wp_enqueue_style('wc_eps_search_result_css', plugins_url('assets/css/search_result.css', __FILE__));
            } else {
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

        public function get_products_with_search_conditions() {
            global $wpdb;

            $where = $inner_join = $left_join = "";
            $conditions = $_SESSION['wc_eps_search_conditions'];

            // Name
            if( $conditions['name'] ) {
                $where .= " AND INSTR(p.post_title, '". $conditions['name'] ."')";
            }

            // SKU
            if( $conditions['sku'] ) {
                $inner_join .= " INNER JOIN wp_postmeta AS m1 ON m1.`post_id`=p.`ID` AND m1.`meta_key`='_sku' AND INSTR(m1.`meta_value`, '". $conditions['sku'] ."')";
            }

            // Is on sale
            if( $conditions['is_on_sale'] ) {
                $inner_join .= " INNER JOIN wp_postmeta AS m2 ON m2.`post_id`=p.`ID` AND m2.`meta_key`='_price'"
                    . " INNER JOIN wp_postmeta AS m3 ON m3.`post_id`=p.`ID` AND m3.`meta_key`='_regular_price'";
                $where .= " AND m3.`meta_value`*1 > m2.`meta_value`*1";
            }

            // Price min and max
            $price_min = $conditions['price_min'] ? floatval($conditions['price_min']) : '';
            $price_max = $conditions['price_max'] ? floatval($conditions['price_max']) : '';
            if( $price_min && $price_max ) {
                $inner_join .= " INNER JOIN wp_postmeta AS m4 ON m4.`post_id`=p.`ID` AND m4.`meta_key`='_price' AND m4.`meta_value`*1 BETWEEN ". $price_min ." AND ". $price_max;
            } else if( $price_min == "" && $price_max ) {
                $inner_join .= " INNER JOIN wp_postmeta AS m4 ON m4.`post_id`=p.`ID` AND m4.`meta_key`='_price' AND m4.`meta_value`*1 <= ". $price_max;
            } else if( $price_min && $price_max == "" ) {
                $inner_join .= " INNER JOIN wp_postmeta AS m4 ON m4.`post_id`=p.`ID` AND m4.`meta_key`='_price' AND m4.`meta_value`*1 >= ". $price_min;
            }

            // Categories
            if( $conditions['categories'] ) {
                $inner_join .= " INNER JOIN wp_term_relationships AS r1 ON r1.`object_id`=p.`ID` AND r1.`term_taxonomy_id` IN (". $conditions['categories'] .")";
            }

            // Attributes
            if( $conditions['attributes'] != '[]' ) {
                $attributes = implode( ',', json_decode(stripslashes($conditions['attributes'])) );
                $inner_join .= " INNER JOIN wp_term_relationships AS r2 ON r2.`object_id`=p.`ID` AND r2.`term_taxonomy_id` IN (". $attributes .")";
            }

            $sql = "SELECT COUNT(t.id) FROM"
                . " (SELECT p.ID AS id"
                . " FROM wp_posts AS p"
                . $inner_join
                . " WHERE p.`post_type`='product' AND p.`post_status`='publish'". $where
                . " GROUP BY p.`ID`) AS t";
            $total_products = $wpdb->get_var($sql);

            $sql = "SELECT p.ID"
                . " FROM wp_posts AS p"
                . $inner_join
                . " WHERE p.`post_type`='product' AND p.`post_status`='publish'". $where
                . " GROUP BY p.`ID`"
                . " LIMIT ". $_GET['start'] .", ". $_GET['length'];
            $ids = $wpdb->get_col($sql);
//            echo $wpdb->last_query; exit;

            /**
             * Get products info to show
             */
            $products = [];
            foreach ($ids as $id) {
                $attributes_html = "";

                $productObj = wc_get_product($id);
                $attributes = $productObj->get_attributes();
                foreach ($attributes as $key => $attribute) {
                    $attributes_html .= '<div class="attribute-row">';
                    $attributes_html .= '<strong>' . $attribute->get_taxonomy_object()->attribute_label . '</strong>';

                    $terms = [];
                    $taxonomy = $attribute->get_taxonomy();
                    foreach ( $attribute->get_options() as $term_id ) {
                        $terms[] = get_term($term_id, $taxonomy)->name;
                    }
                    $attributes_html .= '<span>'. implode(', ', $terms) .'<span>';

                    $attributes_html .= '</div>';
                }


                $products[] = [
                    'id' => $id,
                    'thumbnail' => $productObj->get_image(),
                    'name' => $productObj->get_name(),
                    'sku' => $productObj->get_sku(),
                    'price' => $productObj->get_price_html(),
                    'categories' => $productObj->get_categories(),
                    'attributes' => $attributes_html,
                    'stock' => $productObj->get_stock_status(),
                    'date' => $productObj->get_date_created()->date('d.m.Y')
                ];
            }

            $data = [
                'draw' => $_GET['draw'],
                'recordsTotal' => $total_products,
                'recordsFiltered' => $total_products,
                'data' => $products
            ];

            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        }

        public function show_search_page() {
            global $wpdb;

            if( $_POST['wc_eps']['submit_flag'] ) {
                $_SESSION['wc_eps_search_conditions'] = $_POST['wc_eps'];

                $this->show_search_result();
            } else {
                $_SESSION['wc_eps_search_conditions'] = "";

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
                <h1><?php _e( 'Enhanced Product Search - Product list', 'wc_eps' ); ?></h1>
                <table id="product_list" class="wp-list-table widefat fixed striped posts" width="100%">
                    <thead>
                    <tr>
                        <th class="thumbnail">Image</th>
                        <th class="name">Name</th>
                        <th class="sku">SKU</th>
                        <!--th class="stock">Stock</th-->
                        <th class="price">Price</th>
                        <th class="categories">Categories</th>
                        <th class="attributes">Attributes</th>
                        <th class="date">Date</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th class="thumbnail">Image</th>
                        <th class="name">Name</th>
                        <th class="sku">SKU</th>
                        <!--th class="stock">Stock</th-->
                        <th class="price">Price</th>
                        <th class="categories">Categories</th>
                        <th class="attributes">Attributes</th>
                        <th class="date">Date</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <?php
        }
    }

endif;

// Global for backwards compatibility.
$GLOBALS['wc_eps'] = WCImprovedProductManager::instance();
