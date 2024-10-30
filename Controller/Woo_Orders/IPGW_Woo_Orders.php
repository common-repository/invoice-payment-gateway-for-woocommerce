<?php
namespace IPGW\Controller\Woo_Orders;

use IPGW\Abstract_Class;
use IPGW\Functions\Plugin_Constants;
use IPGW\Interfaces\Model_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Exit if accessed directly


class IPGW_Woo_Orders implements Model_Interface {


	private static $_instance;
	private $_constants;
	private $_ipgw_settings;


	/**
	 * Class constructor.
	 */
	public function __construct( Abstract_Class $main_plugin, Plugin_Constants $constants ) {

		$this->_constants = $constants;
		$main_plugin->add_to_all_plugin_models( $this );

		$this->_ipgw_settings = get_option( 'woocommerce_IPGW_invoice_gateway_settings' );

	}

	/**
	 * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
	 */
	public static function get_instance( Abstract_Class $main_plugin, Plugin_Constants $constants ) {

		if ( ! self::$_instance instanceof self ) {
			self::$_instance = new self( $main_plugin, $constants );
		}

		return self::$_instance;

	}

	/**
	 * Add order invoice meta box.
	 */
	public function add_order_invoice_meta_box() {

		add_meta_box(
			'ipgw-order-invoice',
			__( 'Order Invoice', 'IPGW' ),
			array( $this, 'view_order_invoice_meta_box' ),
			'shop_order',
			'side',
			'default'
		);

	}

	/**
	 * Order invoice meta box.
	 */
	public function view_order_invoice_meta_box() {

		include IPGW_PATH . '/views/view-order-invoice-meta-box.php';

	}

	/**
	 * Add invoice number field.
	 */
	public function add_invoice_number_field() {

		if ( $this->_ipgw_settings['ipgw_enable_purchase_order_number'] == 'yes' ) {

			woocommerce_wp_text_input(
				array(
					'id'        => Plugin_Constants::Purchase_Order_Number,
					'style'     => 'width: 100%;',
					'label'     => __( 'Purchase Order Number', 'IPGW' ),
					'type'      => 'text',
					'data_type' => 'text',
				)
			);

		}

		woocommerce_wp_text_input(
			array(
				'id'          => Plugin_Constants::Invoice_Number,
				'style'       => 'width: 100%;',
				'label'       => __( 'Invoice Number', 'IPGW' ),
				'description' => __( '<br><strong>NOTE:</strong> Before changing status to complete, enter invoice number and update the order first.', 'IPGW' ),
				'type'        => 'text',
				'data_type'   => 'text',
			)
		);

		wp_nonce_field( 'ipgw_action_save_invoice_number', 'ipgw_nonce_save_invoice_number' );

	}

