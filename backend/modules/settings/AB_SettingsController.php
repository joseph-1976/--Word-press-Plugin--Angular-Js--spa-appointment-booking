<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_SettingsController
 */
class AB_SettingsController extends AB_Controller {

    public function index() {
        /** @var WP_Locale $wp_locale */
        global $wp_locale;

        $this->enqueueStyles( array(
            'backend' => array(
                'css/ab_style.css',
                'bootstrap/css/bootstrap.min.css',
                'css/jCal.css',
            )
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/jCal.js' => array( 'jquery' ),
            ),
            'module' => array(
                'js/settings.js' => array( 'jquery' ),
            ),
        ) );

        wp_localize_script( 'ab-jCal.js', 'BooklyL10n',  array(
            'we_are_not_working' => __( 'We are not working on this day', 'ab' ),
            'repeat'             => __( 'Repeat every year', 'ab' ),
            'months'             => array_values( $wp_locale->month ),
            'days'               => array_values( $wp_locale->weekday_abbrev )
        ) );

        // save the settings
        if ( !empty ( $_POST ) ) {
            // Payments form
            if ( $this->getParameter( 'type' ) == '_payments' ) {
                $this->form = new AB_PaymentsForm();
                $this->message_p = __( 'Settings saved.', 'ab' );

                // Business hours form
            }
            else if ( $this->getParameter( 'type' ) == '_hours' ) {
                $this->form = new AB_BusinessHoursForm();
                $this->message_h = __( 'Settings saved.', 'ab' );
            }
            // Purchase Code Form
            else if ( $this->getParameter( 'type' ) == '_purchase_code' ) {
                update_option( 'ab_envato_purchase_code',  esc_html( $this->getParameter( 'ab_envato_purchase_code' ) ) );
                $this->message_pc = __( 'Settings saved.', 'ab' );
            }
            else if ( $this->getParameter( 'type' ) == '_general' ) {
                $ab_settings_time_slot_length = $this->getParameter( 'ab_settings_time_slot_length' );
                if ( in_array( $ab_settings_time_slot_length, array( 5, 10, 12, 15, 20, 30, 60 ) ) ) {
                    update_option( 'ab_settings_time_slot_length',  $ab_settings_time_slot_length );
                }
                update_option( 'ab_settings_minimum_time_prior_booking', (int)$this->getParameter( 'ab_settings_minimum_time_prior_booking' ) );
                update_option( 'ab_settings_maximum_available_days_for_booking', (int)$this->getParameter( 'ab_settings_maximum_available_days_for_booking' ) );
                update_option( 'ab_settings_use_client_time_zone', (int)$this->getParameter( 'ab_settings_use_client_time_zone' ) );
                update_option( 'ab_settings_cancel_page_url', $this->getParameter( 'ab_settings_cancel_page_url' ) );
                update_option( 'ab_settings_final_step_url', $this->getParameter( 'ab_settings_final_step_url' ) );
                $this->message_g = __( 'Settings saved.', 'ab' );
            }
            // Google calendar form
            else if ( $this->getParameter( 'type' ) == '_google_calendar' ) {
                update_option( 'ab_settings_google_client_id', $this->getParameter( 'ab_settings_google_client_id' ) );
                update_option( 'ab_settings_google_client_secret', $this->getParameter( 'ab_settings_google_client_secret' ) );
                update_option( 'ab_settings_google_two_way_sync', $this->getParameter( 'ab_settings_google_two_way_sync' ) );
                update_option( 'ab_settings_google_limit_events', $this->getParameter( 'ab_settings_google_limit_events' ) );
                $this->message_gc = __( 'Settings saved.', 'ab' );
            }
            // Holidays form
            else if ( $this->getParameter( 'type' ) == '_holidays' ) {
                // Company form
            }
            else {
                $this->form = new AB_CompanyForm();
                $this->message_c = __( 'Settings saved.', 'ab' );
            }
            if ( $this->getParameter( 'type' ) != '_purchase_code' && $this->getParameter( 'type' ) != '_holidays'
                && $this->getParameter( 'type' ) != '_import' && $this->getParameter( 'type' ) != '_general' && $this->getParameter( 'type' ) != '_google_calendar' ) {
                $this->form->bind( $this->getPostParameters(), $_FILES );
                $this->form->save();
            }
        }

