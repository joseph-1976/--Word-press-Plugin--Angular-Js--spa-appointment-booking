<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_BookingController
 */
class AB_BookingController extends AB_Controller {

    protected function getPermissions() {
        return array(
          '_this' => 'anonymous',
        );
    }

    /**
     * Render Bookly shortcode.
     *
     * @param $attributes
     * @return string
     */
    public function renderShortCode( $attributes ) {
        static $assets_printed = false;

        if ( !$assets_printed ) {
            $assets_printed = true;
            // The styles and scripts are registered in AB_Frontend.php
            wp_print_styles('ab-reset');
            wp_print_styles('ab-picker-date');
            wp_print_styles('ab-picker-classic-date');
            wp_print_styles('ab-picker');
            wp_print_styles('ab-ladda-themeless');
            wp_print_styles('ab-ladda-min');
            wp_print_styles('ab-core');
            wp_print_styles('ab-columnizer');

            wp_print_scripts('ab-spin');
            wp_print_scripts('ab-ladda');
            wp_print_scripts('bookly');
            wp_print_scripts('ab-picker');
            wp_print_scripts('ab-picker-date');
            wp_print_scripts('ab-hammer');
            // Android animation
            if (stripos(strtolower($_SERVER['HTTP_USER_AGENT']), 'android') !== false) {
                wp_print_scripts('ab-jquery-animate-enhanced');
            }
        }

        // Find bookings with any of paypal statuses
        $this->booking_finished = $this->booking_cancelled = false;
        $this->form_id = uniqid();
        if ( isset ( $_SESSION[ 'bookly' ] ) ) {
            foreach ( $_SESSION[ 'bookly' ] as $form_id => $data ) {
                if ( isset( $data[ 'paypal' ] ) ) {
                    switch ( $data[ 'paypal' ][ 'status' ] ) {
                        case 'success':
                            $this->form_id = $form_id;
                            $this->booking_finished = true;
                            break;
                        case 'cancelled':
                        case 'error':
                            $this->form_id = $form_id;
                            $this->booking_cancelled = true;
                            break;
                    }
                    $_SESSION[ 'bookly' ][ $form_id ][ 'paypal' ][ 'status' ] = null;
                }
                else {
                    unset ( $_SESSION[ 'bookly' ][ $form_id ] );
                }
            }
        }

        $this->attributes = json_encode( array(
            'hide_categories'    => @$attributes[ 'hide_categories' ]    ?: ( @$attributes[ 'ch' ]  ?: false ),
            'category_id'        => @$attributes[ 'category_id' ]        ?: ( @$attributes[ 'cid' ] ?: false ),
            'hide_services'      => @$attributes[ 'hide_services' ]      ?: ( @$attributes[ 'hs' ]  ?: false ),
            'service_id'         => @$attributes[ 'service_id' ]         ?: ( @$attributes[ 'sid' ] ?: false ),
            'hide_staff_members' => @$attributes[ 'hide_staff_members' ] ?: ( @$attributes[ 'he' ]  ?: false ),
            'staff_member_id'    => @$attributes[ 'staff_member_id' ]    ?: ( @$attributes[ 'eid' ] ?: false ),
            'hide_date_and_time' => @$attributes[ 'hide_date_and_time' ] ?: ( @$attributes[ 'ha' ]  ?: false ),
        ) );

        return $this->render( 'short_code', array(), false );
    }

    /**
     * Render first step.
     *
     * @return string JSON
     */
    public function executeRenderService() {
        $form_id = $this->getParameter( 'form_id' );

        $response = null;

        if ( $form_id ) {
            $configuration = new AB_BookingConfiguration();
            $userData = new AB_UserBookingData( $form_id );
            $userData->load();

            if ( get_option( 'ab_settings_use_client_time_zone' ) ) {
                $configuration->setClientTimeZoneOffset( $this->getParameter( 'time_zone_offset' ) / 60 );
                $userData->saveData( array( 'time_zone_offset' => $this->getParameter( 'time_zone_offset' ) ) );
            }

            $this->work_day_time_data = $configuration->fetchAvailableWorkDaysAndTime();

            $this->_prepareProgressTracker( 1, $userData->getServicePrice() );
            $this->info_text = nl2br( esc_html( get_option( 'ab_appearance_text_info_first_step' ) ) );
            $response = array(
                'status'     => 'success',
                'html'       => $this->render( '1_service', array( 'userData' => $userData ), false ),
                'categories' => $configuration->getCategories(),
                'staff'      => $configuration->getStaff(),
                'services'   => $configuration->getServices(),
                'attributes' => $userData->get( 'service_id' )
                    ? array(
                        'service_id'      => $userData->get( 'service_id' ),
                        'staff_member_id' => $userData->getStaffId()
                    )
                    : null
            );
        }

        // Output JSON response.
        if ( $response === null ) {
            $response = array( 'status' => 'no-data' );
        }
        header( 'Content-Type: application/json' );
        echo json_encode( $response );

        exit (0);
    }

