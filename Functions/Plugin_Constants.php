<?php
namespace IPGW\Functions;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Plugin_Constants {

    private static $_instance;

    const TOKEN = 'IPGW';
    const INSTALLED_VERSION = 'IPGW_installed_version';
    const VERSION = '1.1';
    const TEXT_DOMAIN = 'IPGW';
    const THEME_TEMPLATE_PATH = 'invoice-gateway-for-woocommerce-wholesale';

    // Order Post Meta
    const Invoice_Number = 'ipgw_invoice_number';
    const Purchase_Order_Number = 'ipgw_purchase_order_number';

    // Help Section
    const CLEAN_UP_PLUGIN_OPTIONS = 'ipgw_clean_up_plugin_options';

    public function __construct() {

        // Path constants
        $this->_MAIN_PLUGIN_FILE_PATH = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'invoice-payment-gateway-woocommerce' . DIRECTORY_SEPARATOR . 'invoice-payment-gateway-woocommerce.php';
        $this->_PLUGIN_DIR_PATH = plugin_dir_path($this->_MAIN_PLUGIN_FILE_PATH);
        $this->_PLUGIN_DIR_URL = plugin_dir_url($this->_MAIN_PLUGIN_FILE_PATH);
        $this->_PLUGIN_BASENAME = plugin_basename(dirname($this->_MAIN_PLUGIN_FILE_PATH));
        $this->_CSS_ROOT_URL = $this->_PLUGIN_DIR_URL . 'css/';
        $this->_VIEWS_ROOT_PATH = $this->_PLUGIN_DIR_PATH . 'views/';
    }

    public static function get_instance() {

        if (!self::$_instance instanceof self) {
            self::$_instance = new self();
        }

        return self::$_instance;

    }

    public function MAIN_PLUGIN_FILE_PATH() {
        return $this->_MAIN_PLUGIN_FILE_PATH;
    }

    public function PLUGIN_DIR_PATH() {
        return $this->_PLUGIN_DIR_PATH;
    }

    public function PLUGIN_DIR_URL() {
        return $this->_PLUGIN_DIR_URL;
    }

    public function PLUGIN_BASENAME() {
        return $this->_PLUGIN_BASENAME;
    }

    public function CSS_ROOT_URL() {
        return $this->_CSS_ROOT_URL;
    }

    public function VIEWS_ROOT_PATH() {
        return $this->_VIEWS_ROOT_PATH;
    }
}
