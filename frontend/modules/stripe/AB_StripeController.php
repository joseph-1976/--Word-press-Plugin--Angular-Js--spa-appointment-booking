<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_StripeController
 */
class AB_StripeController extends AB_Controller {

    protected function getPermissions() {
        return array(
          '_this' => 'anonymous',
        );
    }

    public function executeStripe() {
        $form_id = $this->getParameter( 'form_id' );
        if ( $form_id ) {
            $userData = new AB_UserBookingData( $form_id );
            $userData->load();

            if ( $userData->get( 'service_id' ) ) {
                Stripe::setApiKey(get_option( 'ab_stripe_secret_key' ));
                Stripe::setApiVersion("2014-10-07");

                $price = $userData->getFinalServicePrice();

                $stripe_data = array(
                    'number'    => $this->getParameter( 'ab_card_number' ),
                    'exp_month' => $this->getParameter( 'ab_card_month' ),
                    'exp_year'  => $this->getParameter( 'ab_card_year' ),
                    'cvc'       => $this->getParameter( 'ab_card_code' ),
                );

                try {
                    $charge = Stripe_Charge::create(array(
                        'card' => $stripe_data,
                        'amount' => intval($price * 100), // amount in cents
                        'currency' => get_option( 'ab_paypal_currency' ),
                        'description' => "Charge for " . $userData->get( 'email' ),
                    ));
                }
                catch ( Exception $e ) {
                    echo json_encode(array('error' => $e->getMessage()));
                    exit();
                }

                if ( $charge->paid ) {
                    $appointment = $userData->save();

                    $customer_appointment = new AB_CustomerAppointment();
                    $customer_appointment->loadBy( array(
                        'appointment_id' => $appointment->get('id'),
                        'customer_id'    => $userData->get( 'customer_id' )
                    ) );

                    $payment = new AB_Payment();
                    $payment->set( 'total', $price);
                    $payment->set( 'type', 'stripe' );
                    $payment->set( 'customer_appointment_id', $customer_appointment->get( 'id' ) );
                    $payment->set( 'created', current_time( 'mysql' ) );

                    $coupon = $userData->getCoupon();
                    if ( $coupon ) {
                        $payment->set( 'coupon', $coupon->get( 'code' ) );
                        $coupon->set( 'used', 1 );
                        $coupon->save();
                    }

                    $payment->save();

                    echo json_encode ( array ( 'state' => 'true' ) );
                }
                else {
                    echo json_encode ( array ( 'error' => 'unknown error' ) );
                }
            }
        }
        exit();
    }

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     */
    protected function registerWpActions( $prefix = '' ) {
        parent::registerWpActions( 'wp_ajax_ab_' );
        parent::registerWpActions( 'wp_ajax_nopriv_ab_' );
    }
}
