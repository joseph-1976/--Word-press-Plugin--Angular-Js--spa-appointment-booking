<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_PayPalController
 */
class AB_PayPalController extends AB_Controller {

    protected function getPermissions() {
        return array(
          '_this' => 'anonymous',
        );
    }

    public function __construct() {
        parent::__construct();

        $this->paypal = new PayPal();
    }

    public function paypalExpressCheckout() {
        $form_id = $this->getParameter( 'form_id' );
        if ( $form_id ) {
            // create a paypal object
            $paypal = new PayPal();
            $userData = new AB_UserBookingData( $form_id );
            $userData->load();

            if ( $userData->get( 'service_id' ) ) {
                $service = $userData->getService();
                $price   = $userData->getFinalServicePrice();

                // get the products information from the $_POST and create the Product objects
                $product = new stdClass();
                $product->name  = $service->get( 'title' );
                $product->desc  = $service->getTitleWithDuration();
                $product->price = $price;
                $product->qty   = 1;
                $paypal->addProduct($product);

                // and send the payment request
                try {
                    $paypal->send_EC_Request( $form_id );

                } catch ( Exception $e ) {
                    $userData->setPayPalStatus( 'error', $this->getParameter( 'error_msg' ) );
                    @wp_safe_redirect( remove_query_arg( array( 'action', 'token', 'PayerID' ), AB_CommonUtils::getCurrentPageURL() ) );
                    exit;
                }
            }
        }
    }

    /**
     * Express Checkout 'CANCELURL' process
     */
    public function paypalResponseCancel() {
        $userData = new AB_UserBookingData( $_GET[ 'ab_fid' ] );
        $userData->load();
        $userData->setPayPalStatus( 'cancelled' );
        @wp_safe_redirect( remove_query_arg( array( 'action', 'token', 'PayerID', 'ab_fid'), AB_CommonUtils::getCurrentPageURL() ) );
        exit;
    }

    /**
     * Express Checkout 'ERRORURL' process
     */
    public function paypalResponseError() {
        $userData = new AB_UserBookingData( $_GET[ 'ab_fid' ] );
        $userData->load();
        $userData->setPayPalStatus( 'error', $this->getParameter( 'error_msg' ) );
        @wp_safe_redirect( remove_query_arg( array( 'action', 'token', 'PayerID', 'error_msg', 'ab_fid' ), AB_CommonUtils::getCurrentPageURL() ) );
        exit;
    }

    /**
     * Process the Express Checkout RETURNURL
     */
    public function paypalResponseSuccess() {
        $form_id = $_GET[ 'ab_fid' ];

        if ( isset( $_GET["token"] ) && isset( $_GET["PayerID"] ) ) {
            $token    = $_GET["token"];
            $payer_id = $_GET["PayerID"];

            // send the request to PayPal
            $response = $this->paypal->sendNvpRequest( 'GetExpressCheckoutDetails', sprintf( '&TOKEN=%s', $token ) );

            if ( strtoupper( $response["ACK"] ) == "SUCCESS" ) {
                $data = sprintf( '&TOKEN=%s&PAYERID=%s&PAYMENTREQUEST_0_PAYMENTACTION=Sale', $token, $payer_id );

                // response keys containing useful data to send via DoExpressCheckoutPayment operation
                $response_data_keys_pattern = sprintf( '/^(%s)/', implode( '|', array(
                    'PAYMENTREQUEST_0_AMT',
                    'PAYMENTREQUEST_0_ITEMAMT',
                    'PAYMENTREQUEST_0_CURRENCYCODE',
                    'L_PAYMENTREQUEST_0',
                ) ) );

                foreach ( $response as $key => $value ) {
                    // collect product data from response using defined response keys
                    if ( preg_match( $response_data_keys_pattern, $key ) ) {
                        $data .= sprintf( '&%s=%s', $key, $value );
                    }
                }

                //We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
                $response = $this->paypal->sendNvpRequest( 'DoExpressCheckoutPayment', $data );
                if ( "SUCCESS" == strtoupper( $response["ACK"] ) || "SUCCESSWITHWARNING" == strtoupper( $response["ACK"] ) ) {
                    // get transaction info
                    $response = $this->paypal->sendNvpRequest( 'GetTransactionDetails', "&TRANSACTIONID=" . urlencode( $response["PAYMENTINFO_0_TRANSACTIONID"] ) );
                    if ( "SUCCESS" == strtoupper( $response["ACK"] ) || "SUCCESSWITHWARNING" == strtoupper( $response["ACK"] ) ) {
                        // need session to get Total and Token

                        $token = $_SESSION[ 'bookly' ][ $form_id ][ 'paypal_response' ][ 0 ][ 'TOKEN' ];

                        $userData = new AB_UserBookingData( $form_id );
                        $userData->load();

                        if ( $userData->get( 'service_id' ) ) {
                            $appointment = $userData->save();

                            $customer_appointment = new AB_CustomerAppointment();
                            $customer_appointment->loadBy( array(
                                'appointment_id' => $appointment->get('id'),
                                'customer_id'    => $userData->get( 'customer_id' )
                            ) );

                            $payment = new AB_Payment();
                            $payment->set( 'token', urldecode($token) );
                            $payment->set( 'total', $userData->getFinalServicePrice() );
                            $payment->set( 'customer_appointment_id', $customer_appointment->get( 'id' ) );
                            $payment->set( 'transaction', urlencode( $response["TRANSACTIONID"] ) );
                            $payment->set( 'created', current_time( 'mysql' ) );

                            $coupon = $userData->getCoupon();
                            if ( $coupon ){
                                $payment->set( 'coupon', $coupon->get( 'code' ) );
                                $coupon->set( 'used', 1 );
                                $coupon->save();
                            }

                            $payment->save();

                            $userData->setPayPalStatus( 'success' );
                        }

                        @wp_safe_redirect( remove_query_arg( array( 'action', 'token', 'PayerID', 'ab_fid' ), AB_CommonUtils::getCurrentPageURL() ) );
                        exit ( 0 );
                    }
                    else {
                        header('Location: ' . add_query_arg( array(
                                'action' => 'ab-paypal-errorurl',
                                'ab_fid' => $form_id,
                                'error_msg' => $response["L_LONGMESSAGE0"]
                            ), AB_CommonUtils::getCurrentPageURL()
                        ) );
                        exit;
                    }
                }
                else {
                    header('Location: ' . add_query_arg( array(
                            'action' => 'ab-paypal-errorurl',
                            'ab_fid' => $form_id,
                            'error_msg' => $response["L_LONGMESSAGE0"]
                        ), AB_CommonUtils::getCurrentPageURL()
                    ) );
                    exit;
                }
            }
            else {
                header('Location: ' . add_query_arg( array(
                        'action' => 'ab-paypal-errorurl',
                        'ab_fid' => $form_id,
                        'error_msg' => 'Invalid token provided'
                    ), AB_CommonUtils::getCurrentPageURL()
                ) );
                exit;
            }
        }
        else {
            throw new Exception('Token parameter not found!');
        }
    }
}