<?php
namespace IPGW\Controller;

use IPGW\Abstract_Class;
use IPGW\Interfaces\Model_Interface;
use IPGW\Interfaces\Activatable_Interface;
use IPGW\Interfaces\Initiable_Interface;
use IPGW\Functions\Plugin_Constants;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class Boot implements Model_Interface {

    
    /**
     * Property that holds the single main instance of Boot.
     */
    private static $_instance;

    private $_constants;

    /**
     * Array of models implementing the IPGW\Interfaces\Activatable_Interface.
     * 
     */
    private $_activatables;

    /**
     * Array of models implementing the IPGW\Interfaces\Initiable_Interface.
     *
     */
    private $_initiables;


    /**
     * Class constructor.
     */
    public function __construct( Abstract_Class $main_plugin , Plugin_Constants $constants , array $activatables = array() , array $initiables = array() ) {

        $this->_constants        = $constants;
        $this->_activatables     = $activatables;
        $this->_initiables       = $initiables;

        $main_plugin->add_to_all_plugin_models( $this );

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     */
    public static function get_instance( Abstract_Class $main_plugin , Plugin_Constants $constants , array $activatables = array() , array $initiables = array() ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $activatables , $initiables );
        
        return self::$_instance;

    }

    /**
     * Load plugin text domain.
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain( Plugin_Constants::TEXT_DOMAIN , false , $this->_constants->PLUGIN_BASENAME() . '/languages' );

    }

    /**
     * Method that houses the logic relating to activating the plugin.
     */
    public function activate_plugin( $network_wide ) {

        global $wpdb;

        if ( is_multisite() ) {

            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    $this->_activate_plugin( $blog_id );

                }

                restore_current_blog();

            } else
                $this->_activate_plugin( $wpdb->blogid ); // activated on a single site, in a multi-site

        } else
            $this->_activate_plugin( $wpdb->blogid ); // activated on a single site

    }

    /**
     * Method to initialize a newly created site in a multi site set up.
     *
     */
    public function new_mu_site_init( $blog_id , $user_id , $domain , $path , $site_id , $meta ) {

        if ( is_plugin_active_for_network( 'invoice-gateway-for-woocommerce-wholesale/invoice-gateway-for-woocommerce-wholesale.php' ) ) {

            switch_to_blog( $blog_id );
            $this->_activate_plugin( $blog_id );
            restore_current_blog();

        }

    }

    /**
     * Initialize plugin settings options.
     */
    private function _initialize_plugin_settings_options() {

        // Set initial value of 'yes' for the option that sets the option that specify whether to delete the options on plugin uninstall. Optionception.
        update_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS, 'yes' );
    }
    
    /**
     * Actual function that houses the code to execute on plugin activation.
     */
    private function _activate_plugin( $blogid ) {

        // Initialize settings options
        $this->_initialize_plugin_settings_options();

        // Execute 'activate' contract of models implementing IPGW\Interfaces\Activatable_Interface
        foreach ( $this->_activatables as $activatable )
            if ( $activatable instanceof Activatable_Interface )
                $activatable->activate();
        
        // Update current installed plugin version
        update_option( Plugin_Constants::INSTALLED_VERSION , Plugin_Constants::VERSION );

        flush_rewrite_rules();

    }

    /**
     * Method that houses the logic relating to deactivating the plugin.
     */
    public function deactivate_plugin( $network_wide ) {

        global $wpdb;

        // check if it is a multisite network
        if ( is_multisite() ) {

            // check if the plugin has been activated on the network or on a single site
            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                
                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    $this->_deactivate_plugin( $wpdb->blogid );

                }

                restore_current_blog();

            } else
                $this->_deactivate_plugin( $wpdb->blogid ); // activated on a single site, in a multi-site
            
        } else
            $this->_deactivate_plugin( $wpdb->blogid ); // activated on a single site
        
    }

    /**
     * Actual method that houses the code to execute on plugin deactivation.
     */
    private function _deactivate_plugin( $blogid ) {

        flush_rewrite_rules();

    }

    /**
     * Method that houses codes to be executed on init hook.
     */
    public function initialize() {
        
        // Execute 'initialize' contract of models implementing IPGW\Interfaces\Initiable_Interface
        foreach ( $this->_initiables as $initiable )
            if ( $initiable instanceof Initiable_Interface )
                $initiable->initialize();
        
    }

    public function load_backend_scripts( $handle ) {

        $screen = get_current_screen();

        $post_type = get_post_type();
        if ( !$post_type && isset( $_GET[ 'post_type' ] ) )
            $post_type = sanitize_text_field( $_GET[ 'post_type' ] );

        if ( ( $handle == 'post-new.php' || $handle == 'post.php' ) && $post_type == 'shop_order' ) {

            wp_enqueue_style( 'IPGW_wc-order_css' , $this->_constants->CSS_ROOT_URL() . 'wc-order.css' , array() , Plugin_Constants::VERSION , 'all' );

        }

    }

    /**
     * Execute plugin Boot code.
     */
    public function run() {

        // load scripts to admin
        add_action( 'admin_enqueue_scripts' , array( $this , 'load_backend_scripts' ) , 10 , 1 );

        // Internationalization
        add_action( 'plugins_loaded' , array( $this , 'load_plugin_textdomain' ) );

        // Execute plugin activation/deactivation
        register_activation_hook( $this->_constants->MAIN_PLUGIN_FILE_PATH() , array( $this , 'activate_plugin' ) );
        register_deactivation_hook( $this->_constants->MAIN_PLUGIN_FILE_PATH() , array( $this , 'deactivate_plugin' ) );

        // Execute plugin initialization ( plugin activation ) on every newly created site in a multi site set up
        add_action( 'wpmu_new_blog' , array( $this , 'new_mu_site_init' ) , 10 , 6 );

        // Execute codes that need to run on 'init' hook
        add_action( 'init' , array( $this , 'initialize' ) );


        // Register Invoice Payment Gateway
        add_filter( 'woocommerce_payment_gateways' , function( $methods ) {

            $methods[] = 'IPGW\Controller\Gateways\IPGW_Invoice_Gateway'; 
            return $methods;

        } , 10 , 1 );

    }

}