    /**
     * Render second step.
     *
     * @return string JSON
     */
    public function executeRenderTime() {
        $form_id = $this->getParameter( 'form_id' );

        $response = null;

        if ( $form_id ) {
            $userData = new AB_UserBookingData( $form_id );
            $userData->load();

            $availableTime = new AB_AvailableTime( $userData );
            $availableTime->load();

            $this->time = $availableTime->getTime();
            $this->_prepareProgressTracker( 2, $userData->getServicePrice() );
            $this->info_text = $this->_prepareInfoText( 2, $userData );

            // Set response.
            $response = array(
                'status'         => empty ( $this->time ) ? 'error' : 'success',
                'html'           => $this->render( '2_time', array(), false ),
                'has_more_slots' => $availableTime->hasMoreSlots()
            );
        }

        // Output JSON response.
        if ( $response === null ) {
            $response = array( 'status' => 'no-data' );
        }
        header( 'Content-Type: application/json' );
        echo json_encode( $response );

        exit (0);
    }

    /**
     * Render third step.
     *
     * @return string JSON
     */
    public function executeRenderDetails() {
        $form_id = $this->getParameter( 'form_id' );

        $response = null;

        if ( $form_id ) {
            $userData = new AB_UserBookingData( $form_id );
            $userData->load();

            $this->info_text = $this->_prepareInfoText( 3, $userData );
            $this->_prepareProgressTracker( 3, $userData->getServicePrice() );
            $response = array(
                'status' => 'success',
                'html'   => $this->render( '3_details', array(
                    'userData'      => $userData,
                    'custom_fields' => json_decode( get_option( 'ab_custom_fields' ) )
                ), false )
            );
        }

        // Output JSON response.
        if ( $response === null ) {
            $response = array( 'status' => 'no-data' );
        }
        header( 'Content-Type: application/json' );
        echo json_encode( $response );

        exit (0);
    }

    /**
     * Render fourth step.
     *
     * @return string JSON
     */
    public function executeRenderPayment() {
        $form_id = $this->getParameter( 'form_id' );

        $response = null;

        if ( $form_id ) {
            $payment_disabled = AB_BookingConfiguration::isPaymentDisabled();

            $userData = new AB_UserBookingData( $form_id );
            $userData->load();
            if ($userData->getServicePrice() <= 0) {
                $payment_disabled = true;
            }

            if ( $payment_disabled == false ) {
                $this->form_id = $form_id;
                $this->info_text = nl2br( esc_html( get_option( 'ab_appearance_text_info_fourth_step' ) ) );
                $this->info_text_coupon = $this->_prepareInfoText(4, $userData);

                $service = $userData->getService();
                $price   = $userData->getFinalServicePrice();

                // create a paypal object
                $paypal = new PayPal();
                $product = new stdClass();
                $product->name  = $service->get( 'title' );
                $product->desc  = $service->getTitleWithDuration();
                $product->price = $price;
                $product->qty   = 1;
                $paypal->addProduct($product);

                // get the products information from the $_POST and create the Product objects
                $this->paypal = $paypal;
                $this->_prepareProgressTracker( 4, $price );

                // Set response.
                $response = array(
                    'status' => 'success',
                    'html'   => $this->render( '4_payment', array(
                        'userData'      => $userData,
                        'paypal_status' => $userData->extractPayPalStatus()
                    ), false )
                );
            }
        }

        // Output JSON response.
        if ( $response === null ) {
            $response = array( 'status' => 'no-data' );
        }
        header( 'Content-Type: application/json' );
        echo json_encode( $response );

        exit (0);
    }

