<?php
/**
 * Plugin Name: Invoice Payment Gateway For Woocommerce
 * Description: Invoice Payment Gateway for Woocommerce provides your customers to check out without immediate payment.
 * Version: 1.2
 * Author: wpexpertsio
 * Author URI: http://wpexpert.io/
 * Developer: wpexpertsio
 * Developer URI: https://wpexperts.io/
 * Text Domain: IPGW
 * Domain Path: /languages
 * WC requires at least: 8.0
 * WC tested up to: 8.8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use IPGW\Abstract_Class;
use IPGW\Interfaces\Model_Interface;
use IPGW\Functions\Plugin_Constants;
use IPGW\Controller\Boot;
use IPGW\Controller\Woo_Orders\IPGW_Woo_Orders;
use IPGW\Controller\Woo_Orders\IPGW_Woo_Order_Email;

if ( ! defined( 'IPGW_PATH' ) ) {
	define( 'IPGW_PATH', plugin_dir_path( __FILE__ ) );
}

/**
 * Register plugin autoloader.
 */
spl_autoload_register(
	function( $class_name ) {

		if ( strpos( $class_name, 'IPGW\\' ) === 0 ) { // Only do autoload for our plugin files

			  $class_file = str_replace( array( '\\', 'IPGW' . DIRECTORY_SEPARATOR ), array( DIRECTORY_SEPARATOR, '' ), $class_name ) . '.php';

			  require_once plugin_dir_path( __FILE__ ) . $class_file;

		}

	}
);

/**
 * The main plugin class.
 */
class OUR_MAIN_CLASS extends Abstract_Class {

	/*
	|--------------------------------------------------------------------------
	| Class Properties
	|--------------------------------------------------------------------------
	*/

	/**
	 * Single main instance of Plugin IPGW plugin.
	 */
	private static $_instance;

	const PLUGIN_NAME = 'Invoice Payment Gateway For Woocommerce';

	/**
	 * Array of missing external plugins that this plugin is depends on.
	 */
	private $_failed_dependencies;




	/*
	|--------------------------------------------------------------------------
	| Class Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * IPGW constructor.
	 */
	public function __construct() {

		if ( $this->_check_plugin_dependencies() !== true ) {

			// Display notice that plugin dependency is not present.
			add_action( 'admin_notices', array( $this, 'missing_plugin_dependencies_notice' ) );

		} else {

			// Lock 'n Load
			$this->_initialize_plugin_components();
			$this->_run_plugin();

		}

	}

	/**
	 * Ensure that only one instance of Invoice Gateway For WooCommerce is loaded or can be loaded (Singleton Pattern).
	 */
	public static function get_instance() {

		if ( ! self::$_instance instanceof self ) {
			self::$_instance = new self();
		}

		return self::$_instance;

	}

	/**
	 * Check for external plugin dependencies.
	 */
	private function _check_plugin_dependencies() {

		// Makes sure the plugin is defined before trying to use it
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->failed_dependencies = array();

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

			$this->failed_dependencies[] = array(
				'plugin-key'       => 'woocommerce',
				'plugin-name'      => 'WooCommerce', // We don't translate this coz this is the plugin name
				'plugin-base-name' => 'woocommerce/woocommerce.php',
			);

		}

		return ! empty( $this->failed_dependencies ) ? $this->failed_dependencies : true;

	}

	/**
	 * Add notice to notify users that some plugin dependencies of this plugin is missing.
	 */
	public function missing_plugin_dependencies_notice() {

		if ( ! empty( $this->failed_dependencies ) ) {

			$admin_notice_msg = '';

			foreach ( $this->failed_dependencies as $failed_dependency ) {

				$admin_notice_msg .= sprintf( __( '<br/>Please ensure you have the <strong>%1$s</strong> plugin installed and activated.<br/>', 'IPGW' ), $failed_dependency['plugin-name'] );

			} ?>

			<div class="notice notice-error">
				<p>
					<?php _e( '<b>Invoice Payment Gateway For Woocommerce</b> plugin missing dependency.<br/>', 'IPGW' ); ?>
					<?php echo $admin_notice_msg; ?>
				</p>
			</div>

			<?php
		}

	}

	/**
	 * Initialize plugin components.
	 */
	private function _initialize_plugin_components() {

		$plugin_constants = Plugin_Constants::get_instance();

		Boot::get_instance( $this, $plugin_constants );
		IPGW_Woo_Orders::get_instance( $this, $plugin_constants );
		IPGW_Woo_Order_Email::get_instance( $this, $plugin_constants );

	}

	/**
	 * Run the plugin. ( Runs the various plugin components ).
	 */
	private function _run_plugin() {

		foreach ( $this->__all_models as $model ) {
			if ( $model instanceof Model_Interface ) {
				$model->run();
			}
		}

	}

}

/**
 * Returns the main instance of IPGW to prevent the need to use globals.
 */
function IPGW() {

	return OUR_MAIN_CLASS::get_instance();

}

$GLOBALS['IPGW'] = IPGW();
