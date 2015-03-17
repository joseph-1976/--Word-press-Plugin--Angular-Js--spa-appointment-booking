<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_CalendarController
 *
 * @property $collection
 * @property $staff_services
 * @property $startDate
 * @property $period_start
 * @property $period_end
 * @property $customers
 * @property $staff_id
 * @property $service_id
 * @property $customer_id
 * @property $staff_collection
 * @property $date_interval_not_available
 * @property $date_interval_warning
 */
class AB_CalendarController extends AB_Controller  {

    protected function getPermissions() {
        return array('_this' => 'user');
    }

    public function index() {
        /** @var WP_Locale $wp_locale */
        global $wp_locale;

        $this->enqueueStyles( array(
            'module' => array(
                'css/jquery-ui-1.10.4/jquery-ui.min.css',
                'css/jquery.weekcalendar.css',
                'css/calendar.css',
                'css/chosen.css',
            ),
            'backend' => array(
                'css/ab_style.css',
                'bootstrap/css/bootstrap.min.css',
            )
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/date.js' => array( 'jquery' ),
                'js/angular-1.3.11.min.js',
                'js/angular-ui-date-0.0.7.js' => array( 'ab-angular-1.3.11.min.js' ),
                'js/ng-new_customer_dialog.js' => array( 'jquery', 'ab-angular-1.3.11.min.js' ),
            ),
            'module' => array(
                'js/chosen.jquery.js' => array( 'jquery' ),
                'js/jquery.weekcalendar.js' => array(
                    'jquery',
                    'jquery-ui-widget',
                    'jquery-ui-dialog',
                    'jquery-ui-button',
                    'jquery-ui-draggable',
                    'jquery-ui-droppable',
                    'jquery-ui-resizable',
                    'jquery-ui-datepicker'
                ),
                'js/ng-app.js' => array( 'jquery', 'ab-angular-1.3.11.min.js', 'ab-angular-ui-date-0.0.7.js' ),
                'js/calendar_daypicker.js'  => array( 'jquery', 'ab-jquery.weekcalendar.js' ),
                'js/calendar_weekpicker.js' => array( 'jquery', 'ab-jquery.weekcalendar.js' ),
                'js/calendar.js'  => array( 'jquery', 'ab-calendar_daypicker.js', 'ab-calendar_weekpicker.js', 'ab-ng-app.js', 'ab-jquery.weekcalendar.js' ),
            )
        ) );

        wp_localize_script( 'ab-jquery.weekcalendar.js', 'BooklyL10n', array(
            'new_appointment'  => __( 'New appointment', 'ab' ),
            'edit_appointment' => __( 'Edit appointment', 'ab' ),
            'are_you_sure'     => __( 'Are you sure?', 'ab' ),
            'phone'            => __( 'Phone', 'ab' ),
            'email'            => __( 'Email', 'ab' ),
            'timeslotsPerHour' => 60 / get_option('ab_settings_time_slot_length'),
            'shortMonths'      => array_values( $wp_locale->month_abbrev ),
            'longMonths'       => array_values( $wp_locale->month ),
            'shortDays'        => array_values( $wp_locale->weekday_abbrev ),
            'longDays'         => array_values( $wp_locale->weekday ),
            'AM'               => $wp_locale->meridiem[ 'AM' ],
            'PM'               => $wp_locale->meridiem[ 'PM' ],
            'Week'             => __( 'Week', 'ab' ) . ': ',
            'dateFormat'       => $this->dateFormatTojQueryUIDatePickerFormat(),
        ));

        if ( is_super_admin() ) {
            $this->collection = $this->getWpdb()->get_results( "SELECT * FROM ab_staff" );
        } else {
            $this->collection = $this->getWpdb()->get_results( $this->getWpdb()->prepare( "SELECT * FROM ab_staff s WHERE s.wp_user_id = %d", array(get_current_user_id()) ) );
        }