	/**
	 * Check validity of a save post action.
	 */
	private function __check_if_valid_save_post_action( $post_id, $post_type ) {

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || get_post_type() != $post_type || empty( $_POST ) ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Save invoice data.
	 */
	public function save_invoice_data( $post_id ) {

		// On manual click of 'update' , 'publish' or 'save draft' button, execute code inside the if statement
		if ( $this->__check_if_valid_save_post_action( $post_id, 'shop_order' ) ) {

			$order = wc_get_order( $post_id );

			if ( is_a( $order, 'WC_Admin_Order' ) || is_a( $order, 'WC_Order' ) ) {

				$this->_save_invoice_number( $post_id, $order );
			}
		}

	}

	/**
	 * Save invoice number.
	 */
	private function _save_invoice_number( $post_id, $order ) {

		// Check nonce
		if ( isset( $_POST['ipgw_nonce_save_invoice_number'] ) && wp_verify_nonce( $_POST['ipgw_nonce_save_invoice_number'], 'ipgw_action_save_invoice_number' ) ) {

			$new_invoice_number = isset( $_POST[ Plugin_Constants::Invoice_Number ] ) ? filter_var( trim( $_POST[ Plugin_Constants::Invoice_Number ] ), FILTER_SANITIZE_STRING ) : '';

			// $existing_invoice_number = get_post_meta( $post_id, Plugin_Constants::Invoice_Number, true );
			$existing_invoice_number = $order->get_meta(Plugin_Constants::Invoice_Number);

			$this->_log_invoice_number_activity( $new_invoice_number, $existing_invoice_number, $post_id );

			// update_post_meta( $post_id, Plugin_Constants::Invoice_Number, $new_invoice_number );
			$order->update_meta_data(Plugin_Constants::Invoice_Number, $new_invoice_number);

			if ( isset( $_POST[ Plugin_Constants::Purchase_Order_Number ] ) ) {

				$new_invoice_number      = isset( $_POST[ Plugin_Constants::Purchase_Order_Number ] ) ? filter_var( trim( $_POST[ Plugin_Constants::Purchase_Order_Number ] ), FILTER_SANITIZE_STRING ) : '';
				// $existing_invoice_number = get_post_meta( $post_id, Plugin_Constants::Purchase_Order_Number, true );
				$existing_invoice_number = $order->get_meta(Plugin_Constants::Purchase_Order_Number);

				$this->_log_invoice_number_activity( $new_invoice_number, $existing_invoice_number, $post_id, 'purchase order number' );

				// update_post_meta( $post_id, Plugin_Constants::Purchase_Order_Number, $new_invoice_number );
				$order->update_meta_data(Plugin_Constants::Purchase_Order_Number, $new_invoice_number);

			}

			$order->save();
		}

	}

	/**
	 * Log invoice number activity.
	 */
	private function _log_invoice_number_activity( $new_invoice_number, $existing_invoice_number, $post_id, $type = 'invoice number' ) {

		if ( $new_invoice_number == $existing_invoice_number ) {
			return;
		}

		$order = wc_get_order( $post_id );
		$user  = wp_get_current_user();

		if ( is_a( $order, 'WC_Order' ) ) {

			if ( $new_invoice_number != '' && $existing_invoice_number == '' ) {
				$order->add_order_note( sprintf( __( '%1$s added %2$s %3$s.', 'IPGW' ), $user->display_name, $type, $new_invoice_number ) );
			} elseif ( $new_invoice_number == '' && $existing_invoice_number != '' ) {
				$order->add_order_note( sprintf( __( '%1$s removed %2$s %3$s.', 'IPGW' ), $user->display_name, $type, $existing_invoice_number ) );
			} elseif ( $new_invoice_number != $existing_invoice_number ) {
				$order->add_order_note( sprintf( __( '%1$s updated %2$s from %3$s to %4$s.', 'IPGW' ), $user->display_name, $type, $existing_invoice_number, $new_invoice_number ) );
			}
		}

	}

	/**
	 * Save Purchase Order Number after order is processed.
	 */
	public function wc_checkout_order_processed( $order_id, $posted_data, $order ) {

		if ( isset( $_REQUEST[ Plugin_Constants::Purchase_Order_Number ] ) && ! empty( $_REQUEST[ Plugin_Constants::Purchase_Order_Number ] ) ) {
			$po_number = sanitize_text_field( $_REQUEST[ Plugin_Constants::Purchase_Order_Number ] );
			// update_post_meta( $order_id, Plugin_Constants::Purchase_Order_Number, $po_number );
			$order->update_meta_data( Plugin_Constants::Purchase_Order_Number, $po_number );
			$order->save();
		}

	}

	/**
     * change order status which selected from invoice payment gatway setting
     **/ 
    public function ipgw_update_order_status($order_id)
    {
	    if (!$order_id) return;     
        $order = wc_get_order( $order_id );
	    if ( 'ipgw_invoice_gateway' === $order->payment_method ) {
		    $payment_gateway = wc_get_payment_gateway_by_order( $order );
		    $IPGW_order_status = $payment_gateway->IPGW_order_status;
		    $order->update_status( $IPGW_order_status );
	    }
    }


	/**
	 * Execution
	 */
	public function run() {

		add_action( 'add_meta_boxes', array( $this, 'add_order_invoice_meta_box' ) );
		add_action( 'ipgw_invoice_gateway_meta_box', array( $this, 'add_invoice_number_field' ) );
		add_action( 'save_post', array( $this, 'save_invoice_data' ), 10, 1 );

		// Order Processed
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'wc_checkout_order_processed' ), 10, 3 );
		// order status change
		add_action('woocommerce_thankyou', array($this, 'ipgw_update_order_status'));

	}

}