    /**
     * Render fifth step.
     *
     * @return string JSON
     */
    public function executeRenderComplete() {
        $state = array (
            'success' => nl2br( esc_html( get_option( 'ab_appearance_text_info_fifth_step' ) ) ),
            'error' =>  __( '<h3>The selected time is not available anymore. Please, choose another time slot.</h3>', 'ab' )
        );

        if ($form_id  = $this->getParameter( 'form_id' ) ) {
            $userData = new AB_UserBookingData($form_id);
            $userData->load();

            // Show Progress Tracker if enabled in settings
            if ( get_option( 'ab_appearance_show_progress_tracker' ) == 1 ) {
                $price = $userData->getServicePrice();

                $this->_prepareProgressTracker( 5, $price );
                echo json_encode ( array (
                    'state' => $state,
                    'step'  => $this->progress_tracker
                ) );
            }
            else {
                echo json_encode ( array ( 'state' => $state ) );
            }
        }

        exit ( 0 );
    }

    /**
     * Save booking data in session.
     */
    public function executeSessionSave() {
        $form_id = $this->getParameter( 'form_id' );
        $errors  = array();
        if ( $form_id ) {
            $userData = new AB_UserBookingData( $form_id );
            $userData->load();
            $errors = $userData->validate( $this->getParameters() );
            if ( empty ( $errors ) ) {
                $userData->saveData( $this->getParameters() );
            }
        }

        header( 'Content-Type: application/json' );
        echo json_encode( $errors );
        exit;
    }

    /**
     * Save appointment (final action).
     */
    public function executeSaveAppointment() {
        $form_id = $this->getParameter( 'form_id' );
        $time_is_available = false;

        if ( $form_id ) {
            $userData = new AB_UserBookingData( $form_id );
            $userData->load();

            if ( AB_BookingConfiguration::isPaymentDisabled() ||
                get_option( 'ab_settings_pay_locally' ) ||
                $userData->getFinalServicePrice() == 0
            ) {
                // check if appointment's time is still available
                if ( !$this->findIntersections($userData->getStaffId(), $userData->get( 'service_id' ), $userData->get( 'appointment_datetime' )) ) {
                    // save appointment to DB
                    $userData->save();
                    $time_is_available = true;
                }
            }
        }

        exit ( json_encode( array( 'state' => $time_is_available ) ) );
    }

    /**
     * Verify if user booking datetime is still available
     *
     * @param $staff_id int
     * @param $service_id int
     * @param $booked_datetime string
     *
     * @return mixed
     */
    public function findIntersections($staff_id, $service_id, $booked_datetime){
        $wpdb = $this->getWpdb();

        $requested_service = new AB_Service();
        $requested_service->load($service_id);

        $endDate = new DateTime($booked_datetime);
        $di = "+ {$requested_service->get( 'duration' )} sec";
        $endDate->modify( $di );

        $query = $wpdb->prepare(
            "SELECT `a`.*, `ss`.`capacity`, COUNT(*) AS `number_of_bookings`
                FROM `ab_customer_appointment` `ca`
                LEFT JOIN `ab_appointment` `a` ON `a`.`id` = `ca`.`appointment_id`
                LEFT JOIN `ab_staff_service` `ss` ON `ss`.`staff_id` = `a`.`staff_id` AND `ss`.`service_id` = `a`.`service_id`
                WHERE `a`.`staff_id` = %d
                GROUP BY `a`.`start_date` , `a`.`staff_id` , `a`.`service_id`
                HAVING
                      (`a`.`start_date` = %s AND `service_id` =  %d and `number_of_bookings` >= `capacity`) OR
                      (`a`.`start_date` = %s AND `service_id` <> %d) OR
                      (`a`.`start_date` > %s AND `a`.`end_date` <= %s) OR
                      (`a`.`start_date` < %s  AND `a`.`end_date` > %s) OR
                      (`a`.`start_date` < %s  AND `a`.`end_date` > %s)
                ",
            $staff_id,
            $booked_datetime, $service_id,
            $booked_datetime, $service_id,
            $booked_datetime, $endDate->format('Y-m-d H:i:s'),
            $endDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'),
            $booked_datetime, $booked_datetime
        );

        return $wpdb->get_row($query);
    }

