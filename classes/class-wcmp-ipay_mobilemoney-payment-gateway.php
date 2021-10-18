<?php

/**
 * WCMP PLUGIN GATEWAY CLASS
 */
class WCMP_Ipay_MobileMoney_Payment_Gateway
{
	public $plugin_url;
    public $plugin_path;
    public $version;
    public $token;
    public $text_domain;
    private $file;
    public $license;
    public $payment_gateway;
    public $ipay_mobilemoney_admin;
    public $connect_ipay_mobilemoney;   
	
	function __construct($file)
	{
		$this->file = $file;
        $this->plugin_url = trailingslashit(plugins_url('', $plugin = $file));
        $this->plugin_path = trailingslashit(dirname($file));
        $this->token = WCMP_IPAY_MOBILEMONEY_PAYMENT_GATEWAY_PLUGIN_TOKEN;
        $this->text_domain = WCMP_IPAY_MOBILEMONEY_PAYMENT_GATEWAY_TEXT_DOMAIN;
        $this->version = WCMP_IPAY_MOBILEMONEY_PAYMENT_GATEWAY_PLUGIN_VERSION;
               
        add_action('init', array(&$this, 'init'), 0);
	}

	 /**
     * initilize plugin on WP init
     */
    function init() {
        // Init Text Domain
        $this->load_plugin_textdomain();

        if (class_exists('WCMp')) {
            //Mobile money
            require_once $this->plugin_path . 'classes/class-wcmp-gateway-ipay_mobilemoney.php';
            $this->connect_ipay_mobilemoney = new WCMp_Gateway_Ipay_MobileMoney();

            require_once $this->plugin_path . 'classes/class-wcmp-ipay_mobilemoney-payment-gateway-admin.php';
            $this->ipay_mobilemoney_admin = new WCMP_Ipay_MobileMoney_Payment_Gateway_Admin();
           
            add_filter('wcmp_payment_gateways', array(&$this, 'add_wcmp_ipay_mobilemoney_payment_gateway'));
        }

        
    }

    public function add_wcmp_ipay_mobilemoney_payment_gateway($load_gateways) {
        $load_gateways[] = 'WCMp_Gateway_Ipay_MobileMoney';              
        return $load_gateways;
    }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present
     *
     * @access public
     * @return void
     */
    public function load_plugin_textdomain() {
        $locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
        $locale = apply_filters('plugin_locale', $locale, 'wcmp-ipay_mobilemoney-payment-gateway');
        load_textdomain('wcmp-ipay_mobilemoney-payment-gateway', WP_LANG_DIR . '/wcmp-ipay_mobilemoney-payment-gateway/wcmp-ipay_mobilemoney-payment-gateway-' . $locale . '.mo');
        load_plugin_textdomain('wcmp-ipay_mobilemoney-payment-gateway', false, plugin_basename(dirname(dirname(__FILE__))) . '/languages');
    }

    /**
     * Helper method to load other class
     * @param type $class_name
     * @param type $dir
     */
    public function load_class($class_name = '', $dir = '') {
        $new_token = 'wcmp';
        if ('' != $class_name && '' != $new_token) {
            if(!$dir)
                require_once ( 'class-' . esc_attr($new_token) . '-' . esc_attr($class_name) . '.php' );
            else
                require_once ( trailingslashit( $dir ) . 'class-' . esc_attr($new_token) . '-' . strtolower($dir) . '-' . esc_attr($class_name) . '.php' );
        }
    }

}