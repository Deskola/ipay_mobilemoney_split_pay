<?php

if (!defined('ABSPATH')) {
    exit;
}



class WCMp_Gateway_Ipay_MobileMoney extends WCMp_Payment_Gateway {

    public $id;
    public $message = array();
    private $test_mode = true;
    private $payout_mode = 'true';
    private $vendor_payment_channel;
    private $reciver_phone;    
    private $reciver_pay_type;    
    private $key_id;
    private $key_secret;      
    private $api_endpoint = '';

    public function __construct() {
        $this->id = 'ipay_mobilemoney';
        $this->enabled = get_wcmp_vendor_settings('payment_method_ipay_mobilemoney', 'payment');
        $this->key_id = get_wcmp_vendor_settings('key_id', 'payment', 'ipay_mobilemoney');
        $this->key_secret = get_wcmp_vendor_settings('key_secret', 'payment', 'ipay_mobilemoney');       
    }
    
    public function gateway_logo() { global $WCMp; return $WCMp->plugin_url . 'assets/images/'.$this->id.'.png'; }

    public function process_payment($vendor, $commissions = array(), $transaction_mode = 'auto', $transfer_args = array()) {
        $this->vendor = $vendor;
        $this->commissions = $commissions;
        $this->currency = get_woocommerce_currency();
        $this->transaction_mode = $transaction_mode;        
        $this->vendor_payment_channel = wcmp_get_user_meta($this->vendor->id, '_vendor_ipay_mobilemoney_channel', true);
        $this->reciver_phone = wcmp_get_user_meta($this->vendor->id,'_vendor_ipay_mobilemoney_phone');        
        $this->test_mode = get_wcmp_vendor_settings('is_split', 'payment', 'ipay') === "Enable" ? false : true;
        $this->api_endpoint = $this->test_mode ? "https://apis.staging.ipayafrica.com/b2c/v3/mobile/{$this->vendor_payment_channel}" : "https://apis.staging.ipayafrica.com/b2c/v3/mobile/{$this->vendor_payment_channel}";       
        
        if ($this->validate_request()) {            
            $ipay_response = $this->process_ipay_mobilemoney_payout();
            doProductVendorLOG(json_encode($ipay_response));
            if ($ipay_response && $ipay_response["rawdata"]["data"]["status"] == 200) {
                $this->record_transaction();
                if ($this->transaction_id) {
                    return array('message' => __('New transaction has been initiated', 'dc-woocommerce-multi-vendor'), 'type' => 'success', 'transaction_id' => $this->transaction_id, 'ipay_response' => $ipay_response["rawdata"]["data"]["text"]);
                }
            } elseif($ipay_response && $ipay_response["rawdata"]["data"]["status"] == 400) {                
                $this->add_commission_note($this->commissions, __("Payment failed. Reason: {$ipay_response['rawdata']['data']['errormessage']}", 'dc-woocommerce-multi-vendor'));
                return false;
            } else {
                $this->add_commission_note($this->commissions, __("Payment failed. Reason: {$ipay_response['rawdata']['data']['error']}", 'dc-woocommerce-multi-vendor'));
                return false;
            }
        } else {
            return $this->message;
        }        
    }

    public function validate_request() {
         global $WCMp;
        if ($this->enabled != 'Enable') {
            $this->message[] = array('message' => __('Invalid payment method', 'dc-woocommerce-multi-vendor'), 'type' => 'error');
            return false;
        } else if (!$this->key_id && !$this->key_secret) {
            $this->message[] = array('message' => __('Ipay payout setting is not configured properly please contact site administrator', 'dc-woocommerce-multi-vendor'), 'type' => 'error');
            return false;
        } else if (!$this->vendor_payment_channel) {
            $this->message[] = array('message' => __('Please update your Ipay Channel to receive commission', 'dc-woocommerce-multi-vendor'), 'type' => 'error');
            return false;
        } else if (!$this->reciver_phone) {
            $this->message[] = array('message' => __('Please update your Ipay Phone to receive commission', 'dc-woocommerce-multi-vendor'), 'type' => 'error');
            return false;
        }

        if ($this->transaction_mode != 'admin') {
            /* handel thesold time */
            $threshold_time = isset($WCMp->vendor_caps->payment_cap['commission_threshold_time']) && !empty($WCMp->vendor_caps->payment_cap['commission_threshold_time']) ? $WCMp->vendor_caps->payment_cap['commission_threshold_time'] : 0;
            if ($threshold_time > 0) {
                foreach ($this->commissions as $index => $commission) {
                    if (intval((date('U') - get_the_date('U', $commission)) / (3600 * 24)) < $threshold_time) {
                        unset($this->commissions[$index]);
                    }
                }
            }
            /* handel thesold amount */
            $thesold_amount = isset($WCMp->vendor_caps->payment_cap['commission_threshold']) && !empty($WCMp->vendor_caps->payment_cap['commission_threshold']) ? $WCMp->vendor_caps->payment_cap['commission_threshold'] : 0;
            if ($this->get_transaction_total() > $thesold_amount) {
                return true;
            } else {
                $this->message[] = array('message' => __('Minimum thesold amount to withdrawal commission is ' . $thesold_amount, 'dc-woocommerce-multi-vendor'), 'type' => 'error');
                return false;
            }
        }
        return parent::validate_request();
    }

