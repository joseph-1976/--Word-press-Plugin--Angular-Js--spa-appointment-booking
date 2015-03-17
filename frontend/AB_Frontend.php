<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AB_Frontend {

    public function __construct() {
        add_action( 'wp_loaded', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'registerCSSAndJS' ) );
        // Init controllers.
        $this->bookingController = new AB_BookingController();
        $this->paypalController  = new AB_PayPalController();
        // Register shortcodes.
        add_shortcode( 'bookly-form', array( $this->bookingController, 'renderShortCode') );
        /** @deprecated [ap-booking] */
        add_shortcode( 'ap-booking', array( $this->bookingController, 'renderShortCode') );
    }

    public function registerCSSAndJS() {
        /** @var WP_Locale $wp_locale */
        global $wp_locale;

        wp_register_style( 'ab-reset', plugins_url( 'resources/css/ab-reset.css', __FILE__ ) );
        wp_register_style( 'ab-ladda-min', plugins_url( 'resources/css/ladda.min.css',   __FILE__ ) );
        wp_register_style( 'ab-core', plugins_url( 'resources/css/ab-core.css',   __FILE__ ) );
        wp_register_style( 'ab-picker-classic-date', plugins_url( 'resources/css/picker.classic.date.css', __FILE__ ) );
        wp_register_style( 'ab-picker-date', plugins_url( 'resources/css/picker.classic.css', __FILE__ ) );
        wp_register_style( 'ab-picker', plugins_url( 'resources/css/ab-picker.css', __FILE__ ) );
        wp_register_style( 'ab-columnizer', plugins_url( 'resources/css/ab-columnizer.css', __FILE__ ) );
        wp_register_script( 'ab-spin', plugins_url( 'resources/js/spin.min.js', __FILE__ ) );
        wp_register_script( 'ab-ladda', plugins_url( 'resources/js/ladda.min.js', __FILE__ ) );
        wp_register_script( 'bookly', plugins_url( 'resources/js/bookly.js', __FILE__ ), array( 'jquery' ) );
        wp_register_script( 'ab-picker', plugins_url( 'resources/js/picker.js', __FILE__ ) );
        wp_register_script( 'ab-picker-date', plugins_url( 'resources/js/picker.date.js', __FILE__ ) );
        wp_localize_script( 'ab-picker-date', 'BooklyL10n', array(
            'today'     => __( 'Today', 'ab' ),
            'months'    => array_values( $wp_locale->month ),
            'days'      => array_values( $wp_locale->weekday_abbrev ),
            'nextMonth' => __( 'Next month', 'ab' ),
            'prevMonth' => __( 'Previous month', 'ab' ),
        ) );
        wp_register_script( 'ab-hammer', plugins_url( 'resources/js/jquery.hammer.min.js', __FILE__ ) );
        // Android animation
        if ( array_key_exists('HTTP_USER_AGENT', $_SERVER) && stripos( strtolower( $_SERVER[ 'HTTP_USER_AGENT' ] ), 'android' ) !== false ) {
            wp_register_script( 'ab-jquery-animate-enhanced', plugins_url( 'resources/js/jquery.animate-enhanced.min.js', __FILE__ ) );
        }
    }

    public function init() {
        if ( !session_id() ) {
            @session_start();
        }

        // PayPal Express Checkout
        if ( isset( $_REQUEST['action'] ) ) {
            switch ( $_REQUEST['action'] ) {
                case 'ab_paypal_checkout':
                    $this->paypalController->paypalExpressCheckout();
                    break;
                case 'ab-paypal-returnurl':
                    $this->paypalController->paypalResponseSuccess();
                    break;
                case 'ab-paypal-cancelurl':
                    $this->paypalController->paypalResponseCancel();
                    break;
                case 'ab-paypal-errorurl':
                    $this->paypalController->paypalResponseError();
                    break;
            }
        }
    }
}