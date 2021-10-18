<?php
/**
 * Plugin Name: WCMP IPAY Mobilemoney PAYMENT GATEWAY
 * Plugin URI: https://https://ipayafrica.com
 * Description: WCMP Marketplace iPay MobileMoney vendor payment gateway 
 * Author: iPay
 * Version: 1.2.0
 * Author URI: https://ipayafrica.com
 * Text Domain: wcmp-pg-ipay_mobilemoney
 * Domain Path: /lang/
 * WC requires at least: 3.0.0
 * WC tested up to: 3.4.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function wcmp_ipay_mobilemoney_settings($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wcmp-setting-admin&tab=payment') . '">Settings</a>';
    $ipay_docs = '<a href="https://dev.ipayafrica.com/">Docs</a>';
    array_push($links, $settings_link);
    array_push($links, $ipay_docs);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'wcmp_ipay_mobilemoney_settings');

if (!class_exists('WCMP_Ipay_MobileMoney_Payment_Gateway_Dependencies')) {
    require_once 'includes/class-wcmp-ipay_mobilemoney-payment-gateway-dependencies.php';
}
require_once 'includes/wcmp-ipay_mobilemoney-payment-gateway-core-functions.php';
require_once 'wcmp-ipay_mobilemoney-payment-gateway-config.php';

if (!defined('WCMP_IPAY_MOBILEMONEY_PAYMENT_GATEWAY_PLUGIN_TOKEN')) {
    exit;
}
if (!defined('WCMP_IPAY_MOBILEMONEY_PAYMENT_GATEWAY_TEXT_DOMAIN')) {
    exit;
}

if(!WCMP_Ipay_MobileMoney_Payment_Gateway_Dependencies::woocommerce_active_check()){
    add_action('admin_notices', 'woocommerce_inactive_notice');
}

if (!class_exists('WCMP_Ipay_MobileMoney_Payment_Gateway') && WCMP_Ipay_MobileMoney_Payment_Gateway_Dependencies::woocommerce_active_check()) {
    require_once( 'classes/class-wcmp-ipay_mobilemoney-payment-gateway.php' );
   
    global $WCMP_Ipay_MobileMoney_Payment_Gateway;
    $WCMP_Ipay_MobileMoney_Payment_Gateway = new WCMP_Ipay_MobileMoney_Payment_Gateway(__FILE__);    
    $GLOBALS['WCMP_Ipay_MobileMoney_Payment_Gateway'] = $WCMP_Ipay_MobileMoney_Payment_Gateway;
    
}
