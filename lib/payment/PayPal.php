<?php

/**
 *
 */
class PayPal {

    /**
     * The array of products for checkout
     *
     * @var array
     */
    protected $products = array();

    public static function getCurrencyCodes() {
        return array( 'AUD', 'BRL', 'CAD', 'CHF', 'CLP', 'CZK', 'DKK', 'EUR', 'GBP', 'GTQ', 'HKD', 'HUF', 'IDR', 'INR', 'ILS', 'JPY', 'KRW', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RMB', 'RUB', 'SAR', 'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'USD', 'ZAR' );
    }

    /**
     * Send the Express Checkout NVP request
     *
     * @param $form_id
     * @throws Exception
     */
    public function send_EC_Request( $form_id ) {
        if ( !session_id() ) {
            @session_start();
        }

        if ( ! count( $this->products ) ) {
            throw new Exception('Products not found!');
        }

        $total = 0;

        // create the data to send on PayPal
        $data =
            '&SOLUTIONTYPE='                   . 'Sole'.
            '&PAYMENTREQUEST_0_PAYMENTACTION=' . 'Sale'.
            '&PAYMENTREQUEST_0_CURRENCYCODE='  . urlencode( get_option( 'ab_paypal_currency' ) ) .
            '&RETURNURL='. urlencode( add_query_arg( array( 'action' => 'ab-paypal-returnurl', 'ab_fid' => $form_id), AB_CommonUtils::getCurrentPageURL() ) ) .
            '&CANCELURL='. urlencode( add_query_arg( array( 'action' => 'ab-paypal-cancelurl', 'ab_fid' => $form_id), AB_CommonUtils::getCurrentPageURL() ) );

        foreach ( $this->products as $k => $product ) {
            $data .=
                "&L_PAYMENTREQUEST_0_NAME{$k}=".urlencode($product->name).
                "&L_PAYMENTREQUEST_0_DESC{$k}=".urlencode($product->desc).
                "&L_PAYMENTREQUEST_0_AMT{$k}=".urlencode($product->price).
                "&L_PAYMENTREQUEST_0_QTY{$k}=".urlencode($product->qty);

            $total += ($product->qty * $product->price);
        }
        $data .=
            "&PAYMENTREQUEST_0_AMT=".urlencode($total).
            "&PAYMENTREQUEST_0_ITEMAMT=".urlencode($total);

        // send the request to PayPal
        $response = self::sendNvpRequest('SetExpressCheckout', $data);

        //Respond according to message we receive from Paypal
        if ( "SUCCESS" == strtoupper( $response["ACK"] ) || "SUCCESSWITHWARNING" == strtoupper( $response["ACK"] ) ) {
            $_SESSION['bookly'][$form_id]['paypal_response'] = array( $response, $form_id );

            $paypalurl ='https://www'.get_option( 'ab_paypal_ec_mode' ).'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token='.urldecode( $response["TOKEN"] );
            header('Location: '.$paypalurl);
            exit;
        } else {
            header('Location: ' . add_query_arg( array( 'action' => 'ab-paypal-errorurl', 'ab_fid' => $form_id, 'error_msg' => $response["L_LONGMESSAGE0"]), AB_CommonUtils::getCurrentPageURL() ) );
            exit;
        }
    }

    /**
     * Send the NVP Request to the PayPal
     *
     * @param $method
     * @param $nvpStr
     * @return array
     */
    public function sendNvpRequest($method, $nvpStr) {
        $username   = urlencode( get_option( 'ab_paypal_api_username' ) );
        $password   = urlencode( get_option( 'ab_paypal_api_password' ) );
        $signature  = urlencode( get_option( 'ab_paypal_api_signature' ) );

        $url = "https://api-3t".get_option( 'ab_paypal_ec_mode' ).".paypal.com/nvp";
        $version = urlencode('76.0');

        // Set the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Set the API operation, version, and API signature in the request.
        $nvpreq = "METHOD={$method}&VERSION={$version}&PWD={$password}&USER={$username}&SIGNATURE={$signature}{$nvpStr}";

        // Set the request as a POST FIELD for curl.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        // Get response from the server.
        $httpResponse = curl_exec($ch);

        if(!$httpResponse) {
            exit("$method failed: ".curl_error($ch).'('.curl_errno($ch).')');
        }

        // Extract the response details.
        $httpResponseArray = explode("&", $httpResponse);

        $httpParsedResponseArray = array();
        foreach ($httpResponseArray as $i => $value) {
            $tmpAr = explode("=", $value);
            if(sizeof($tmpAr) > 1) {
                $httpParsedResponseArray[$tmpAr[0]] = $tmpAr[1];
            }
        }

        if((0 == sizeof($httpParsedResponseArray)) || !array_key_exists('ACK', $httpParsedResponseArray)) {
            exit("Invalid HTTP Response for POST request($nvpreq) to $url.");
        }

        return $httpParsedResponseArray;
    }

    public function renderForm( $form_id ) {
        $output = '<form method="post" class="ab-paypal-form">';
        $output .= '<input type="hidden" name="action" value="ab_paypal_checkout"/>';
        $output .= "<input type='hidden' name='form_id' value='{$form_id}'/>";
        $output .= '<button class="ab-left ab-to-third-step ab-btn ladda-button orange zoom-in" style="margin-right: 10px;"><span class="ab_label">' . __( 'Back', 'ab' ) . '</span><span class="spinner"></span></button>';
        $output .= '<button class="ab-right ab-final-step ab-btn ladda-button orange zoom-in"><span class="ab_label">' . __( 'Next', 'ab' ) . '</span><span class="spinner"></span></button>';
        $output .= '</form>';

        echo $output;
    }

    /**
     * @return array
     */
    public function getProducts() {
        return $this->products;
    }

    /**
     * Add the Product for payment
     *
     * @param stdClass $product
     */
    public function addProduct( stdClass $product ) {
        $this->products[] = $product;
    }
}