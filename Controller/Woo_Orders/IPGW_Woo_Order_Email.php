<?php
namespace IPGW\Controller\Woo_Orders;

use IPGW\Abstract_Class;
use IPGW\Functions\Plugin_Constants;
use IPGW\Interfaces\Model_Interface;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class IPGW_Woo_Order_Email implements Model_Interface {

    private static $_instance;
    private $_constants;
    private $_ipgw_settings;

    /**
     * Class constructor.
     */
    public function __construct(Abstract_Class $main_plugin, Plugin_Constants $constants) {

        $this->_constants = $constants;

        $main_plugin->add_to_all_plugin_models($this);
        $main_plugin->add_to_public_models($this);

        $this->_ipgw_settings = get_option('woocommerce_IPGW_invoice_gateway_settings');

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     */
    public static function get_instance(Abstract_Class $main_plugin, Plugin_Constants $constants) {

        if (!self::$_instance instanceof self) {
            self::$_instance = new self($main_plugin, $constants);
        }

        return self::$_instance;

    }

    /**
     * Add invoice note to admin new order email.
     */
    public function add_invoice_note_to_admin_new_order_email($order, $sent_to_admin, $plain_text, $email) {

        if ($email instanceof \WC_Email_New_Order && $order instanceof \WC_Order) {

            if ($order->get_meta('_payment_method') == 'ipgw_invoice_gateway') {

                // $invoice_number = get_post_meta($order->get_id(), Plugin_Constants::Invoice_Number, true);
                $invoice_number = $order->get_meta(Plugin_Constants::Invoice_Number);

                if ($invoice_number) {
                    if ($plain_text) {
                        echo sprintf("\nInvoice Number: %s\n", $invoice_number);
                    } else {
                        echo '<span style="color: red; font-weight: 600;"><p>' . sprintf("\nInvoice Number: %s\n", $invoice_number) . '</p></span>';
                    }

                } else {
                    if ($plain_text) {
                        echo "\nNOTE: This order requires an invoice.\n";
                    } else {
                        echo '<span style="color: red; font-weight: 600;"><p>' . __('NOTE: This order requires an invoice.', 'IPGW') . '</p></span>';
                    }

                }

                // $po_number = get_post_meta($order->get_id(), Plugin_Constants::Purchase_Order_Number, true);
                $po_number = $order->get_meta(Plugin_Constants::Purchase_Order_Number);

                if ($po_number && $this->_ipgw_settings['ipgw_enable_purchase_order_number'] == 'yes') {
                    if ($plain_text) {
                        echo sprintf("\nPurchase Order Number: %s\n", $po_number);
                    } else {
                        echo '<p>' . sprintf("\nPurchase Order Number: %s\n", $po_number) . '</p>';
                    }

                }

            }

        }

    }

    /**
     * Add "paid by invoice" note on customer completed order email.
     */
    public function add_paid_by_invoice_note_on_customer_completed_order_email($order, $sent_to_admin, $plain_text, $email) {

        if ($email instanceof \WC_Email_Customer_Completed_Order && $order instanceof \WC_Order) {

            if ($order->get_meta('_payment_method') == 'ipgw_invoice_gateway') {

                // $invoice_number = get_post_meta($order->get_id(), Plugin_Constants::Invoice_Number, true);
                $invoice_number = $order->get_meta(Plugin_Constants::Invoice_Number);

                if ($invoice_number != "") {

                    if ($plain_text) {
                        echo "\n" . __('Paid via invoice number: ', 'IPGW') . $invoice_number . "\n";
                    } else {
                        echo sprintf(__('<br><p>Paid via invoice number: <b>%1$s</b></p>', 'IPGW'), $invoice_number);
                    }

                }

                // $po_number = get_post_meta($order->get_id(), Plugin_Constants::Purchase_Order_Number, true);
                $po_number = $order->get_meta(Plugin_Constants::Purchase_Order_Number);

                if ($po_number != "" && $this->_ipgw_settings['ipgw_enable_purchase_order_number'] == 'yes') {

                    if ($plain_text) {
                        echo __('Purchase order number: ', 'IPGW') . $po_number;
                    } else {
                        echo sprintf(__('<p>Purchase order number: <b>%1$s</b></p>', 'IPGW'), $po_number);
                    }

                }

            }

        }

    }

    /**
     * Execution
     */
    public function run() {

        add_action('woocommerce_email_order_details', array($this, 'add_invoice_note_to_admin_new_order_email'), 9, 4);
        add_filter('woocommerce_email_order_details', array($this, 'add_paid_by_invoice_note_on_customer_completed_order_email'), 9, 4);

    }

}
