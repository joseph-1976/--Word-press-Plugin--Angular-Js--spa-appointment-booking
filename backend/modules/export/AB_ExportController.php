<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_ExportController
 */
class AB_ExportController extends AB_Controller {

    public function index() {
        /** @var WP_Locale $wp_locale */
        global $wp_locale;

        $this->enqueueStyles( array(
            'backend' => array(
                'css/ab_style.css',
                'bootstrap/css/bootstrap.min.css',
                'css/daterangepicker.css',
                'css/bootstrap-select.min.css',
            )
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/date.js' =>  array( 'jquery' ),
                'js/daterangepicker.js' => array( 'jquery' ),
                'js/bootstrap-select.min.js',
            )
        ) );

        wp_localize_script( 'ab-daterangepicker.js', 'BooklyL10n', array(
            'today'        => __( 'Today', 'ab' ),
            'yesterday'    => __( 'Yesterday', 'ab' ),
            'last_7'       => __( 'Last 7 Days', 'ab' ),
            'last_30'      => __( 'Last 30 Days', 'ab' ),
            'this_month'   => __( 'This Month', 'ab' ),
            'last_month'   => __( 'Last Month', 'ab' ),
            'custom_range' => __( 'Custom Range', 'ab' ),
            'apply'        => __( 'Apply', 'ab' ),
            'clear'        => __( 'Clear', 'ab' ),
            'to'           => __( 'To', 'ab' ),
            'from'         => __( 'From', 'ab' ),
            'months'       => array_values( $wp_locale->month ),
            'days'         => array_values( $wp_locale->weekday_abbrev )
        ));
        $this->render( 'index' );
    }

    /**
     * Export Appointment to CSV
     */
    public function executeExportToCSV() {
        $start_date = new DateTime( $this->getParameter( 'date_start' ) );
        $start_date = $start_date->format( 'Y-m-d H:i:s' );
        $end_date   = new DateTime( $this->getParameter( 'date_end' ) );
        $end_date   = $end_date->modify( '+1 day' )->format( 'Y-m-d H:i:s' );
        $delimiter  = $this->getParameter( 'delimiter', ',' );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=Appointments.csv' );

        $header = array(
            __( 'Staff Member', 'ab' ),
            __( 'Service', 'ab' ),
            __( 'Booking Time', 'ab' ),
            __( 'Duration', 'ab' ),
            __( 'Price', 'ab' ),
            __( 'Customer', 'ab' ),
            __( 'Phone', 'ab' ),
            __( 'Email', 'ab' ),
        );

        $custom_fields = array();
        $fields_data = json_decode( get_option( 'ab_custom_fields' ) );
        foreach ($fields_data as $field_data) {
            $custom_fields[$field_data->id] = '';
            $header[] = $field_data->label;
        }

        $output = fopen( 'php://output', 'w' );
        fwrite($output, pack("CCC",0xef,0xbb,0xbf));
        fputcsv( $output, $header, $delimiter );

        $appointments = $this->getWpdb()->get_results( "
        SELECT st.full_name AS staff_name,
               s.title AS service_title,
               a.start_date AS start_date,
               s.duration AS service_duration,
               c.name AS customer_name,
               c.phone AS customer_phone,
               c.email AS customer_email,
               p.total AS customer_payed,
               ss.price AS staff_price,
               ca.id as customer_appointment_id
        FROM ab_customer_appointment ca
        LEFT JOIN ab_appointment a ON a.id = ca.appointment_id
        LEFT JOIN ab_service s ON s.id = a.service_id
        LEFT JOIN ab_staff st ON st.id = a.staff_id
        LEFT JOIN ab_customer c ON c.id = ca.customer_id
        LEFT JOIN ab_payment p ON p.customer_appointment_id = ca.id
        LEFT JOIN ab_staff_service ss ON ss.staff_id = st.id AND ss.service_id = s.id
        WHERE a.start_date between '{$start_date}' AND '{$end_date}'
        ORDER BY a.start_date DESC
        ", ARRAY_A );

        foreach( $appointments as $appointment ) {
            $row_data = array(
                $appointment['staff_name'],
                $appointment['service_title'],
                $appointment['start_date'],
                AB_Service::durationToString( $appointment['service_duration'] ),
                AB_CommonUtils::formatPrice( $appointment['customer_payed'] === null ? $appointment['staff_price'] : $appointment['customer_payed'] ),
                $appointment['customer_name'],
                $appointment['customer_phone'],
                $appointment['customer_email'],
            );

            $customer_appointment = new AB_CustomerAppointment();
            $customer_appointment->load($appointment['customer_appointment_id']);
            foreach ($customer_appointment->getCustomFields() as $custom_field) {
                $custom_fields[$custom_field['id']] = $custom_field['value'];
            }

            fputcsv( $output, array_merge( $row_data, $custom_fields ), $delimiter );

            $custom_fields = array_map(function() { return ''; }, $custom_fields);
        }
        fclose( $output );

        exit();
    }

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     */
    protected function registerWpActions( $prefix = '' ) {
        parent::registerWpActions( 'wp_ajax_ab_' );
    }
}