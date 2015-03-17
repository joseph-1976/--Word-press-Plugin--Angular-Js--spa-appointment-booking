<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_Appointment
 */
class AB_Appointment extends AB_Entity {

    protected static $table_name = 'ab_appointment';

    protected static $schema = array(
        'id'              => array( 'format' => '%d' ),
        'staff_id'        => array( 'format' => '%d' ),
        'service_id'      => array( 'format' => '%d' ),
        'start_date'      => array( 'format' => '%s' ),
        'end_date'        => array( 'format' => '%s' ),
        'google_event_id' => array( 'format' => '%s' ),
    );

    /**
     * Get color of service
     *
     * @param string $default
     * @return string
     */
    public function getColor( $default = '#DDDDDD' ) {
        if ( ! $this->isLoaded() ) {
            return $default;
        }

        $service = new AB_Service();

        if ( $service->load( $this->get( 'service_id' ) ) ) {
            return $service->get( 'color' );
        }

        return $default;
    }

    /**
     * Get AB_CustomerAppointment entities associated with this appointment.
     *
     * @return AB_CustomerAppointment[]   Array of entities
     */
    public function getCustomerAppointments() {
        $result = array();

        if ( $this->get( 'id' ) ) {
            $records = $this->wpdb->get_results( $this->wpdb->prepare(
                'SELECT `ca`.*,
                        `c`.`name`,
                        `c`.`phone`,
                        `c`.`email`
                FROM `ab_customer_appointment` `ca` LEFT JOIN `ab_customer` `c` ON `c`.`id` = `ca`.`customer_id`
                WHERE `ca`.`appointment_id` = %d',
                $this->get( 'id' )
            ), ARRAY_A);

            foreach( $records as $data ) {
                $ca = new AB_CustomerAppointment();
                $ca->setData( $data );

                // Inject AB_Customer entity.
                $ca->customer = new AB_Customer();
                $data[ 'id' ] = $data[ 'customer_id' ];
                $ca->customer->setData( $data, true );

                $result[] = $ca;
            }
        }

        return $result;
    }

    /**
     * Set array of customers associated with this appointment.
     *
     * @param array $customers  Array of customer IDs or customer entities
     */
    public function setCustomers( array $customers ) {
        // Prepare array of customer IDs.
        $new_ids = $customers;
        if ( !empty ( $new_ids ) && $new_ids[ 0 ] instanceof AB_Customer ) {
            $new_ids = array_map( function( $customer ) { return $customer->get( 'id' ); }, $new_ids );
        }

        // Retrieve customer IDs currently associated with this appointment.
        $current_ids = array_map( function( $ca ) { return $ca->customer->get( 'id' ); }, $this->getCustomerAppointments() );

        // Remove redundant customers.
        $customer_appointment = new AB_CustomerAppointment();
        foreach ( array_diff( $current_ids, $new_ids ) as $id ) {
            if ( $customer_appointment->loadBy( array(
                'appointment_id' => $this->get( 'id' ),
                'customer_id'    => $id
            ) ) ) {
                $customer_appointment->delete();
            }
        }

        // Add new customers.
        foreach ( array_diff( $new_ids, $current_ids ) as $id ) {
            $customer_appointment = new AB_CustomerAppointment();
            $customer_appointment->set( 'appointment_id', $this->get('id') );
            $customer_appointment->set( 'customer_id', $id );
            $customer_appointment->save();
        }
    }

    /**
     * Save appointment to database
     *(and delete event in Google Calendar if staff changes).
     *
     * @return false|int
     */
    public function save() {
        // Google Calendar.
        if ( $this->isLoaded() && $this->hasGoogleCalendarEvent() ) {
            $modified = $this->getModified();
            if ( array_key_exists( 'staff_id', $modified ) ) {
                // Delete event from the Google Calendar of the old staff if the staff was changed.
                $staff_id = $this->get( 'staff_id' );
                $this->set( 'staff_id', $modified[ 'staff_id' ] );
                $this->deleteGoogleCalendarEvent();
                $this->set( 'staff_id', $staff_id );
                $this->set( 'google_event_id', null );
            }
        }

        return parent::save();
    }

    /**
     * Delete entity from database
     *(and delete event in Google Calendar if it exists).
     *
     * @return bool|false|int
     */
    public function delete(){
        $result = parent::delete();
        if ( $result && $this->hasGoogleCalendarEvent() ) {
            $this->deleteGoogleCalendarEvent();
        }

        return $result;
    }

    /**
     * Create or update event in Google Calendar.
     *
     * @return bool
     */
    public function handleGoogleCalendar() {
        if ( $this->hasGoogleCalendarEvent() ) {
            return $this->updateGoogleCalendarEvent();
        }
        else {
            $google_event_id = $this->createGoogleCalendarEvent();
            if ( $google_event_id ) {
                $this->set( 'google_event_id', $google_event_id );
                return (bool)$this->save();
            }
        }

        return false;
    }

    /**
     * Check whether this appointment has an associated event in Google Calendar.
     *
     * @return bool
     */
    public function hasGoogleCalendarEvent() {
        return $this->get( 'google_event_id' ) != '';
    }

