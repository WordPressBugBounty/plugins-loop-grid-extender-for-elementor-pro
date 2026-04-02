<?php
/**
 * Plugin Name: Loop Grid Extender for Elementor - ACF Repeater & Smart Filters
 * Description: Use ACF repeater fields inside Elementor loop items and add smart dynamic dropdown taxonomy filters to the Elementor Loop Grid widget.
* Version: 1.1.7
* Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 * Author: Cool Plugins
 * Author URI: https://coolplugins.net/?utm_source=lge_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: loop-grid-extender-for-elementor-pro
 * Requires Plugins: elementor
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

!defined('LGEFEP_VERSION') && define('LGEFEP_VERSION', '1.1.7');
!defined('LGEFEP_FILE') && define('LGEFEP_FILE', __FILE__);
!defined('LGEFEP_PLUGIN_DIR') && define('LGEFEP_PLUGIN_DIR', plugin_dir_path(LGEFEP_FILE));
!defined('LGEFEP_PLUGIN_URL') && define('LGEFEP_PLUGIN_URL', plugin_dir_url(LGEFEP_FILE));
define('LGEFEP_FEEDBACK_API', 'https://feedback.coolplugins.net/');

if ( ! function_exists( 'is_plugin_active' ) ) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if(!class_exists('LGEFEP_ADDON')){

    class LGEFEP_ADDON{
    
        private static $instance = null;
    
        public static function get_instance(){
            if(self::$instance == null){
                self::$instance = new self();
            }
            return self::$instance;
        }
    
        public function __construct(){
            register_activation_hook(LGEFEP_FILE, [$this, 'activate']);
            register_deactivation_hook(LGEFEP_FILE, [$this, 'deactivate']);
            add_action('plugins_loaded', array($this, 'lgefep_plugins_loaded'));
            add_action("elementor/init", [$this, 'init']);
            add_action( 'init', array( $this, 'is_compatible' ) );
        }
    
        public function init(){
            if(defined('ELEMENTOR_PRO_VERSION')){
                require_once LGEFEP_PLUGIN_DIR . 'includes/class-taxonomy-addon-render.php';
                require_once LGEFEP_PLUGIN_DIR . 'includes/class-taxonomy-addon-controls.php';

                if(class_exists('ACF')){
                    require_once LGEFEP_PLUGIN_DIR . 'includes/class-register-dynamic-acf-repeater-tag.php';
                    require_once LGEFEP_PLUGIN_DIR . 'includes/acf-repeater-dynamic-tag-controller/acf-repeater-tag-manager.php';
                    require_once LGEFEP_PLUGIN_DIR . 'includes/acf-repeater-dynamic-tag-controller/acf-loop-grid-controller.php';
                }
            }
        }

        public function lgefep_plugins_loaded(){
            if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
                return false;
            }
            
            if (is_admin()) {
                require_once __DIR__ . '/admin/feedback/admin-feedback-form.php';
                require_once __DIR__ . '/admin/class-admin-notice.php';
                
                // Initialize admin notice
                if ( class_exists( 'lgefep_admin_notices' ) ) {
                    new lgefep_admin_notices();
                }
            }
        }


        public function activate(){
            update_option('LGEFEP_VERSION', LGEFEP_VERSION);
            update_option( 'LGEFEP_TYPE', 'FREE' );
            add_option( 'lgefep_install_date', gmdate( 'Y-m-d h:i:s' ) );
            add_option('lgefep_initial_save_version', LGEFEP_VERSION);
        }
    
        public function deactivate(){
        }

        /**
         * Check if Elementor Pro is installed and activated
         */
        public function is_compatible() {
            add_action( 'admin_init', array( $this, 'is_elementor_pro_exist' ) );
        }

        /**
         * Function use for deactivate plugin if elementor pro not exist
         */
        public function is_elementor_pro_exist() {
            if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
                add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
                return false;
            }
        }

        /**
         * Show notice to enable elementor pro
         */
        public function admin_notice_missing_main_plugin() {
            $message = sprintf(
                /* translators: %1$s: Plugin name, %2$s: Required plugin name */
                esc_html__(
                    '%1$s requires %2$s to be installed and activated.',
                    'loop-grid-extender-for-elementor-pro'
                ),
                esc_html__( 'Loop Grid Extender for Elementor Pro', 'loop-grid-extender-for-elementor-pro' ),
                esc_html__( 'Elementor Pro', 'loop-grid-extender-for-elementor-pro' )
            );
            printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', esc_html( $message ) );
            deactivate_plugins( plugin_basename( __FILE__ ) );
        }
    }
    
    $lgefep_loop_grid_extender_for_elementor_pro = LGEFEP_ADDON::get_instance();
}


