<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AB_UserBookingData {

    private $form_id = null;

    private $data = array(
        // Step 0
        'time_zone_offset'     => null,
        // Step 1
        'service_id'           => null,
        'staff_ids'            => array(),
        'date_from'            => null,
        'days'                 => array(),
        'time_from'            => null,
        'time_to'              => null,
        // Step 2
        'appointment_datetime' => null,
        // Step 3
        'name'                 => null,
        'email'                => null,
        'phone'                => null,
        'custom_fields'        => null,
        // Step 4
        'coupon'               => null,
        // Other
        'customer_id'          => null,
        //'capacity' => null,
    );
    /**
     * Constructor.
     *
     * @param $form_id
     */
    public function __construct( $form_id ) {
        global $wpdb;

        $this->form_id = $form_id;

        // Set up default parameters.
        $prior_time = AB_BookingConfiguration::getMinimumTimePriorBooking();
        $this->set( 'date_from', date( 'Y-m-d', current_time( 'timestamp' ) + $prior_time ) );
        $this->set( 'time_from', $wpdb->get_var(
            'SELECT SUBSTRING_INDEX(MIN(`start_time`), ":", 2) AS `min_end_time`
                FROM `ab_staff_schedule_item`
             WHERE `start_time` IS NOT NULL'
        ) );
        $this->set( 'time_to', $wpdb->get_var(
            'SELECT SUBSTRING_INDEX(MAX(`end_time`), ":", 2) AS `max_end_time`
                FROM `ab_staff_schedule_item`
             WHERE `end_time` IS NOT NULL'
        ) );
    }
    /**
     * Set data parameter.
     *
     * @param string $name
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function set( $name, $value ) {
        if ( !array_key_exists( $name, $this->data ) ) {
            throw new InvalidArgumentException( sprintf( 'Trying to set unknown parameter "%s"', $name ) );
        }

        $this->data[ $name ] = $value;
    }

    /**
     * Get data parameter.
     *
     * @param string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get( $name ) {
        if ( !array_key_exists( $name, $this->data ) ) {
            throw new InvalidArgumentException( sprintf( 'Trying to get unknown parameter "%s"', $name ) );
        }

        return $this->data[ $name ];
    }



    /**
     * Load data from session.
     */
    public function load() {
        if ( isset( $_SESSION[ 'bookly' ][ $this->form_id ] ) ) {
            $this->data = $_SESSION[ 'bookly' ][ $this->form_id ][ 'data' ];
        }
    }

    /**
     * Partially update data in session.
     *
     * @param array $data
     */
    public function saveData( array $data ) {
        foreach ( $data as $key => $value ) {
            if ( array_key_exists( $key, $this->data ) ) {
                $this->set( $key, $value );
            }
        }
        $_SESSION['bookly'][ $this->form_id ][ 'data' ] = $this->data;
    }

    /**
     * Validate fields.
     *
     * @param $data
     * @return array
     */
    public function validate( $data ) {
        $validator = new AB_Validator();
        foreach ( $data as $field_name => $field_value ) {
            switch ( $field_name ) {
                case 'email':
                    $validator->validateEmail( $field_name, $field_value, true );
                    break;
                case 'phone':
                    $validator->validatePhone( $field_name, $field_value, true );
                    break;
                case 'date_from':
                case 'time_from':
                case 'time_to':
                case 'appointment_datetime':
                    $validator->validateDateTime( $field_name, $field_value, true );
                    break;
                case 'name':
                    $validator->validateString( $field_name, $field_value, 255, true, true, 3 );
                    break;
                case 'service_id':
                    $validator->validateNumber( $field_name, $field_value );
                    break;
                case 'custom_fields':
                    $validator->validateCustomFields( $field_value );
                    break;
                default:
            }
        }

        if ( isset( $data['time_from'] ) && isset( $data['time_to'] ) ) {
            $validator->validateTimeGt( 'time_from', $data['time_from'], $data['time_to'] );
        }

        return $validator->getErrors();
    }

    /**
     * @return AB_Appointment
     */
    public function save() {
        add_filter('wp_mail_from', create_function( '$content_type',
            'return get_option( \'ab_settings_sender_email\' ) == \'\' ?
                get_option( \'admin_email\' ) : get_option( \'ab_settings_sender_email\' );'
        ) );
        add_filter('wp_mail_from_name', create_function( '$name',
            'return get_option( \'ab_settings_sender_name\' ) == \'\' ?
                get_option( \'blogname\' ) : get_option( \'ab_settings_sender_name\' );'
        ) );

        // If customer with such name & e-mail exists, append new booking to him, otherwise - create new customer
        $customer = new AB_Customer();
        $customer->loadBy( array(
            'name'  => $this->get( 'name' ),
            'email' => $this->get( 'email' )
        ) );
        $customer->set( 'name',  $this->get( 'name' ) );
        $customer->set( 'email', $this->get( 'email' ) );
        $customer->set( 'phone', $this->get( 'phone' ) );
        $customer->save();

        $this->set( 'customer_id', $customer->get( 'id' ) );

        $service = $this->getService();

        /**
         * Get appointment, with same params.
         * If it is -> create connection to this appointment,
         * otherwise create appointment and connect customer to new appointment
         */
        $appointment = new AB_Appointment();
        $appointment->loadBy( array(
            'staff_id'   => $this->getStaffId(),
            'service_id' => $this->get( 'service_id' ),
            'start_date' => $this->get( 'appointment_datetime' )
        ) );
        if ( $appointment->isLoaded() == false ) {
            $appointment->set( 'staff_id', $this->getStaffId() );
            $appointment->set( 'service_id', $this->get( 'service_id' ) );
            $appointment->set( 'start_date', $this->get( 'appointment_datetime' ) );

            $endDate = new DateTime( $this->get( 'appointment_datetime' ) );
            $di = "+ {$service->get( 'duration' )} sec";
            $endDate->modify( $di );

            $appointment->set( 'end_date', $endDate->format( 'Y-m-d H:i:s' ) );
            $appointment->save();
        }

//        for ( $i = 1; $i <= $this->getCapacity(); $i++ ) {
            $customer_appointment = new AB_CustomerAppointment();
            $customer_appointment->set( 'appointment_id', $appointment->get( 'id' ) );
            $customer_appointment->set( 'customer_id', $customer->get( 'id' ) );
            $customer_appointment->set( 'custom_fields', $this->get( 'custom_fields' ) );
            $customer_appointment->save();
//        }

        // 100% discount coupon.
        $coupon = $this->getCoupon();
        if ( $coupon && $coupon->get( 'discount' ) == '100' ) {
            $payment = new AB_Payment();
            $payment->set( 'coupon', $this->get( 'coupon' ) );
            $payment->set( 'total', '0.00' );
            $payment->set( 'type', 'coupon' );
            $payment->set( 'created', current_time( 'mysql' ) );
            $payment->set( 'customer_appointment_id', $customer_appointment->get( 'id' ) );
            $payment->save();

            $coupon->set('used', 1);
            $coupon->save();
        }

        // Google Calendar.
        $appointment->handleGoogleCalendar();

        $appointment->sendEmailNotifications( $this->get( 'time_zone_offset' ), $coupon );

        return $appointment;
    }

    /**
     * Get coupon.
     *
     * @return AB_Coupon|bool
     */
    public function getCoupon() {
        $coupon = new AB_Coupon();
        $coupon->loadBy( array(
            'code' => $this->get( 'coupon' ),
            'used' => 0,
        ) );

        return $coupon->isLoaded() ? $coupon : false;
    }

    /**
     * Get service.
     *
     * @return AB_Service
     */
    public function getService() {
        $service = new AB_Service();
        $service->load( $this->get( 'service_id' ) );

        return $service;
    }

    /**
     * Get service price.
     *
     * @return string|false
     */
    public function getServicePrice() {
        $staff_service = new AB_StaffService();
        $staff_service->loadBy( array(
            'staff_id'   => $this->getStaffId(),
            'service_id' => $this->get( 'service_id' )
        ) );

        return $staff_service->isLoaded() ? $staff_service->get( 'price' ) : false;
    }

    /**
     * Get service price (with applied coupon).
     *
     * @return float
     */
    public function getFinalServicePrice() {
        $price  = $this->getServicePrice();
        // Apply coupon.
        $coupon = $this->getCoupon();
        if ( $coupon ) {
            $price = $coupon->apply( $price );
        }

        return $price;
    }

    /**
     * Get category name.
     *
     * @return string
     */
    public function getCategoryName() {
        $service = $this->getService();
        if ( $service->get( 'category_id' ) ) {
            $category = new AB_Category();
            $category->load( $service->get( 'category_id' ) );

            return $category->get( 'name' );
        }

        return __( 'Uncategorized', 'ab' );
    }

    /**
     * Get staff id.
     *
     * @return int
     */
    public function getStaffId() {
        $ids = $this->get( 'staff_ids' );
        if ( count( $ids ) == 1 ) {
            return $ids[ 0 ];
        }

        return 0;
    }

    /**
     * Get staff name.
     *
     * @return string
     */
    public function getStaffName() {
        $staff_id = $this->getStaffId();

        if ( $staff_id ) {
            $staff = new AB_Staff();
            $staff->load( $staff_id );

            return $staff->get( 'full_name' );
        }

        return __( 'Any', 'ab' );
    }

    /**
     * Set PayPal transaction status.
     *
     * @param string $status
     * @param string $error
     */
    public function setPayPalStatus( $status, $error = null ) {
        $_SESSION[ 'bookly' ][ $this->form_id ][ 'paypal' ] = array(
            'status' => $status,
            'error'  => $error
        );
    }

    /*
     * Get and clear PayPal transaction status.
     *
     * @return array|false
     */
    public function extractPayPalStatus() {
        if ( isset ( $_SESSION[ 'bookly' ][ $this->form_id ][ 'paypal' ] ) ) {
            $status = $_SESSION[ 'bookly' ][ $this->form_id ][ 'paypal' ];
            unset ( $_SESSION[ 'bookly' ][ $this->form_id ][ 'paypal' ] );

            return $status;
        }

        return false;
    }
}