    /**
     * render Progress Tracker for Backend Appearance
     */
    public function executeRenderProgressTracker( ) {
        $booking_step = $this->getParameter( 'booking_step' );

        if ( $booking_step ) {
            $this->_prepareProgressTracker( $booking_step );

            echo json_encode( array(
                'html' => $this->progress_tracker
            ) );
        }
        exit;
    }

    public function executeRenderNextTime() {
        $form_id = $this->getParameter( 'form_id' );

        $response = null;

        if ( $form_id ) {
            $userData = new AB_UserBookingData( $form_id );
            $userData->load();

            $availableTime = new AB_AvailableTime( $userData );
            $availableTime->setStartDate( $this->getParameter( 'start_date' ) );

            $availableTime->load();

            if ( count( $availableTime->getTime() ) ) { // check, if there are available time
                $html = '';
                foreach ( $availableTime->getTime() as $date => $hours ) {
                    foreach ($hours as $slot) {
                        if ( $slot[ 'is_day' ] ) {
                            $button = sprintf(
                                '<button class="ab-available-day" value="%s">%s</button>',
                                $slot[ 'value' ],
                                $slot[ 'label' ]
                            );
                        }
                        else {
                            $button = sprintf(
                                '<button data-date="%s" data-staff_id="%s" class="ab-available-hour ladda-button %s" value="%s" data-style="zoom-in" data-spinner-color="#333"><span class="ladda-label"><i class="ab-hour-icon"><span></span></i>%s</span></button>',
                                $slot[ 'date' ],
                                $slot[ 'staff_id' ],
                                $slot['booked'] ? 'booked' : '',
                                $slot[ 'value' ],
                                $slot[ 'label' ]
                            );
                        }
                        $html .= $button;
                    }
                }
                // Set response.
                $response = array(
                    'status'         => 'success',
                    'html'           => $html,
                    'has_more_slots' => $availableTime->hasMoreSlots() // show/hide the next button
                );
            }
            else {
                // Set response.
                $response = array(
                    'status' => 'error',
                    'html'   => sprintf(
                        '<h3>%s</h3>',
                        __( 'The selected time is not available anymore. Please, choose another time slot.', 'ab' )
                    )
                );
            }
        }

        // Output JSON response.
        if ( $response === null ) {
            $response = array( 'status' => 'no-data' );
        }
        header( 'Content-Type: application/json' );
        echo json_encode( $response );

        exit (0);
    }

    /**
     * Cancel Appointment using token.
     */
    public function executeCancelAppointment() {
        $customer_appointment = new AB_CustomerAppointment();

        if ( $customer_appointment->loadBy( array( 'token' => $this->getParameter( 'token' ) ) ) ) {
            $customer_appointment->delete();

            // Delete appointment, if there aren't customers
            $current_capacity = $this->getWpdb()->get_var($this->getWpdb()->prepare('SELECT count(*) from `ab_customer_appointment` WHERE appointment_id = %d', $customer_appointment->get('appointment_id')));
            if (!$current_capacity){
                $appointment = new AB_Appointment();
                $appointment->load($customer_appointment->get('appointment_id'));
                $appointment->delete();
            }

            if (get_option( 'ab_settings_cancel_page_url' )){
                exit(wp_redirect(get_option( 'ab_settings_cancel_page_url' )));
            }
        }

        exit(wp_redirect(home_url()));
    }

    /**
     * Apply coupon
     */
    public function executeApplyCoupon(){
        $form_id = $this->getParameter( 'form_id' );
        $coupon_code = $this->getParameter( 'coupon' );

        $response = null;

        if (get_option('ab_settings_coupons') and $form_id) {
            $userData = new AB_UserBookingData($form_id);
            $userData->load();

            $price = $userData->getServicePrice();

            if ($coupon_code === ''){
                $userData->saveData( array( 'coupon' => null ) );
                $response = array(
                    'status' => 'reset',
                    'text'   => $this->_prepareInfoText(4, $userData, $price)
                );
            }
            else {
                $coupon = new AB_Coupon();
                $coupon->loadBy( array(
                    'code' => $coupon_code,
                    'used' => 0,
                ) );

                if ( $coupon->isLoaded() ) {
                    $userData->saveData( array( 'coupon' => $coupon_code ) );
                    $price = $coupon->apply( $price );
                    $response = array(
                        'status'   => 'success',
                        'text'     => $this->_prepareInfoText(4, $userData, $price),
                        'discount' => $coupon->get( 'discount' )
                    );
                }
                else {
                    $userData->saveData( array( 'coupon' => null ) );
                    $response = array(
                        'status' => 'error',
                        'error'  => __('* This coupon code is invalid or has been used', 'ab'),
                        'text'   => $this->_prepareInfoText(4, $userData, $price)
                    );
                }
            }
        }

        // Output JSON response.
        if ( $response === null ) {
            $response = array( 'status' => 'no-data' );
        }
        header( 'Content-Type: application/json' );
        echo json_encode( $response );

        exit (0);
    }