    public function process_ipay_mobilemoney_payout() {
        $response = array();
        $response_success = array();
        $response_failed = array();
        $raw_data = array();
        if ($this->api_endpoint && is_array($this->commissions)) {
            foreach ($this->commissions as $commission_id) {
                $commissionResponse = array();
                //check the order is payed with razor pay or not!!
                $vendor_order_id = wcmp_get_commission_order_id($commission_id);
                //get order details
                if ($vendor_order_id) {
                    $vendor_order = wc_get_order($vendor_order_id);
                    //check for valid vendor_order
                    if ($vendor_order) {
                        //get order payment mode
                        $paymentMode = $vendor_order->get_payment_method();
                        $orderStatus = $vendor_order->get_status();
                         //get commission amount to be transferred and commission note
                        $commission_amount = WCMp_Commission::commission_totals($commission_id, 'edit');
                        $transaction_total = (float) $commission_amount;
                        $amount_to_pay = round($this->get_transaction_total() - $this->transfer_charge($this->transaction_mode) - $this->gateway_charge(), 2);
                        $acceptedOrderStatus = apply_filters('wcmp_ipay_payment_order_status', array('processing', 'on-hold', 'completed'));
                         //check payment mode
                        if ($paymentMode != 'ipay') {
                            //payment method is not valid
                            $commissionResponse['message'] = "Order is not processed With Ipay Mobile Money!"
                                . " Unable to Process #$vendor_order_id Order Commission!!";
                            $commissionResponse['type'] = 'error';
                        }  elseif ( $amount_to_pay < 1 ) {
                            $commissionResponse['message'] = "Commission Amount is less than 1 !!"
                                . " Unable to Process #$commission_id Commission!!";
                            $commissionResponse['type'] = 'error';
                        } else {
                            $reference = "{$this->vendor->id}0{$vendor_order_id}0{$commission_id}";

                            $datastring = "amount=" . $amount_to_pay . "&phone=" . $this->reciver_phone . "&reference=" . $reference . "&vid=" . $this->key_id;

                            $generatedHash = hash_hmac('sha256', $datastring, $this->key_secret); 

                            $data = array(
                                "amount" => $amount_to_pay,
                                "phone" => $this->reciver_phone,
                                "reference" => $reference,
                                "vid" => $this->key_id,
                                "hash" => $generatedHash,                               
                            );   
                           
                            $jsonifiedData = json_encode($data);

                            try {

                                $transfer = $this->make_curl_api_request($this->api_endpoint, $jsonifiedData);

                                if ($transfer) {                                    
                                    $response_success['success'] = 'success';
                                    $response_success['commission_id'][] = $commission_id;
                                    $raw_data["data"] = json_decode($transfer, true);
                                    $raw_data['time'] = date("F j, Y, g:i a");    
                                }
                                
                            } catch (Exception $e) {
                                //log gateway error
                                doProductVendorLOG('Ipay Comission Payment Error!!'
                                    ."\n".$e->getCode().": ".$e->getMessage());
                                //set error message for the vendor_order id
                                $commissionResponse['message'] = "Something Went Wrong!"
                                    ." Unable to Process #$commission_id Commission!!";
                                $commissionResponse['type'] = "error";
                            }                             
                        }

                    }else {
                        //set error message for the vendor_order id
                        $commissionResponse['message'] = "Unable to get #$vendor_order_id Order Details!!";
                        $commissionResponse['type'] = "error";
                    }
                }else {
                    //set error message for the commission id
                    $commissionResponse['message'] = "Unable to get #$commission_id Commission Respective Order Id!!";
                    $commissionResponse['type'] = "error";
                }
                 //set response
                $response['error'][] = $commissionResponse;
                $response['success'] = $response_success;
                $response['failed'] = $response_failed;
                $response['rawdata'] = $raw_data;
            }
        }        

        return $response;       
    }

    private function make_curl_api_request($url, $payload){      

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Set HTTP Header for POST request
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
            )
        );        
       
        $result = curl_exec ($ch);

        curl_close($ch);
        return $result;
    }
        
}