        $this->render( 'calendar', array( 'custom_fields' => json_decode( get_option( 'ab_custom_fields' ) ) ) );
    }
    /**
     * Get data for WeekCalendar in `week` mode.
     *
     * @return json
     */
    public function executeWeekStaffAppointments() {
        $result = array( 'events' => array(), 'freebusys' => array() );
        $staff_id = $this->getParameter( 'staff_id' );
        if ( $staff_id ) {
            $staff = new AB_Staff();
            $staff->load( $staff_id );

            $start_date = $this->getParameter( 'start_date' );
            $end_date   = $this->getParameter( 'end_date' );

            $staff_appointments = $staff->getAppointments( $start_date, $end_date );
            foreach ( $staff_appointments as $appointment ) {
                $result['events'][] = $this->getAppointment( $appointment );
            }

            $wpdb     = $this->getWpdb();
            $schedule = $wpdb->get_results( $wpdb->prepare(
                'SELECT
                     ssi.*
                 FROM `ab_staff_schedule_item` ssi
                 WHERE ssi.staff_id = %d',
                $staff_id
            ) );

            $holidays = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ab_holiday WHERE staff_id = %d OR staff_id IS NULL', $staff_id ) );

            if ( ! empty( $schedule ) ) {
                $wp_week_start_day  = get_option( 'start_of_week', 1 );
                $schedule_start_day = $schedule[0]->id - 1;

                // if wp week start day is higher than our
                // cut the list into 2 parts (before and after wp wp week start day)
                // move the second part of the list above the first one
                if ( $wp_week_start_day > $schedule_start_day ) {
                    $schedule_start = array_slice( $schedule, 0, $wp_week_start_day );
                    $schedule_end   = array_slice( $schedule, $wp_week_start_day );
                    $schedule       = $schedule_end;

                    foreach ( $schedule_start as $schedule_item ) {
                        $schedule[] = $schedule_item;
                    }
                }

                $active_schedule_items_ids = array();

                foreach ( $schedule as $item ) {
                    // if start time is NULL we consider that the day is "OFF"
                    if ( null !== $item->start_time ) {
                        $day_name = AB_DateUtils::getWeekDayByNumber($item->day_index - 1);
                        if ($day_name == 'Sunday' && $wp_week_start_day == 0){
                            $date = date( 'Y-m-d', strtotime( $day_name . ' last week', strtotime( $start_date ) ) );
                        }else{
                            $date = date( 'Y-m-d', strtotime( $day_name . ' this week', strtotime( $start_date ) ) );
                        }
                        $startDate = new DateTime( $date . ' ' . $item->start_time );
                        $endDate   = new DateTime( $date . ' ' . $item->end_time );
                        // Skip holidays
                        foreach ( $holidays as $holiday ) {
                            $holidayDate = new DateTime($holiday->holiday);
                            if ( $holiday->repeat_event ) {
                                if ($holidayDate->format('m-d') == $startDate->format('m-d')) {
                                    continue 2;
                                }
                            } else {
                                if ($holidayDate->format('Y-m-d') == $startDate->format('Y-m-d')) {
                                    continue 2;
                                }
                            }
                        }

                        // get available day parts
                        $result['freebusys'][]       = $this->getFreeBusy( $startDate, $endDate, true );
                        $active_schedule_items_ids[] = $item->id;
                    }
                }

                if ( empty( $active_schedule_items_ids ) ) {
                    $active_schedule_items_ids = array( 0 );
                }

                $schedule_breaks = $wpdb->get_results(
                    'SELECT
                         sib.*,
                         ssi.day_index AS "day_index"
                     FROM `ab_schedule_item_break` sib
                     LEFT JOIN `ab_staff_schedule_item` ssi ON sib.staff_schedule_item_id = ssi.id
                     WHERE sib.staff_schedule_item_id IN (' . implode( ', ', $active_schedule_items_ids ) . ')'
                );

                foreach ( $schedule_breaks as $break_item ) {
                    $day_name  = AB_DateUtils::getWeekDayByNumber($break_item->day_index - 1);
                    $date      = date( 'Y-m-d', strtotime( $day_name . ' this week', strtotime( $start_date ) ) );
                    $startDate = new DateTime( $date . ' ' . $break_item->start_time );
                    $endDate   = new DateTime( $date . ' ' . $break_item->end_time );

                    // get breaks
                    $result['freebusys'][] = $this->getFreeBusy( $startDate, $endDate, false );
                }
            }
        }
        echo json_encode( $result );
        exit;
    }

    /**
     * Get data for WeekCalendar in `day` mode.
     *
     * @return json
     */
    public function executeDayStaffAppointments() {
        $result = array( 'events' => array(), 'freebusys' => array() );
        $staff_ids = $this->getParameter( 'staff_id' );
        if (is_array($staff_ids)) {
            $wpdb = $this->getWpdb();

            $start_date = $this->getParameter( 'start_date' );

            $appointments = $wpdb->get_results( sprintf(
                  'SELECT
                        a.id,
                        a.start_date,
                        a.end_date,
                        s.title,
                        s.color,
                        staff.id AS "staff_id",
                        staff.full_name AS "staff_fullname",
                        ss.capacity AS max_capacity,
                        COUNT( ca.id ) AS current_capacity,
                        ca.customer_id
                    FROM ab_appointment a
                    LEFT JOIN ab_customer_appointment ca ON ca.appointment_id = a.id
                    LEFT JOIN ab_service s ON a.service_id = s.id
                    LEFT JOIN ab_staff staff ON a.staff_id = staff.id
                    LEFT JOIN ab_staff_service ss ON ss.staff_id = a.staff_id AND ss.service_id = a.service_id
                    WHERE DATE(a.start_date) = DATE("%s") AND a.staff_id IN (%s)
                    GROUP BY a.id',
                  $wpdb->_real_escape($start_date),
                  implode(',', array_merge(array(0), array_map('intval', $staff_ids)))
              ) );

            foreach ( $appointments as $appointment ) {
                $result['events'][] = $this->getAppointment( $appointment, $appointment->staff_id );
            }

            $day_index = date("N", strtotime($start_date)) + 1;
            $schedule  = $wpdb->get_results(
                'SELECT
                     ssi.*,
                     s.id AS "staff_id"
                 FROM `ab_staff_schedule_item` ssi
                 LEFT JOIN `ab_staff` s ON ssi.staff_id = s.id
                 WHERE ssi.day_index = "' . $day_index . '"
                 AND ssi.start_time IS NOT NULL'
            );

            $active_schedule_items_ids = array();

            foreach ( $schedule as $item ) {
                $startDate = new DateTime(date( 'Y-m-d', strtotime( $start_date ) ) . ' ' . $item->start_time);
                $endDate = new DateTime(date( 'Y-m-d', strtotime( $start_date ) ) . ' ' . $item->end_time);

                $holidays = $wpdb->get_results($wpdb->prepare(
                        'SELECT * FROM ab_holiday WHERE staff_id = %d and ((`repeat_event` = 0 and DATE_FORMAT( `holiday` , "%%Y-%%m-%%d" ) = %s) or (`repeat_event` = 1 and DATE_FORMAT( `holiday` , "%%Y-%%m" ) = %s))',
                        array($item->staff_id, $startDate->format('Y-m-d'), $startDate->format('m-d')))
                );
                if (!$holidays){
                    $result['freebusys'][] = $this->getFreeBusy( $startDate, $endDate, true, $item->staff_id );
                    $active_schedule_items_ids[] = $item->id;
                }
            }

            if ( empty($active_schedule_items_ids) ) {
                $active_schedule_items_ids = array( 0 );
            }

            $schedule_breaks = $wpdb->get_results(
                'SELECT
                     sib.*,
                     s.id AS "staff_id"
                 FROM `ab_schedule_item_break` sib
                 LEFT JOIN `ab_staff_schedule_item` ssi ON sib.staff_schedule_item_id = ssi.id
                 LEFT JOIN `ab_staff` s ON ssi.staff_id = s.id
                 WHERE sib.staff_schedule_item_id IN (' . implode( ', ', $active_schedule_items_ids ) . ')'
            );

            foreach ( $schedule_breaks as $break_item ) {
                $startDate = new DateTime(date( 'Y-m-d', strtotime( $start_date ) ) . ' ' . $break_item->start_time);
                $endDate = new DateTime(date( 'Y-m-d', strtotime( $start_date ) ) . ' ' . $break_item->end_time);

                $result['freebusys'][] = $this->getFreeBusy( $startDate, $endDate, false, $break_item->staff_id );
            }
        }
        echo json_encode( $result );
        exit;
    }

    /**
     * Get data needed for appointment form initialisation.
     */
    public function executeGetDataForAppointmentForm() {
        $result = array(
            'staff'         => array(),
            'customers'     => array(),
            'custom_fields' => array(),
            'time'          => array(),
            'time_interval' => get_option( 'ab_settings_time_slot_length' ) * 60
        );

        // Staff list.
        $em = AB_EntityManager::getInstance( 'AB_Staff' );
        if ( is_super_admin() ) {
            $staff_members = $em->findAll();
        }
        else {
            $staff_members = $em->findBy( array( 'wp_user_id' => get_current_user_id() ) );
        }
        /** @var AB_Staff $staff_member */
        foreach ( $staff_members as $staff_member ) {
            $services = array();
            foreach ( $staff_member->getStaffServices() as $staff_service ) {
                $services[] = array(
                    'id'       => $staff_service->service->get( 'id' ),
                    'title'    => sprintf(
                        '%s (%s)',
                        $staff_service->service->get( 'title' ),
                        AB_Service::durationToString( $staff_service->service->get( 'duration' ) )
                    ),
                    'duration' => $staff_service->service->get( 'duration' ),
                    'capacity' => $staff_service->get( 'capacity' )
                );
            }
            $result[ 'staff' ][] = array(
                'id'        => $staff_member->get( 'id' ),
                'full_name' => $staff_member->get( 'full_name' ),
                'services'  => $services
            );
        }

        // Customers list.
        $em = AB_EntityManager::getInstance( 'AB_Customer' );
        foreach ( $em->findAll( array( 'name' => 'asc' ) ) as $customer ) {
            $name = $customer->get('name');
            if ( $customer->get( 'email' ) != '' && $customer->get( 'phone' ) != '' ) {
                $name .= ' (' . $customer->get( 'email' ) . ', ' . $customer->get( 'phone' ) . ')';
            }
            else if ( $customer->get( 'email' ) != '' ) {
                $name .= ' (' . $customer->get( 'email' ) . ')';
            }
            else if ( $customer->get( 'phone' ) ) {
                $name .= ' (' . $customer->get( 'phone' ) . ')';
            }

            $result[ 'customers' ][] = array(
                'id'   => $customer->get( 'id' ),
                'name' => $name,
            );
        }

        // Time list.
        $tf         = get_option( 'time_format' );
        $ts_length  = get_option( 'ab_settings_time_slot_length' );
        $time_start = new AB_DateTime( AB_StaffScheduleItem::WORKING_START_TIME, new DateTimeZone( 'UTC' ) );
        $time_end   = new AB_DateTime( AB_StaffScheduleItem::WORKING_END_TIME, new DateTimeZone( 'UTC' ) );

        // Run the loop.
        while ( $time_start->format( 'U' ) <= $time_end->format( 'U' ) ) {
            $result[ 'time' ][ ] = array(
                'value' => $time_start->format( 'H:i' ),
                'title' => date_i18n( $tf, $time_start->format( 'U' ) )
            );
            $time_start->modify( '+' . $ts_length . ' min' );
        }

        echo json_encode( $result );
        exit (0);
    }

    /**
     * Get appointment data when editing the appointment.
     */
    public function executeGetDataForAppointment() {
        /**
         * @var WPDB $wpdb
         */
        global $wpdb;

        $response = array( 'status' => 'error', 'data' => array('customers' => array()) );

        $appointment = new AB_Appointment();
        if ( $appointment->load( $this->getParameter( 'id' ) ) ) {
            $response[ 'status' ] = 'ok';
            $response[ 'data' ][ 'service_id' ]  = $appointment->get( 'service_id' );

            $appointment_additional_info = $wpdb->get_row( $wpdb->prepare(
                'SELECT
                  ss.capacity AS max_capacity,
                  COUNT( ca.id ) AS current_capacity
              FROM ab_appointment a
              LEFT JOIN ab_customer_appointment ca ON ca.appointment_id = a.id
              LEFT JOIN ab_staff_service ss ON ss.staff_id = a.staff_id AND ss.service_id = a.service_id
              WHERE a.id = %d',
                $appointment->get('id')
            ) );

            $response[ 'data' ][ 'current_capacity' ] = $appointment_additional_info->current_capacity;
            $response[ 'data' ][ 'max_capacity' ] = $appointment_additional_info->max_capacity;

            $customers = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT ca.*
                     FROM ab_customer_appointment ca
                     LEFT JOIN ab_customer ac ON ac.id = ca.customer_id
                     WHERE ca.appointment_id = %d',
                    $appointment->get('id')
                )
            );

            foreach($customers as $customer){
                $response[ 'data' ][ 'customers' ][] = $customer->customer_id;
                $response[ 'data' ][ 'custom_fields' ][] = array(
                    'customer_id'   => $customer->customer_id,
                    'fields'        => $customer->custom_fields ? json_decode($customer->custom_fields, true) : array(),
                );
            }
        }

        exit ( json_encode( $response ) );
    }

    /**
     * Save appointment form (for both create and edit).
     */
    public function executeSaveAppointmentForm() {
        $response = array( 'status' => 'error' );

        $start_date     = date('Y-m-d H:i:s', strtotime( $this->getParameter('start_date' ) ) );
        $end_date       = date('Y-m-d H:i:s', strtotime( $this->getParameter( 'end_date' ) ) );
        $staff_id       = $this->getParameter( 'staff_id' );
        $service_id     = $this->getParameter( 'service_id' );
        $appointment_id = $this->getParameter( 'id',  0 );
        $customers      = json_decode( $this->getParameter( 'customers', '[]' ) );
        $custom_fields  = json_decode( $this->getParameter( 'custom_fields', '[]' ) );

        $staff_service = new AB_StaffService();
        $staff_service->loadBy( array(
            'staff_id'   => $staff_id,
            'service_id' => $service_id
        ) );

        // Check for errors.
        if ( !$service_id ) {
            $response[ 'errors' ][ 'service_required' ] = true;
        }
        if ( empty ( $customers ) ) {
            $response[ 'errors' ][ 'customers_required' ] = true;
        }
        if ( !$this->dateIntervalIsAvailableForAppointment( $start_date, $end_date, $staff_id, $appointment_id ) ) {
            $response[ 'errors' ][ 'date_interval_not_available' ] = true;
        }
        if ( count( $customers ) > $staff_service->get( 'capacity' ) ) {
            $response[ 'errors' ][ 'overflow_capacity' ] = __( 'The number of customers should be not more than ', 'ab' ) . $staff_service->get( 'capacity' );
        }
        if ( !$this->getParameter( 'start_date' )) {
            $response[ 'errors' ][ 'time_interval' ] = __( 'Start time must not be empty', 'ab' );
        }elseif ( !$this->getParameter( 'end_date' )) {
            $response[ 'errors' ][ 'time_interval' ] = __( 'End time must not be empty', 'ab' );
        }elseif ( $start_date == $end_date ) {
            $response[ 'errors' ][ 'time_interval' ] = __( 'End time must not be equal to start time', 'ab' );
        }

        // If no errors then try to save the appointment.
        if ( !isset ( $response[ 'errors' ] ) ) {
            $appointment = new AB_Appointment();
            if ( $appointment_id ) {
                // edit
                $appointment->load( $appointment_id );
            }
            $appointment->set( 'start_date', $start_date );
            $appointment->set( 'end_date',   $end_date );
            $appointment->set( 'staff_id',   $staff_id );
            $appointment->set( 'service_id', $service_id );

            if ( $appointment->save() !== false ) {
                // Save customers.
                $appointment->setCustomers( $customers );

                // Save custom fields
                if ( $ac = $appointment->getCustomerAppointments() and count( $ac ) ) {
                    /** @var AB_CustomerAppointment $customer_appointment */
                    foreach ( $ac as $customer_appointment ) {
                        $customer_appointment->set( 'custom_fields', '' );
                        foreach ( $custom_fields as $fields ) {
                            if ( $customer_appointment->get( 'customer_id' ) == $fields->customer_id ) {
                                $customer_appointment->set( 'custom_fields', json_encode($fields->fields ) );
                            }
                        }
                        $customer_appointment->save();
                    }
                }

                // Google Calendar.
                $appointment->handleGoogleCalendar();

                if ( $this->getParameter( 'email_notification' ) === 'true' ) {
                    $appointment->sendEmailNotifications();
                }

                $startDate = new DateTime( $appointment->get('start_date') );
                $endDate   = new DateTime( $appointment->get('end_date') );
                $desc      = array();
                if ( $staff_service->get( 'capacity' ) == 1 ) {
                    $customer_appointments = $appointment->getCustomerAppointments();
                    if ( !empty ( $customer_appointments ) ) {
                        $ca = $customer_appointments[ 0 ]->customer;
                        foreach ( array( 'name', 'phone', 'email' ) as $data_entry ) {
                            $entry_value = $ca->get( $data_entry );
                            if ( $entry_value ) {
                                $desc[] = '<div class="wc-employee">' . esc_html( $entry_value ) . '</div>';
                            }
                        }

                        foreach ($customer_appointments[0]->getCustomFields() as $custom_field) {
                            $desc[] = '<div class="wc-notes">' . esc_html( $custom_field['label'] ) . ': ' . esc_html( $custom_field['value'] ) . '</div>';
                        }
                    }
                }
                else {
                    $desc[] = '<div class="wc-notes">' . __( 'Signed up', 'ab' ) . ' ' . count( $appointment->getCustomerAppointments() ) . '</div>';
                    $desc[] = '<div class="wc-notes">' . __( 'Capacity', 'ab' ) . ' ' . $staff_service->get( 'capacity' ) . '</div>';
                }

                $service   = new AB_Service();
                $service->load( $service_id );

                $response[ 'status' ] = 'ok';
                $response[ 'data' ]   = array(
                    'id'     => (int)$appointment->get( 'id' ),
                    'start'  => $startDate->format( 'm/d/Y H:i' ),
                    'end'    => $endDate->format( 'm/d/Y H:i' ),
                    'desc'   => implode('', $desc),
                    'title'  => $service->get( 'title' ) ? $service->get( 'title' ) : __( 'Untitled', 'ab' ),
                    'color'  => $service->get( 'color' ),
                    'userId' => (int)$appointment->get( 'staff_id' ),
                );
            }
            else {
                $response[ 'errors' ] = array( 'unknown' => true );
            }
        }

        exit (json_encode($response));
    }

    public function executeCheckAppointmentDateSelection() {
        $start_date     = $this->getParameter( 'start_date' );
        $end_date       = $this->getParameter( 'end_date' );
        $staff_id       = $this->getParameter( 'staff_id' );
        $service_id     = $this->getParameter( 'service_id' );
        $appointment_id = $this->getParameter( 'appointment_id' );
        $timestamp_diff = strtotime( $end_date ) - strtotime( $start_date );

        $result = array(
            'date_interval_not_available' => false,
            'date_interval_warning' => false,
        );

        if ( !$this->dateIntervalIsAvailableForAppointment( $start_date, $end_date, $staff_id, $appointment_id ) ) {
            $result['date_interval_not_available'] = true;
        }

        if ( $service_id ) {
            $service = new AB_Service();
            $service->load( $service_id );

            $duration = $service->get( 'duration' );

            // service duration interval is not equal to
            $result['date_interval_warning'] = ($timestamp_diff != $duration);
        }

        echo json_encode( $result );
        exit;
    }

    public function executeDeleteAppointment() {
        $appointment = new AB_Appointment();
        $appointment->load( $this->getParameter( 'appointment_id' ) );
        $appointment->delete();
        exit;
    }

    /**
     * @param $start_date
     * @param $end_date
     * @param $staff_id
     * @param $appointment_id
     * @return bool
     */
    private function dateIntervalIsAvailableForAppointment( $start_date, $end_date, $staff_id, $appointment_id ) {
        return ! is_object( $this->getWpdb()->get_row( $this->getWpdb()->prepare(
            'SELECT * FROM `ab_appointment`
             WHERE (
                 start_date > %s AND start_date < %s
                 OR (end_date > %s AND end_date < %s)
                 OR (start_date < %s AND end_date > %s)
                 OR (start_date = %s OR end_date = %s)
             )
             AND staff_id = %d
             AND id <> %d',
            $start_date,
            $end_date,
            $start_date,
            $end_date,
            $start_date,
            $end_date,
            $start_date,
            $end_date,
            $staff_id,
            $appointment_id
        ) ) );
    }

    /**
     * @param $id
     *
     * @return AB_Customer
     */
    public function getCustomer( $id ) {
        $customer      = new AB_Customer();
        $customer_data = $this->getWpdb()->get_row( $this->getWpdb()->prepare(
            'SELECT * FROM `ab_customer` WHERE id = %d', $id
        ) );
        // populate customer with data
        if ( $customer_data ) {
            $customer->setData( $customer_data );
        }

        return $customer;
    }

    /**
     * Get appointment data
     *
     * @param stdClass     $appointment
     * @param null         $user_id
     *
     * @return array
     */
    private function getAppointment( stdClass $appointment, $user_id = null ) {
        $startDate = new DateTime( $appointment->start_date );
        $endDate   = new DateTime( $appointment->end_date );
        $desc = array();

        if ($appointment->max_capacity == 1){
            $customer = new AB_Customer();
            $customer->load($appointment->customer_id);
            foreach ( array( 'name', 'phone', 'email' ) as $data_entry ) {
                $entry_value = $customer->get( $data_entry );
                if ( $entry_value ) {
                    $desc[] = '<div class="wc-employee">' . esc_html( $entry_value ) . '</div>';
                }
            }

            $customer_appointment = new AB_CustomerAppointment();
            $customer_appointment->loadBy(array(
                'customer_id'    => $customer->get('id'),
                'appointment_id' => $appointment->id
            ));

            foreach ($customer_appointment->getCustomFields() as $custom_field) {
                $desc[] = '<div class="wc-notes">' . esc_html( $custom_field['label'] ) . ' : ' . esc_html( $custom_field['value'] ) . '</div>';
            }
        }else{
            $desc[] = '<div class="wc-notes">Signed up ' . $appointment->current_capacity . '</div>';
            $desc[] = '<div class="wc-notes">Capacity ' . $appointment->max_capacity . '</div>';
        }

        $appointment_data = array(
            'id'    => $appointment->id,
            'start' => $startDate->format( 'm/d/Y H:i' ),
            'end'   => $endDate->format( 'm/d/Y H:i' ),
            'title' => $appointment->title ? esc_html( $appointment->title ) : __( 'Untitled', 'ab' ),
            'desc'  => implode('', $desc),
            'color' => $appointment->color
        );

        // if needed to be rendered for a specific user
        // pass the the user id
        if ( null !== $user_id ) {
            $appointment_data['userId'] = $user_id;
        }
        return $appointment_data;
    }

    /**
     * Get free busy data
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param          $free
     * @param null     $user_id
     *
     * @return array
     */
    private function getFreeBusy( DateTime $startDate, DateTime $endDate, $free, $user_id = null ) {
        $freebusy_data = array(
            'start' => $startDate->format( 'm/d/Y H:i' ),
            'end'   => $endDate->format( 'm/d/Y H:i' ),
            'free'  => $free
        );
        // if needed to be rendered for a specific user
        // pass the the user id
        if ( null !== $user_id ) {
            $freebusy_data['userId'] = $user_id;
        }
        return $freebusy_data;
    }

    /**
     * @return string
     */
    private function dateFormatTojQueryUIDatePickerFormat() {
        $chars = array(
            // Day
            'd' => 'dd', 'j' => 'd', 'l' => 'DD', 'D' => 'D',
            // Month
            'm' => 'mm', 'n' => 'm', 'F' => 'MM', 'M' => 'M',
            // Year
            'Y' => 'yy', 'y' => 'y',
            // Others
            'S' => '',
        );

        return strtr((string)get_option('date_format'), $chars);
    }

     /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     */
    protected function registerWpActions( $prefix = '' ) {
        parent::registerWpActions( 'wp_ajax_ab_' );
    }
}
