<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AB_PaymentsForm extends AB_Form {

    public function __construct() {
        $this->setFields(array(
            'ab_paypal_currency',
            'ab_settings_pay_locally',
            'ab_paypal_type',
            'ab_paypal_api_username',
            'ab_paypal_api_password',
            'ab_paypal_api_signature',
            'ab_paypal_ec_mode',
            'ab_paypal_id',
            'ab_authorizenet_api_login_id',
            'ab_authorizenet_transaction_key',
            'ab_authorizenet_sandbox',
            'ab_authorizenet_type',
            'ab_stripe',
            'ab_stripe_secret_key',
            'ab_settings_coupons',
        ));
    }

    public function save() {
        foreach ( $this->data as $field => $value ) {
            update_option( $field, $value );
        }
    }
}