<?php
/**
 * Plugin Name:     EDD FES Rest API
 * Plugin URI:
 * Description:
 * Version:         1.0.0
 * Author:          rubengc
 * Author URI:      http://rubengc.com
 * Text Domain:     edd-fes-rest-api
 *
 * @package         EDD\FES_Rest_API
 * @author          rubengc
 * @copyright       Copyright (c) rubengc
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_FES_Rest_API' ) ) {

    /**
     * Main EDD_FES_Rest_API class
     *
     * @since       1.0.0
     */
    class EDD_FES_Rest_API {

        /**
         * @var         EDD_FES_Rest_API $instance The one true EDD_FES_Rest_API
         * @since       1.0.0
         */
        private static $instance;

        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_FES_Rest_API
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_FES_Rest_API();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }
            return self::$instance;
        }
        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_FES_REST_API_VER', '1.0.0' );
            // Plugin path
            define( 'EDD_FES_REST_API_DIR', plugin_dir_path( __FILE__ ) );
            // Plugin URL
            define( 'EDD_FES_REST_API_URL', plugin_dir_url( __FILE__ ) );
        }
        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            require_once EDD_FES_REST_API_DIR . 'includes/class-controller.php';
            require_once EDD_FES_REST_API_DIR . 'includes/class-products-controller.php';
            require_once EDD_FES_REST_API_DIR . 'includes/class-profile-controller.php';

            new FES_Rest_API_Products_Controller();
            new FES_Rest_API_Profile_Controller();
        }
        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Removed all default routes for testing purposes

            // https://gist.github.com/ricomadiko/25e10a9f35f2f4cdf13550a97d8911ad
            remove_action( 'rest_api_init', 'create_initial_rest_routes', 0 );
            remove_action( 'rest_api_init', 'wp_oembed_register_route' );

            // Adds an annotation on download page if download was created/updated thought API
            add_action( 'edit_form_before_permalink', array( $this, 'download_label' ) );
        }
        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_FES_REST_API_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_fes_rest_api_languages_directory', $lang_dir );
            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-fes-rest-api' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-fes-rest-api', $locale );
            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-fes-rest-api/' . $mofile;
            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-fes-rest-api/ folder
                load_textdomain( 'edd-fes-rest-api', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-fes-rest-api/languages/ folder
                load_textdomain( 'edd-fes-rest-api', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-fes-rest-api', false, $lang_dir );
            }
        }

        /**
         * Adds an annotation on download page if download was created/updated thought API
         *
         * @access      public
         * @since       1.0.0
         * @param       WP_Post $post The current post
         */
        public function download_label( $post ) {
            if($post->post_type == 'download') {
                $created_from_api = get_post_meta( $post->ID, 'edd_fes_rest_api_created_from_api', true );
                $updated_from_api = get_post_meta( $post->ID, 'edd_fes_rest_api_updated_from_api', true );

                $box_text = ' from API';

                if($created_from_api != '' && $updated_from_api != '') {
                    $box_text = __( 'Created and updated from API', 'edd-fes-rest-api' );
                } else if($created_from_api != '') {
                    $box_text = __( 'Created from API', 'edd-fes-rest-api' );
                } else if($updated_from_api != '') {
                    $box_text = __( 'Updated from API', 'edd-fes-rest-api' );
                }

                if($box_text != '') {
                    ?>
                    <div id="edd-fes-rest-api-box" style="
                        display: inline-block;
                        float: right;
                        height: 24px;
                        line-height: 25px;
                        padding: 0 10px 1px;
                        font-size: 11px;
                        font-weight: 500;
                        margin: 0;
                        -webkit-border-radius: 3px;
                        border-radius: 3px;
                        background-color: #90da36;
                        border-bottom: 2px solid #76bb22;
                        color: #fff;
                    ">
                        <span><?php echo $box_text; ?></span>
                    </div>
                    <?php
                }
            }
        }
    }
} // End if class_exists check
/**
 * The main function responsible for returning the one true EDD_FES_Rest_API
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_FES_Rest_API The one true EDD_FES_Rest_API
 */
function edd_fes_rest_api() {
    return EDD_FES_Rest_API::instance();
}
add_action( 'plugins_loaded', 'edd_fes_rest_api' );