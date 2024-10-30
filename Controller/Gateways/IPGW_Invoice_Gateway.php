<?php
namespace IPGW\Controller\Gateways;

use IPGW\Functions\Plugin_Constants;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class IPGW_Invoice_Gateway extends \WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
    public function __construct() {

        $this->id = "ipgw_invoice_gateway";
        $this->method_title = __('Invoice Payment Gateway for Woocommerce', 'IPGW');
        $this->method_description = __('IPGW Provide your customers to check out without immediate payment. You will be responsible for invoicing the customer and entering the invoice ID on their order. Then once you confirm payment is received, you can mark the order as Completed.', 'IPGW');

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Get settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);
        $this->enable_for_methods = $this->get_option('enable_for_methods', array());
        $this->enabled_for_wwp_role = $this->get_option('enabled_for_wwp_role', array());
        $this->enable_for_virtual = $this->get_option('enable_for_virtual', 'yes') === 'yes' ? true : false;
        $this->enable_for_po = $this->get_option('ipgw_enable_purchase_order_number', 'yes') === 'yes' ? true : false;
        $this->IPGW_order_status = $this->get_option('ipgw_order_status');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

        // Customer Emails
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);

        do_action('ipgw_invoice_gateway_construct');

    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {

        $shipping_methods = array();

        if (is_admin()) {

            foreach (WC()->shipping()->load_shipping_methods() as $method) {

                if (method_exists($method, 'get_method_title')) {
                    $shipping_methods[$method->id] = $method->get_method_title();
                } elseif (property_exists($method, 'method_title')) {
                    $shipping_methods[$method->id] = $method->method_title;
                } elseif (property_exists($method, 'title')) {
                    $shipping_methods[$method->id] = $method->title;
                }

            }            

        }

        //$allterms = get_terms('wholesale_user_roles', array('hide_empty' => false));

        // echo '<pre>';
        // print_r($roles);
        // echo '</pre>';
        // wp_die();
        
        // if ( !isset($allterms->errors) ) {
        //     foreach ( $allterms as $term) {
        //         $user_roles[$term->slug] = $term->name;
        //     } 
        // }

        global $wp_roles;
  	    $user_roles = $wp_roles->get_names();
        $statuses  = wc_get_order_statuses();
        
        $this->form_fields = apply_filters('ipgw_invoice_gateway_form_fields', array(
            'enabled' => array(
                'title' => __('Invoice Payment', 'IPGW'),
                'label' => __('Enable', 'IPGW'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'IPGW'),
                'type' => 'text',
                'description' => __('Payment method description that the customer will see on your checkout.', 'IPGW'),
                'default' => __('Invoice Payment', 'IPGW'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'IPGW'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your website.', 'IPGW'),
                'default' => __('Pay with invoice.', 'IPGW'),
                'desc_tip' => true,
            ),
            'instructions' => array(
                'title' => __('Instructions', 'IPGW'),
                'type' => 'textarea',
                'description' => __('Instructions that will be added to the thank you page.', 'IPGW'),
                'default' => __('Pay with invoice.', 'IPGW'),
                'desc_tip' => true,
            ),
            'enable_for_methods' => array(
                'title' => __('Enable for shipping methods', 'IPGW'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px;',
                'default' => '',
                'description' => __('If invoice gateway is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'IPGW'),
                'options' => $shipping_methods,
                'desc_tip' => true,
                'custom_attributes' => array(
                    'data-placeholder' => __('Select shipping methods', 'IPGW'),
                ),
            ),
            'enabled_for_wwp_role' => array(
                'title' => __('Enable for Specific Roles', 'IPGW'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px;',
                'default' => '',
                'description' => __('If invoice gateway is only enable for certain user roles, set it up here. Leave blank to enable for all users.', 'IPGW'),
                'options' => @$user_roles,
                'desc_tip' => true,
                'custom_attributes' => array(
                    'data-placeholder' => __('Select User Roles', 'IPGW'),
                ),
            ),

            'ipgw_order_status' => array(

                'title'=>__('Order Status','IPGW'),
                'type' => 'select',
                'class'=> 'wc-enhanced-select',
                'css'  => 'width:450px;',
                'default'=> '' ,
                'description' => __( 'Order status','IPGW' ),
                'options' => @$statuses,
                'desc_tip' => true,
                'custom_attributes'=>array(
                    'data-placeholder'=> __('Order Status','IPGW' ),
                ),

            ),

            'ipgw_enable_purchase_order_number' => array (
                'title' => __('Enable Purchase Order Number', 'IPGW'),
                'type' => 'checkbox',
                'desc' => __('Allow adding "Purchase Order Number" in the checkout page and option to add it in the edit order page.', 'IPGW'),
                'id' => 'ipgw_enable_purchase_order_number',
            ),
            'enable_for_virtual' => array(
                'title' => __('Accept for virtual orders', 'IPGW'),
                'label' => __('Accept invoice if the order is virtual', 'IPGW'),
                'type' => 'checkbox',
                'default' => 'yes',
            ),
        ));

    }

    /**
     * Show Purchase Order Number option
     */
    public function payment_fields() {
     

        if ( $this->enable_for_po ) {

            echo '<p><b>' . __('Purchase Order (optional)', 'IPGW') . '</b></p>';
            echo '<p><input type="text" name="ipgw_purchase_order_number" placeholder="PO Number"></p>';
            echo '<p>' . __('We will generate and send you an invoice for your order, if you have a PO number, please enter it.', 'IPGW') . '</p>';

        } else {
            $description = $this->get_description();
            if ($description) {
                echo wpautop(wptexturize($description)); // @codingStandardsIgnoreLine.
            }
        }

    }

    /**
     * Check if whole is enable and wholesale roles are available.
     */
    private function __get_user_role() {
        
        global $wp_roles;

        $all_roles = $wp_roles->get_names();

        $user_id = get_current_user_id();

        if ( !empty($user_id) ) {

            $user_info = get_userdata($user_id);
            
            $user_role = "";
			if( isset( $user_info->roles ) && is_array( $user_info->roles ) ) {
				$user_role = array_values( $user_info->roles );
	            $user_role = $user_role[0];
			}

            $user_role = reset($user_info->roles);

            foreach ( $all_roles as $slug => $role ) {
                
                if ( $slug == $user_role ) {
                    return $slug;
                }

            }

            return false;
        }
        
        return false;
    }

    /**
     * Check If The Gateway Is Available For Use.
     */
    public function is_available() {

        /**
         * This will check if gateway is set for all user roles or particular user roles.
         */
        $user_role = $this->__get_user_role();
        if ( (!empty($this->enabled_for_wwp_role) && !in_array($user_role, $this->enabled_for_wwp_role)) ) {

            return;
        }

        $order = null;
        $needs_shipping = false;

        // Test if shipping is needed first
        if (WC()->cart && WC()->cart->needs_shipping()) {
            $needs_shipping = true;
        } elseif (is_page(wc_get_page_id('checkout')) && 0 < get_query_var('order-pay')) {

            $order_id = absint(get_query_var('order-pay'));
            $order = wc_get_order($order_id);

            // Test if order needs shipping.
            if (0 < sizeof($order->get_items())) {

                foreach ($order->get_items() as $item) {

                    $_product = $order->get_product_from_item($item);

                    if ($_product && $_product->needs_shipping()) {

                        $needs_shipping = true;
                        break;

                    }

                }

            }

        }

        $needs_shipping = apply_filters('woocommerce_cart_needs_shipping', $needs_shipping);

        // Virtual order, with virtual disabled
        if (!$this->enable_for_virtual && !$needs_shipping) {
            return false;
        }

        if (!empty($this->enable_for_methods) && $needs_shipping) {

            // Only apply if all packages are being shipped via chosen methods, or order is virtual
            $chosen_shipping_methods_session = WC()->session->get('chosen_shipping_methods');
            $chosen_shipping_methods = isset($chosen_shipping_methods_session) ? array_unique($chosen_shipping_methods_session) : array();
            $check_method = false;

            if (is_object($order)) {

                if ($order->shipping_method) {
                    $check_method = $order->shipping_method;
                }

            } elseif (empty($chosen_shipping_methods) || sizeof($chosen_shipping_methods) > 1) {
                $check_method = false;
            } elseif (sizeof($chosen_shipping_methods) == 1) {
                $check_method = $chosen_shipping_methods[0];
            }

            if (!$check_method) {
                return false;
            }

            $found = false;

            foreach ($this->enable_for_methods as $method_id) {

                if (strpos($check_method, $method_id) === 0) {

                    $found = true;
                    break;

                }

            }

            if (!$found) {
                return false;
            }

        }

        return parent::is_available();

    }

    /**
     * Process the payment and return the result.
     */
    public function process_payment($order_id) {


        $order = wc_get_order($order_id);

        // Mark as processing or on-hold (payment won't be taken until delivery)
        $order->update_status(apply_filters('ipgw_invoice_gateway_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'processing', $order), __('Payment to be made upon invoice.', 'IPGW'));

        // Reduce stock levels
        wc_reduce_stock_levels($order_id);

        // Remove cart
        WC()->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );

    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page() {

        if ($this->instructions) {
            echo wpautop(wptexturize($this->instructions));
        }

    }

    /**
     * Add content to the WC emails.
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        
        if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method() && $order->get_status() != 'completed') {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }

        // $invoice_number = get_post_meta($order->get_id(), Plugin_Constants::Invoice_Number, true);
        $invoice_number = $order->get_meta(Plugin_Constants::Invoice_Number);

        if ( $invoice_number && !$sent_to_admin ) {
            if ($plain_text) {
                echo sprintf("\nInvoice Number: %d\n", $invoice_number);
            } else {
                echo '<span style="color: red; font-weight: 600;"><p>' . sprintf("\nInvoice Number: %d\n", $invoice_number) . '</p></span>';
            }

        }

    }

}