    /**
     * Create a new event in Google Calendar and associate it to this appointment.
     *
     * @return string|false
     */
    public function createGoogleCalendarEvent() {
        $google = new AB_Google();
        if ( $google->loadByStaffId( $this->get( 'staff_id' ) ) ) {
            // Create new event in Google Calendar.
            return $google->createEvent( $this );

        }

        return false;
    }

    public function updateGoogleCalendarEvent() {
        $google = new AB_Google();
        if ( $google->loadByStaffId( $this->get( 'staff_id' ) ) ) {
            // Update existing event in Google Calendar.
            return $google->updateEvent( $this );
        }

        return false;
    }

    /**
     * Delete event from Google Calendar associated to this appointment.
     *
     * @return bool
     */
    public function deleteGoogleCalendarEvent() {
        $google = new AB_Google();
        if ( $google->loadByStaffId( $this->get( 'staff_id' ) ) ) {
            // Delete existing event in Google Calendar.
            return $google->delete( $this->get( 'google_event_id' ) );
        }

        return false;
    }

    /**
     * Send email notifications to client and staff member.
     *
     * @param int $time_zone_offset
     * @param AB_Coupon|bool $coupon
     */
    public function sendEmailNotifications( $time_zone_offset = null, $coupon = false ) {
        $client_notification = new AB_Notifications();
        $client_notification->loadBy( array( 'slug' => 'client_info' ) );

        $staff_notification = new AB_Notifications();
        $staff_notification->loadBy( array( 'slug' => 'provider_info' ) );

        $staff = new AB_Staff();
        $staff->load( $this->get( 'staff_id' ) );

        $service = new AB_Service();
        $service->load( $this->get( 'service_id' ) );

        $staff_service = new AB_StaffService();
        $staff_service->loadBy( array( 'staff_id' => $this->get( 'staff_id' ), 'service_id' => $this->get( 'service_id' ) ) );

        $category = new AB_Category();
        $category->load( $service->get( 'category_id' ) );

        $price = $staff_service->get( 'price' );
        if ( $coupon ) {
            $price = $coupon->apply( $price );
        }

        foreach ( $this->getCustomerAppointments() as $ca ) {
            $replacement = new AB_NotificationReplacement();
            $replacement->set( 'appointment_datetime', $this->get('start_date') );
            $replacement->set( 'appointment_token', $ca->get( 'token' ) );
            $replacement->set( 'category_name', $category->get( 'name' ) );
            $replacement->set( 'client_name', $ca->customer->get( 'name' ) );
            $replacement->set( 'client_phone', $ca->customer->get( 'phone' ) );
            $replacement->set( 'client_email', $ca->customer->get( 'email' ) );
            $replacement->set( 'service_name', $service->get( 'title' ) != '' ? $service->get( 'title' ) : __( 'Untitled', 'ab' ) );
            $replacement->set( 'service_price', $price );
            $replacement->set( 'staff_name', $staff->get( 'full_name' ) );
            $replacement->set( 'staff_email', $staff->get( 'email' ) );
            $replacement->set( 'staff_phone', $staff->get( 'phone' ) );
            $replacement->set( 'staff_photo', $staff->get( 'avatar_url' ) );

            $custom_fields = '';
            foreach ($ca->getCustomFields() as $custom_field) {
                $custom_fields .= sprintf(
                    "%s: %s\n",
                    $custom_field[ 'label' ], $custom_field[ 'value' ]
                );
            }
            $replacement->set( 'custom_fields', $custom_fields );

            if ( $staff_notification->get( 'active' ) ) {
                // Send email notification to service provider.
                $subject = $replacement->replace( $staff_notification->get( 'subject' ) );
                $message = wpautop( $replacement->replace( $staff_notification->get( 'message' ) ) );
                wp_mail( $staff->get( 'email' ), $subject, $message, AB_CommonUtils::getEmailHeaderFrom() );

                // Send copy to administrators
                if ( $staff_notification->get( 'copy' ) ) {
                    $admin_emails = AB_CommonUtils::getAdminEmails();
                    if ( ! empty ( $admin_emails ) ) {
                        wp_mail( $admin_emails, $subject, $message, AB_CommonUtils::getEmailHeaderFrom() );
                    }
                }
            }

            if ( $client_notification->get( 'active' ) ) {
                // Client time zone offset.
                if ( $time_zone_offset !== null ) {
                    $client_diff = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS + $time_zone_offset * 60;
                    $replacement->set( 'appointment_datetime', date( 'Y-m-d H:i:s', strtotime( $this->get( 'start_date' ) ) - $client_diff ) );
                }
                // Send email notification to client.
                $subject = $replacement->replace( $client_notification->get( 'subject' ) );
                $message = wpautop( $replacement->replace( $client_notification->get( 'message' ) ) );
                wp_mail( $ca->customer->get( 'email' ), $subject, $message, AB_CommonUtils::getEmailHeaderFrom() );
            }
        }
    }
}
