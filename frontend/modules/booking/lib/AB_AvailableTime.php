<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AB_AvailableTime {

    /**
     * @var AB_UserBookingData
     */
    private $_userData;

    private $_staffIdsStr = '0';

    private $service_duration = 0;

    private $staff_working_hours = array();

    private $start_date;

    private $bookings = array();

    private $prices = array();

    private $holidays = array();

    private $has_more_slots = false;

    /**
     * @var array
     */
    private $time = array();

    /**
     * Constructor.
     *
     * @param $userBookingData
     */
    public function __construct( AB_UserBookingData $userBookingData ) {

        // Store $userBookingData.
        $this->_userData = $userBookingData;

        // Prepare staff ids string for SQL queries.
        $this->_staffIdsStr = implode( ', ', array_merge(
            array_map( 'intval', $userBookingData->get( 'staff_ids' ) ),
            array( 0 )
        ) );
    }

    public function load() {
        // Load staff hours with breaks.
        $this->staff_working_hours = $this->_getStaffHours();

        // Load bookings.
        $this->bookings = $this->_getBookings();

        // Merge Google Calendar events with original bookings.
        if  ( get_option( 'ab_settings_google_two_way_sync' ) ) {
            foreach ($this->_getGCEvents() as $staff_id => $events) {
                if (isset ($this->bookings[$staff_id])) {
                    $this->bookings[$staff_id] = array_merge($this->bookings[$staff_id], $events);
                } else {
                    $this->bookings[$staff_id] = $events;
                }
            }
        }

        // Load service prices for every staff member.
        $this->prices = $this->_getPrices();

        // Load holidays for every staff member.
        $this->holidays = $this->_getHolidays();

        // Service duration
        $service = $this->_userData->getService();
        $this->service_duration = (int) $service->get( 'duration' );

        $date = new DateTime( $this->start_date ? ($this->start_date . '+1 day') : $this->_userData->get( 'date_from' ) );
        $now = new DateTime( '@' . current_time( 'timestamp' ) );
        if ( $now > $date ) {
            $date = $now;
        }
        $date = $date->format( 'Y-m-d' );

        if ( count( $this->_userData->get( 'days' ) ) && !empty ( $this->staff_working_hours ) ) {
            $items_number = 0; // number of handled slots
            $now->modify( '+' . AB_BookingConfiguration::getMaximumAvailableDaysForBooking() . ' days' );
            $maximum_date       = $now->format( 'Y-m-d' );
            $days               = 0; // number of handled days
            $day_per_column     = AB_BookingConfiguration::getShowDayPerColumn();

            while (
                // get the 10 columns/request
                ( ( $day_per_column && $days < 10 /* one day/column */) || ( !$day_per_column && $items_number < 100 /* 10 slots/column * 10 columns */) )
                &&
                // don't exceed limit of days from settings
                $date <= $maximum_date
            ) {
                $date = $this->_findAvailableDay( $date );
                if ( $date ) {
                    $available_time = $this->_findAvailableTime( $date );
                    if ( !empty ( $available_time ) ) {

                        // Client time zone offset.
                        $client_diff = get_option( 'ab_settings_use_client_time_zone' )
                            ? get_option( 'gmt_offset' ) * HOUR_IN_SECONDS + $this->_userData->get( 'time_zone_offset' ) * 60
                            : 0;

                        foreach ( $available_time as $item ) {
                            // Handle not full bookings (when number of bookings is less than capacity).
                            if ( isset ( $item[ 'not_full' ] ) ) {
                                if ( !isset( $this->time[ $date ][ $item[ 'start' ] ] ) ) {
                                    $this->_addTime($date, $item[ 'start' ] - $client_diff, $item['staff_id'], $item[ 'start' ]);
                                }
                                else {
                                    // Change staff member for this slot if the other one has higher price.
                                    if ( $this->prices[ $this->time[ $date ][ $item[ 'start' ] ][ 'staff_id' ] ] < $this->prices[ $item[ 'staff_id' ] ] ) {
                                        $this->time[ $date ][ $item[ 'start' ] ][ 'staff_id' ] = $item[ 'staff_id' ];
                                    }
                                }
                                continue;
                            }
                            // Loop from start to end with time slot length step.
                            for (
                                $time = $item[ 'start' ];
                                $time <= ($item[ 'end' ] - $this->service_duration);
                                $time += AB_BookingConfiguration::getTimeSlotLength()
                            ) {
                                // Resolves intersections
                                if ( !isset($this->time[ $date ][ $time ] ) ) {

                                    if ( $client_diff > $time) {
                                        $slot_date = date( 'Y-m-d', strtotime( $date . ' -1 day' ) );
                                    } else if ( $time - $client_diff >= 86400 ) {
                                        $slot_date = date( 'Y-m-d', strtotime( $date . ' +1 day' ) );
                                    } else {
                                        $slot_date = $date;
                                    }

                                    if ($this->_addDate($slot_date)){
                                        ++ $items_number;
                                        ++ $days;
                                    }

                                    $this->_addTime($date, $time - $client_diff, $item['staff_id'], $time, isset($item['booked']) ? true : false);
                                    ++ $items_number;
                                }
                                else {
                                    // Change staff member for this slot if the other one has higher price.
                                    if ( $this->prices[ $this->time[ $date ][ $time ][ 'staff_id' ] ] < $this->prices[ $item[ 'staff_id' ] ] ) {
                                        $this->time[ $date ][ $time ][ 'staff_id' ] = $item[ 'staff_id' ];
                                    }
                                }
                            }
                        }
                    }
                }
                else {
                    break;
                }
                $date = date( 'Y-m-d', strtotime( $date . ' +1 day' ) );
            }
            // detect if have more slots
            if ($date <= $maximum_date) {
                while($date <= $maximum_date) {
                    $date = $this->_findAvailableDay( $date );
                    if ( $date ) {
                        $available_time = $this->_findAvailableTime( $date );
                        if ( !empty ( $available_time ) ) {
                            $this->has_more_slots = true;
                            break;
                        }
                    }
                    $date = date( 'Y-m-d', strtotime( $date . ' +1 day' ) );
                }
            }
        }
    }

    /**
     * Find a day which is available for booking based on
     * user requested set of days.
     *
     * @access private
     * @param string $date
     * @return string | false
     */
    private function _findAvailableDay( $date ) {
        $datetime = new DateTime( $date );
        $attempt  = 0;
        // Find available day within requested days.
        $customer_requested_days = $this->_userData->get( 'days' );
        while ( !in_array( $datetime->format( 'w' ) + 1, $customer_requested_days ) ) {
            $datetime->modify( '+1 day' );
            if ( ++ $attempt >= 7 ) {
                return false;
            }
        }

        return $datetime->format( 'Y-m-d' );
    }

    /**
     * Find array of time slots available for booking
     * for given date.
     *
     * @access private
     * @param string $date
     * @return array
     */
    private function _findAvailableTime( $date ) {
        $result            = array();
        $time_slot_length  = AB_BookingConfiguration::getTimeSlotLength();
        $prior_time        = AB_BookingConfiguration::getMinimumTimePriorBooking();
        $current_timestamp = current_time( 'timestamp' ) + $prior_time;
        $current_date      = date( 'Y-m-d', $current_timestamp );

        if ( $date < $current_date ) {
            return array();
        }

        $day_of_week  = date( 'w', strtotime( $date ) ) + 1; // 1-7
        $current_time = date( 'H:i:s', ceil( $current_timestamp / $time_slot_length ) * $time_slot_length );

        foreach ( $this->staff_working_hours as $staff_id => $hours ) {
//            // if the capacity of staff service is less than selected by user, skip it
//            $available_capacity = $this->_getAvailableCapacity($staff_id, $this->_userData->get( 'service_id' ));
//            if ( $available_capacity < $this->_userData->getCapacity() ) {
//                continue;
//            }

            if ( isset ( $hours[ $day_of_week ] ) && $this->isWorkingDay( $date, $staff_id )) {
                // Find intersection between working and requested hours
                //(excluding time slots in the past).
                $working_start_time = ($date == $current_date && $current_time > $hours[ $day_of_week ][ 'start_time' ])
                    ? $current_time
                    : $hours[ $day_of_week ][ 'start_time' ];

                $intersection = $this->_findIntersection(
                    $this->_strToTime( $working_start_time ),
                    $this->_strToTime( $hours[ $day_of_week ][ 'end_time' ] ),
                    $this->_strToTime( $this->_userData->get( 'time_from' ) ),
                    $this->_strToTime( $this->_userData->get( 'time_to' ) )
                );

                if (is_array($intersection) && !array_key_exists('start', $intersection)){
                    $intersections = $intersection;
                    foreach ($intersections as $intersection){
                        if ( $intersection && $this->service_duration <= ( $intersection[ 'end' ] - $intersection[ 'start' ] ) ) {
                            // Initialize time frames.
                            $timeframes = array( array(
                                'start'    => $intersection[ 'start' ],
                                'end'      => $intersection[ 'end' ],
                                'staff_id' => $staff_id
                            ) );
                            // Remove breaks from the time frames.
                            foreach ( $hours[ $day_of_week ][ 'breaks' ] as $break ) {
                                $timeframes = $this->_removeTimePeriod(
                                    $timeframes,
                                    $this->_strToTime( $break[ 'start' ] ),
                                    $this->_strToTime( $break[ 'end' ] )
                                );
                            }
                            // Remove bookings from the time frames.
                            $bookings = isset ( $this->bookings[ $staff_id ] ) ? $this->bookings[ $staff_id ] : array();
                            foreach ( $bookings as $booking ) {
                                $bookingStart = new DateTime( $booking['start_date'] );
                                if ( $date == $bookingStart->format('Y-m-d') ) {
                                    $bookingEnd    = new DateTime( $booking['end_date'] );
                                    $booking_start = $bookingStart->format( 'U' ) % (24 * 60 * 60);
                                    $booking_end   = $bookingEnd->format( 'U' ) % (24 * 60 * 60);
                                    $timeframes    = get_option( 'ab_appearance_show_blocked_timeslots' ) == 1 ?
                                                     $this->_setBookedTimePeriod( $timeframes, $booking_start, $booking_end ):
                                                     $this->_removeTimePeriod( $timeframes, $booking_start, $booking_end );
                                }
                            }
                            $result = array_merge( $result, $timeframes );
                        }
                    }
                }
                else {
                    if ( $intersection && $this->service_duration <= ( $intersection[ 'end' ] - $intersection[ 'start' ] ) ) {
                        // Initialize time frames.
                        $timeframes = array( array(
                            'start'    => $intersection[ 'start' ],
                            'end'      => $intersection[ 'end' ],
                            'staff_id' => $staff_id
                        ) );
                        // Remove breaks from the time frames.
                        foreach ( $hours[ $day_of_week ][ 'breaks' ] as $break ) {
                            $timeframes = $this->_removeTimePeriod(
                                $timeframes,
                                $this->_strToTime( $break[ 'start' ] ),
                                $this->_strToTime( $break[ 'end' ] )
                            );
                        }
                        // Remove bookings from the time frames.
                        $bookings = isset ( $this->bookings[ $staff_id ] ) ? $this->bookings[ $staff_id ] : array();
                        foreach ( $bookings as $booking ) {
                            $bookingStart = new DateTime( $booking['start_date'] );
                            if ( $date == $bookingStart->format('Y-m-d') ) {
                                $bookingEnd    = new DateTime( $booking['end_date'] );
                                $booking_start = $bookingStart->format( 'U' ) % (24 * 60 * 60);
                                $booking_end   = $bookingEnd->format( 'U' ) % (24 * 60 * 60);

                                if (get_option( 'ab_appearance_show_blocked_timeslots' ) == 1) {
                                    if ($booking['number_of_bookings'] >= $booking['capacity'])
                                    {
                                        $timeframes = $this->_setBookedTimePeriod( $timeframes, $booking_start, $booking_end );
                                    }
                                } else {
                                    $timeframes = $this->_removeTimePeriod( $timeframes, $booking_start, $booking_end );

                                    if (
                                        $booking['number_of_bookings'] < $booking['capacity'] &&
                                        $booking['service_id'] == $this->_userData->get( 'service_id' ) &&
                                        $booking_start >= $intersection[ 'start' ]
                                    ) {
                                        $timeframes[] = array(
                                            'start'    => $booking_start,
                                            'end'      => $booking_end,
                                            'staff_id' => $staff_id,
                                            'not_full' => true
                                        );
                                    }
                                }
                            }
                        }
                        $result = array_merge( $result, $timeframes );
                    }
                }
            }
        }
        usort( $result, create_function( '$a, $b', 'return $a[\'start\'] - $b[\'start\'];' ) );

        return $result;
    }

    /**
     * Checks if the date is not a holiday for this employee
     * @param string $date
     * @param int $staff_id
     * @return bool
     */
    private function isWorkingDay( $date, $staff_id ) {
        $working_day = true;
        $date = new DateTime($date);
        if ( isset($this->holidays[ $staff_id ]) ) {
            foreach ( $this->holidays[ $staff_id ] as $holiday ) {
                $holidayDate = new DateTime($holiday->holiday);
                if ( $holiday->repeat_event ) {
                    $working_day = $holidayDate->format('m-d') != $date->format('m-d');
                } else {
                    $working_day = $holidayDate->format('Y-m-d') != $date->format('Y-m-d');
                }
                if ( !$working_day ) {
                    break;
                }
            }
        }

        return $working_day;
    }

    /**
     * Find intersection between 2 time periods.
     *
     * @param mixed $p1_start
     * @param mixed $p1_end
     * @param mixed $p2_start
     * @param mixed $p2_end
     * @return array | false
     */
    private function _findIntersection( $p1_start, $p1_end, $p2_start, $p2_end ) {
        $result = false;

        if ($p2_start > $p2_end){
            $result = array();
            $result[] = $this->_findIntersection($p1_start, $p1_end, 0, $p2_end);
            $result[] = $this->_findIntersection($p1_start, $p1_end, $p2_start, 86400);
        }else{
            if ( $p1_start <= $p2_start && $p1_end >= $p2_start && $p1_end <= $p2_end ) {
                $result = array( 'start' => $p2_start, 'end' => $p1_end );
            } else if ( $p1_start <= $p2_start && $p1_end >= $p2_end ) {
                $result = array( 'start' => $p2_start, 'end' => $p2_end );
            } else if ( $p1_start >= $p2_start && $p1_start <= $p2_end && $p1_end >= $p2_end ) {
                $result = array( 'start' => $p1_start, 'end' => $p2_end );
            } else if ( $p1_start >= $p2_start && $p1_end <= $p2_end ) {
                $result = array( 'start' => $p1_start, 'end' => $p1_end );
            }
        }

        return $result;
    }

    private function _setBookedTimePeriod( array $timeframes, $booking_start, $booking_end ) {
        $result = array();
        foreach ( $timeframes as $timeframe ) {
            $intersection = $this->_findIntersection(
                $timeframe[ 'start' ],
                $timeframe[ 'end' ],
                $booking_start,
                $booking_end
            );
            if ( $intersection  && $intersection['start'] != $intersection['end']) {
                if ( $timeframe[ 'start' ] < $intersection[ 'start' ] && $this->service_duration <= ( $intersection[ 'start' ] - $timeframe[ 'start' ] ) ) {
                    $result[] = array(
                        'start'    => $timeframe[ 'start' ],
                        'end'      => $intersection[ 'start' ],
                        'staff_id' => $timeframe[ 'staff_id' ]
                    );
                }
                if ( $timeframe[ 'end' ] > $intersection[ 'end' ] && $this->service_duration <= ( $timeframe[ 'end' ] - $intersection[ 'end' ] ) ) {
                    $result[] = array(
                        'start'    => $intersection[ 'end' ],
                        'end'      => $timeframe[ 'end' ],
                        'staff_id' => $timeframe[ 'staff_id' ]
                    );
                }
                $result[] = array(
                    'start'     => $intersection[ 'start' ],
                    'end'       => $intersection[ 'end' ],
                    'staff_id'  => $timeframe[ 'staff_id' ],
                    'booked'    => true
                );
            } else {
                $result[] = $timeframe;
            }
        }

        return $result;
    }

    /**
     * Remove time period from the set of time frames.
     *
     * @param array $timeframes
     * @param mixed $p_start
     * @param mixed $p_end
     * @return array
     */
    private function _removeTimePeriod( array $timeframes, $p_start, $p_end ) {
        $result = array();
        foreach ( $timeframes as $timeframe ) {
            $intersection = $this->_findIntersection(
                $timeframe[ 'start' ],
                $timeframe[ 'end' ],
                $p_start,
                $p_end
            );
            if ( $intersection ) {
                if ( $timeframe[ 'start' ] < $intersection[ 'start' ] && $this->service_duration <= ( $intersection[ 'start' ] - $timeframe[ 'start' ] ) ) {
                    $result[] = array(
                        'start'    => $timeframe[ 'start' ],
                        'end'      => $intersection[ 'start' ],
                        'staff_id' => $timeframe[ 'staff_id' ]
                    );
                }
                if ( $timeframe[ 'end' ] > $intersection[ 'end' ] && $this->service_duration <= ( $timeframe[ 'end' ] - $intersection[ 'end' ] ) ) {
                    $result[] = array(
                        'start'    => $intersection[ 'end' ],
                        'end'      => $timeframe[ 'end' ],
                        'staff_id' => $timeframe[ 'staff_id' ]
                    );
                }
            } else {
                $result[] = $timeframe;
            }
        }

        return $result;
    }

    /**
     * Convert string to timestamp.
     *
     * @param $str
     * @return int
     */
    private function _strToTime( $str ) {
        return strtotime( sprintf( '1970-01-01 %s', $str ) );
    }

    /**
     * @return array
     */
    public function getTime() {
        return $this->time;
    }

    public function setStartDate( $start_date ) {
        $this->start_date = $start_date;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function hasMoreSlots() {
        return $this->has_more_slots;
    }

    /*******************
     * Private methods *
     *******************/

    /**
     * Get staff working hours with breaks.
     *
     * @return array
     * [
     *  [
     *      start_time => H:i:s,
     *      end_time   => H:i:s,
     *      breaks     => [ [start => H:i:s, end => H:i:s], ... ]
     *  ],
     *  ...
     * ]
     */
    private function _getStaffHours() {
        /** @var WPDB $wpdb */
        global $wpdb;

        $result = array();

        $rows = $wpdb->get_results( "
            SELECT `item`.*, `break`.`start_time` AS `break_start`, `break`.`end_time` AS `break_end`
                FROM `ab_staff_schedule_item` `item`
                LEFT JOIN `ab_schedule_item_break` `break` ON `item`.`id` = `break`.`staff_schedule_item_id`
            WHERE `item`.`staff_id` IN ({$this->_staffIdsStr}) AND `item`.`start_time` IS NOT NULL
        " );

        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                if ( !isset ( $result[ $row->staff_id ][ $row->day_index ] ) ) {
                    $result[ $row->staff_id ][ $row->day_index ] = array(
                        'start_time' => $row->start_time,
                        'end_time'   => $row->end_time,
                        'breaks'     => array(),
                    );
                }
                if ( $row->break_start ) {
                    $result[ $row->staff_id ][ $row->day_index ][ 'breaks' ][] = array(
                        'start' => $row->break_start,
                        'end'   => $row->break_end
                    );
                }
            }
        }

        return $result;
    }

//    /**
//     * @param $staff_id
//     * @param $service_id
//     * @return int
//     */
//    private function _getAvailableCapacity($staff_id, $service_id) {
//        /** @var WPDB $wpdb */
//        global $wpdb;
//
//        $row    = $wpdb->get_row( $wpdb->prepare("SELECT capacity FROM ab_staff_service WHERE staff_id = %d AND service_id = %d", intval( $staff_id ), intval( $service_id ) ) );
//        $row2   = $wpdb->get_row( $wpdb->prepare( "
//            SELECT COUNT(*) AS booked
//            FROM ab_customer_appointment ca
//            LEFT JOIN ab_appointment a ON a.id = ca.appointment_id
//            WHERE a.staff_id = %d
//            AND a.service_id = %d",
//            intval( $staff_id ), intval( $service_id ) )
//        );
//
//        if ( $row ) {
//            if ( $row->capacity == 1 ) {
//                return 1;
//            }
//
//            if ( $row2 ) {
//                return $row->capacity - $row2->booked;
//            } else {
//                return 1;
//            }
//        }
//
//        return 0;
//    }

    /**
     * Get array of appointments.
     *
     * @return array
     * [
     *  staff_id => [ appointment_data ],
     *  ...
     * ]
     */
    private function _getBookings() {
        /** @var WPDB $wpdb */
        global $wpdb;

        $result = array();

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT `a`.*, `ss`.`capacity`, COUNT(*) AS `number_of_bookings`
                FROM `ab_customer_appointment` `ca`
                LEFT JOIN `ab_appointment` a ON `a`.`id` = `ca`.`appointment_id`
                LEFT JOIN `ab_staff_service` `ss` ON `ss`.`staff_id` = `a`.`staff_id` AND `ss`.`service_id` = `a`.`service_id`
             WHERE `a`.`staff_id` IN ({$this->_staffIdsStr}) AND `a`.`start_date` >= %s
             GROUP BY `a`.`start_date`, `a`.`staff_id`, `a`.`service_id`",
            $this->_userData->get( 'date_from' ) ), ARRAY_A );

        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                $result[ $row['staff_id'] ][] = $row;
            }
        }

        return $result;
    }

    /**
     * Get Google Calendar events for each staff member who has it attached.
     *
     * @return array
     * [
     *  staff_id => [ appointment_data ],
     *  ...
     * ]
     */
    private function _getGCEvents() {
        /** @var WPDB $wpdb */
        global $wpdb;

        $result = array();

        $rows = $wpdb->get_results("SELECT `id` AS `staff_id`, `google_data` FROM `ab_staff`
                                    WHERE `id` IN ({$this->_staffIdsStr}) AND `google_data` IS NOT NULL");

        if (is_array($rows)) {
            $startDate = new DateTime($this->_userData->get( 'date_from' ));
            foreach ($rows as $row) {
                $google = new AB_Google();
                $google->loadByStaffId($row->staff_id);

                foreach ($google->getCalendarEvents($startDate) as $event) {
                    $result[ $row->staff_id ][] = $event;
                }
            }
        }

        return $result;
    }

    /**
     * Get service prices of every staff member.
     *
     * @return array
     */
    private function _getPrices() {
        /** @var WPDB $wpdb */
        global $wpdb;

        $result = array();

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `ab_staff_service` WHERE `staff_id` IN ({$this->_staffIdsStr}) AND `service_id` = %d",
            $this->_userData->get( 'service_id' )
        ) );

        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                $result[ $row->staff_id ] = $row->price;
            }
        }

        return $result;
    }

    /**
     * Get holidays of every staff member.
     *
     * @return array
     */
    private function _getHolidays() {
        /** @var WPDB $wpdb */
        global $wpdb;

        $result = array();

        $rows = $wpdb->get_results( "SELECT * FROM `ab_holiday` WHERE `staff_id` IN ({$this->_staffIdsStr})" );

        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                $result[ $row->staff_id ][] = $row;
            }
        }

        return $result;
    }

    /**
     * Add date to Time Table (step 2)
     *
     * @param string $date
     * @return bool
     */
    private function _addDate( $date ) {
        if ( !isset ( $this->time[ $date ][ 'day' ] ) ) {
            $this->time[ $date ]['day'] = array(
                'is_day' => true,
                'label'  => date_i18n( 'D, M d', strtotime( $date ) ),
                'value'  => $date,
            );

            return true;
        }

        return false;
    }

    /**
     * Add time to Time Table (step 2)
     *
     * @param string $date
     * @param int $label_time
     * @param int $staff_id
     * @param int $time
     * @param boolean $booked
     */
    private function _addTime( $date, $label_time, $staff_id, $time, $booked = false ) {
        $this->time[ $date ][ $time ] = array(
            'is_day'     => false,
            'label'      => date_i18n( get_option('time_format'), $label_time ),
            'value'      => sprintf( '%s %s', $date, date( 'H:i:s', $time ) ),
            'staff_id'   => $staff_id,
            'date'       => $date,
            'booked'     => $booked,
        );
    }
}