    /**
     * Render progress tracker into a variable.
     *
     * @param int $booking_step
     * @param int|bool $price
     */
    private function _prepareProgressTracker( $booking_step, $price = false ) {
        $payment_disabled = (
            AB_BookingConfiguration::isPaymentDisabled()
            ||
            // If price is passed and it is zero then do not display payment step.
            $price !== false &&
            $price <= 0
        );

        $this->progress_tracker = $this->render( '_progress_tracker', array(
            'booking_step'     => $booking_step,
            'payment_disabled' => $payment_disabled
        ), false );
    }

    /**
     * Render info text into a variable.
     *
     * @param int $booking_step
     * @param AB_UserBookingData $userData
     * @param int $preset_price
     *
     * @return string
     */
    private function _prepareInfoText( $booking_step, $userData, $preset_price = null ) {
        $service = $userData->getService();
        $category_name = $userData->getCategoryName();
        $staff_name = $userData->getStaffName();
        $price = ($preset_price === null)? $userData->getServicePrice() : $preset_price;

        // Convenient Time
        if ( $booking_step === 2 ) {
            $replacement = array(
                '[[STAFF_NAME]]'   => '<b>' . $staff_name . '</b>',
                '[[SERVICE_NAME]]' => '<b>' . $service->get( 'title' ) . '</b>',
                '[[CATEGORY_NAME]]' => '<b>' . $category_name . '</b>',
            );

            return str_replace( array_keys( $replacement ), array_values( $replacement ),
                nl2br( esc_html( get_option( 'ab_appearance_text_info_second_step' ) ) )
            );
        }

        // Your Details
        if ( $booking_step === 3 ) {
            if ( get_option( 'ab_settings_use_client_time_zone' ) ) {
                $service_time = date_i18n( get_option( 'time_format' ), strtotime( $userData->get( 'appointment_datetime' ) ) - (($userData->get( 'time_zone_offset' ) + get_option( 'gmt_offset' ) * 60) * 60));
            }
            else {
                $service_time = date_i18n( get_option( 'time_format' ), strtotime( $userData->get( 'appointment_datetime' ) ) );
            }
            $service_date = date_i18n( get_option( 'date_format' ), strtotime( $userData->get( 'appointment_datetime' ) ) );

            $replacement = array(
                '[[STAFF_NAME]]'    => '<b>' . $staff_name . '</b>',
                '[[SERVICE_NAME]]'  => '<b>' . $service->get( 'title' ) . '</b>',
                '[[CATEGORY_NAME]]' => '<b>' . $category_name . '</b>',
                '[[SERVICE_TIME]]'  => '<b>' . $service_time . '</b>',
                '[[SERVICE_DATE]]'  => '<b>' . $service_date . '</b>',
                '[[SERVICE_PRICE]]' => '<b>' . AB_CommonUtils::formatPrice( $price ) . '</b>',
            );

            return str_replace( array_keys( $replacement ), array_values( $replacement ),
                nl2br( esc_html( get_option( 'ab_appearance_text_info_third_step' ) ) )
            );
        }

        // Coupon Text
        if ($booking_step === 4) {
            $replacement = array(
                '[[SERVICE_PRICE]]' => '<b>' . AB_CommonUtils::formatPrice($price) . '</b>',
            );

            return str_replace(array_keys($replacement), array_values($replacement),
                nl2br(esc_html(get_option('ab_appearance_text_info_coupon')))
            );
        }

        return '';
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