        // get holidays
        $this->holidays = $this->getHolidays();

        $this->render( 'index' );
    } // index

    /**
     * Ajax request for Holidays calendar
     */
    public function executeSettingsHoliday() {
        $id       = $this->getParameter( 'id', false );
        $holiday  = $this->getParameter( 'holiday' ) == 'true';
        $repeat   = $this->getParameter( 'repeat' ) == 'true';
        $day      = $this->getParameter( 'day', false );

        // update or delete the event
        if ( $id ) {
            if ( $holiday ) {
                $this->getWpdb()->update( 'ab_holiday', array('repeat_event' => intval( $repeat ) ), array( 'id' => $id ), array( '%d' ) );
                $this->getWpdb()->update( 'ab_holiday', array( 'repeat_event' => intval( $repeat ) ), array( 'parent_id' => $id ), array( '%d' )  );
            } else {
                $this->getWpdb()->delete( 'ab_holiday', array( 'id' => $id ), array( '%d' ) );
                $this->getWpdb()->delete( 'ab_holiday', array( 'parent_id' => $id ), array( '%d' ) );
            }
            // add the new event
        } elseif ( $holiday && $day ) {
            $day = new DateTime( $day );
            $this->getWpdb()->insert( 'ab_holiday', array( 'holiday' => $day->format( 'Y-m-d H:i:s' ), 'repeat_event' => intval( $repeat ) ), array( '%s', '%d' ) );
            $parent_id = $this->getWpdb()->insert_id;
            $staff = $this->getWpdb()->get_results( 'SELECT id FROM ab_staff' );
            foreach ( $staff as $employee ) {
                $this->getWpdb()->insert( 'ab_holiday',
                    array(
                        'holiday' => date( 'Y-m-d H:i:s', $day->format( 'U' ) ),
                        'repeat_event' => intval( $repeat ),
                        'staff_id' => $employee->id,
                        'parent_id' => $parent_id
                    ),
                    array( '%s', '%d', '%d' )
                );
            }
        }

        // and return refreshed events
        echo $this->getHolidays();
        exit;
    }

    /**
     * @return mixed|string|void
     */
    protected function getHolidays() {
        $collection = $this->getWpdb()->get_results( "SELECT * FROM ab_holiday WHERE staff_id IS NULL" );
        $holidays = array();
        if ( count( $collection ) ) {
            foreach ( $collection as $holiday ) {
                $holidays[ $holiday->id ] = array(
                    'm'     => intval( date( 'm', strtotime( $holiday->holiday ) ) ),
                    'd'     => intval( date( 'd', strtotime( $holiday->holiday ) ) ),
                    'title' => $holiday->title,
                );
                // if not repeated holiday, add the year
                if ( ! $holiday->repeat_event ) {
                    $holidays[ $holiday->id ][ 'y' ] = intval( date( 'Y', strtotime( $holiday->holiday ) ) );
                }
            }
        }

        return json_encode( (object) $holidays );
    }

    /**
     * Show admin notice about purchase code and license.
     */
    public function showAdminNotice() {
        global $current_user;

        if ( !get_user_meta( $current_user->ID, 'ab_dismiss_admin_notice', true ) &&
            get_option( 'ab_envato_purchase_code' ) == '' &&
            time() > get_option( 'ab_installation_time' ) + 7*24*60*60
        ) {
            $this->render( 'admin_notice' );
        }
    }

    /**
     * Ajax request to dismiss admin notice for current user.
     */
    public function executeDismissAdminNotice() {
        global $current_user;

        update_user_meta( $current_user->ID, 'ab_dismiss_admin_notice', 1 );
    }

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     */
    protected function registerWpActions( $prefix = '' ) {
        parent::registerWpActions( 'wp_ajax_ab_' );
    }
}