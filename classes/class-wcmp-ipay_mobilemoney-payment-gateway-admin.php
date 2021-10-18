<?php

/**
 * 
 */
class WCMP_Ipay_MobileMoney_Payment_Gateway_Admin
{
	
	public function __construct() {
        add_filter( 'automatic_payment_method', array( $this, 'admin_ipay_mobilemoney_payment_mode'), 20);
        add_filter( 'wcmp_vendor_payment_mode', array( $this, 'vendor_ipay_mobilemoney_payment_mode' ), 20);
        add_filter("settings_vendors_payment_tab_options", array( $this, 'wcmp_setting_ipay_mobilemoney_account_id' ), 90, 2 );
        add_action( 'settings_page_payment_ipay_mobilemoney_tab_init', array( &$this, 'payment_ipay_mobilemoney_init' ), 10, 2 );
        add_filter('wcmp_tabsection_payment', array( $this, 'wcmp_tabsection_payment_ipay_mobilemoney' ) );
        add_filter('wcmp_vendor_user_fields', array( $this, 'wcmp_vendor_user_fields_for_ipay_mobilemoney' ), 10, 2 );
        add_action('wcmp_after_vendor_billing', array($this, 'wcmp_after_vendor_billing_for_ipay_mobilemoney'));
    }

    public function wcmp_after_vendor_billing_for_ipay_mobilemoney() {
        global $WCMp;
        $user_array = $WCMp->user->get_vendor_fields( get_current_user_id() );
        ?>
        <div class="payment-gateway payment-gateway-ipay_mobilemoney <?php echo apply_filters('wcmp_vendor_paypal_email_container_class', ''); ?>">
            <div class="form-group">
                <label for="vendor_ipay_mobilemoney_channel" class="control-label col-sm-3 col-md-3"><?php _e('Mobile channel', 'dc-woocommerce-multi-vendor'); ?></label>
                <div class="col-md-6 col-sm-9">                         
                    <select class="form-control" id="vendor_ipay_mobilemoney_channel" name="vendor_ipay_mobilemoney_channel"> 
                        <option value="<?php echo isset($user_array['vendor_ipay_mobilemoney_channel']['value']) ? 'mpesa' : 'mpesa'; ?>">MPesa</option>
                        <option value="<?php echo isset($user_array['vendor_ipay_mobilemoney_channel']['value']) ? 'airtelmoney' : 'airtelmoney'; ?>">Airtel Money</option>
                        <option value="<?php echo isset($user_array['vendor_ipay_mobilemoney_channel']['value']) ? 'elipa' : 'elipa'; ?>">elipa</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="vendor_ipay_mobilemoney_phone" class="control-label col-sm-3 col-md-3"><?php esc_html_e('Phone number', 'wcmp-ipay_mobilemoney-payment-gateway'); ?></label>
                <div class="col-md-6 col-sm-9">
                    <input id="vendor_ipay_mobilemoney_phone" class="form-control" type="text" name="vendor_ipay_mobilemoney_phone" value="<?php echo isset($user_array['vendor_ipay_mobilemoney_phone']['value']) ? $user_array['vendor_ipay_mobilemoney_phone']['value'] : ''; ?>"  placeholder="<?php esc_attr_e('Phone number e.g 2547xxxxxxxx', 'wcmp-ipay_mobilemoney-payment-gateway'); ?>">
                </div>
            </div>
        </div>      
                 
              
        <?php
    }

    public function wcmp_vendor_user_fields_for_ipay_mobilemoney($fields, $vendor_id) {
        $vendor = get_wcmp_vendor($vendor_id);       
        //mobile money settings
        $fields["vendor_ipay_mobilemoney_channel"] = array(
            'label' => __('Vendor Pay Channel', 'wcmp-ipay_mobilemoney-payment-gateway'),
            'type' => 'text',
            'value' => $vendor->ipay_mobilemoney_channel,
            'class' => "user-profile-fields regular-text"
        );
        $fields["vendor_ipay_mobilemoney_phone"] = array(
            'label' => __('Vendor Phone number', 'wcmp-ipay_mobilemoney-payment-gateway'),
            'type' => 'text',
            'value' => $vendor->ipay_mobilemoney_phone,
            'class' => "user-profile-fields regular-text"
        ); 
        
        return $fields;
    }

    public function admin_ipay_mobilemoney_payment_mode( $arg ) {
        unset($arg['ipay_mobilemoney_block']);
        $admin_payment_mode_select = array_merge( $arg, array( 'ipay_mobilemoney' => __('iPay Mobilemoney', 'wcmp-ipay_mobilemoney-payment-gateway') ) );
        return $admin_payment_mode_select;
    }

    public function vendor_ipay_mobilemoney_payment_mode($payment_mode) {
        $payment_admin_settings = get_option('wcmp_payment_settings_name');

        if (isset($payment_admin_settings['payment_method_ipay_mobilemoney']) && $payment_admin_settings['payment_method_ipay_mobilemoney'] = 'Enable') {
            $payment_mode['ipay_mobilemoney'] = __('iPay Mobilemoney', 'wcmp-ipay_mobilemoney-payment-gateway');
        }
        return $payment_mode;
    }

    public function wcmp_setting_ipay_mobilemoney_account_id( $payment_tab_options, $vendor_obj ) {       

        //mobile money settings
        $payment_tab_options['vendor_ipay_mobilemoney_channel'] = array('label' => __('Channel (mpesa/airtelmoney/elipa)', 'wcmp-ipay_mobilemoney-payment-gateway'), 'type' => 'select', 'id' => 'vendor_ipay_mobilemoney_channel', 'label_for' => 'vendor_ipay_mobilemoney_channel', 'name' => 'vendor_ipay_mobilemoney_channel', 'options' => array('mpesa'=>"mpesa","airtelmoney"=>"airtelmoney", "elipa"=>"elipa" ), 'wrapper_class' => 'payment-gateway-ipay_mobilemoney payment-gateway');

        $payment_tab_options['vendor_ipay_mobilemoney_phone'] = array('label' => __('Phone Number', 'wcmp-ipay_mobilemoney-payment-gateway'), 'type' => 'text', 'id' => 'vendor_ipay_mobilemoney_phone', 'label_for' => 'vendor_ipay_mobilemoney_phone', 'name' => 'vendor_ipay_mobilemoney_phone', 'value' => $vendor_obj->ipay_mobilemoney_phone, 'wrapper_class' => 'payment-gateway-ipay_mobilemoney payment-gateway');        
        return $payment_tab_options;
    }

    public function payment_ipay_mobilemoney_init( $tab, $subsection ) {
        global $WCMP_Ipay_MobileMoney_Payment_Gateway;
        require_once $WCMP_Ipay_MobileMoney_Payment_Gateway->plugin_path . 'admin/class-wcmp-settings-payment-ipay_mobilemoney.php';
        new WCMp_Settings_Payment_Ipay_MobileMoney( $tab, $subsection );
    }

    public function wcmp_tabsection_payment_ipay_mobilemoney($tabsection_payment) {
        if ( 'Enable' === get_wcmp_vendor_settings( 'payment_method_ipay_mobilemoney', 'payment' ) ) {
            $tabsection_payment['ipay_mobilemoney'] = array( 'title' => __( 'iPay MobileMoney', 'wcmp-ipay_mobilemoney-payment-gateway' ), 'icon' => 'dashicons-admin-settings' );
        }
        return $tabsection_payment;
    }
}