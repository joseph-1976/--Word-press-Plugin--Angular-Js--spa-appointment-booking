<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AB_CustomerController extends AB_Controller {

    protected function getPermissions() {
        return array(
            'executeSaveCustomer' => 'user',
            'executeGetNgNewCustomerDialogTemplate' => 'user',
        );
    }

    public function index() {
        if ( !empty ( $_POST )){
            $this->importCustomers();
        }

        $this->enqueueStyles( array(
            'backend' => array(
                'css/ab_style.css',
                'bootstrap/css/bootstrap.min.css',
            )
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/angular-1.3.11.min.js',
                'js/angular-sanitize.min.js',
                'js/angular-ui-utils-0.2.1.min.js',
                'js/angular-ui-date-0.0.7.js',
                'js/ng-new_customer_dialog.js' => array( 'jquery', 'ab-angular-1.3.11.min.js' ),
            ),
            'module' => array(
                'js/ng-app.js' => array( 'jquery', 'ab-angular-1.3.11.min.js', 'ab-angular-ui-utils-0.2.1.min.js', 'ab-angular-ui-date-0.0.7.js' ),
            )
        ) );

        wp_localize_script( 'ab-ng-app.js', 'BooklyL10n', array(
            'are_you_sure'  => __( 'Are you sure?', 'ab' ),
            'module'        => 'customer',
        ));

        $this->render('index');
    }

    /**
     * Get list of customers.
     */
    public function executeGetCustomers() {
        $wpdb = $this->getWpdb();

        $response = array(
            'status' => 'ok',
            'data'   => array(
                'customers' => array(),
                'total'       => 0,
                'pages'       => 0,
                'active_page' => 0,
            )
        );

        $page   = intval( $this->getParameter( 'page' ) );
        $sort   = in_array( $this->getParameter( 'sort' ), array( 'name', 'phone', 'email', 'notes', 'last_appointment', 'total_appointments', 'payments' ) )
            ? $this->getParameter( 'sort' ) : 'name';
        $order  = in_array( $this->getParameter( 'order' ), array( 'asc', 'desc' ) ) ? $this->getParameter( 'order' ) : 'asc';
        $filter = $wpdb->_real_escape( $this->getParameter( 'filter' ) );

        $items_per_page = 20;
        $total = $wpdb->get_var( 'SELECT COUNT(*) FROM `ab_customer`' );
        $pages = ceil( $total / $items_per_page );
        if ( $page < 1 || $page > $pages ) {
            $page = 1;
        }

        if ( $total ) {
            $query = "SELECT `c`.*, MAX(`a`.`start_date`) AS `last_appointment`, COUNT(`a`.`id`) AS `total_appointments`,  COALESCE(SUM(`p`.`total`),0) AS `payments`
                        FROM `ab_customer` `c`
                        LEFT JOIN `ab_customer_appointment` `ca` ON `ca`.`customer_id` = `c`.`id`
                        LEFT JOIN `ab_appointment` `a` ON `a`.`id` = `ca`.`appointment_id`
                        LEFT JOIN `ab_payment` `p` ON `p`.`customer_appointment_id` = `ca`.`id`";
            // WHERE
            if ( $filter !== '' ) {
                $query .= " WHERE `c`.`name` LIKE '%{$filter}%' OR `c`.`phone` LIKE '%{$filter}%' OR `c`.`email` LIKE '%{$filter}%'";
            }
            // GROUP BY
            $query .= ' GROUP BY `c`.`id`';
            // ORDER BY
            $query .= " ORDER BY {$sort} {$order}";
            // LIMIT
            $start = ( $page - 1) * $items_per_page;
            $query .= " LIMIT {$start}, {$items_per_page}";

            $data = $wpdb->get_results( $query );
            array_walk( $data, function ( $row ) {
                $row->last_appointment = AB_CommonUtils::getFormattedDateTime( $row->last_appointment );
                $row->payments = AB_CommonUtils::formatPrice( $row->payments );
            } );

            // Populate response.
            $response[ 'data' ][ 'customers' ]   = $data;
            $response[ 'data' ][ 'total' ]       = $total;
            $response[ 'data' ][ 'pages' ]       = $pages;
            $response[ 'data' ][ 'active_page' ] = $page;
        }

        echo json_encode( $response );
        exit ( 0 );
    }

    /**
     * Create or edit a customer.
     */
    public function executeSaveCustomer() {
        $response = array();
        $form = new AB_CustomerForm();

        do {
            if ( $this->getParameter( 'name' ) !== '' ) {
                $form->bind( $this->getPostParameters() );
                /** @var AB_Customer $customer */
                $customer = $form->save();
                if ( $customer ) {
                    $response[ 'status' ]   = 'ok';
                    $response[ 'customer' ] = array(
                        'id'      => $customer->id,
                        'name'    => $customer->name,
                        'phone'   => $customer->phone,
                        'email'   => $customer->email,
                        'notes'   => $customer->notes,


                        'jsonString' => json_encode( array(
                            'name'  => $customer->name,
                            'phone' => $customer->phone,
                            'email' => $customer->email,
                            'notes' => $customer->notes


                        ) )
                    );
                    break;
                }
            }
            $response[ 'status' ] = 'error';
            $response[ 'errors' ] = array( 'name' => array( 'required' ) );
        } while ( 0 );

        echo json_encode( $response );
        exit ( 0 );
    }

    /**
     * Import customers from CSV.
     */
    private function importCustomers() {
        @ini_set( 'auto_detect_line_endings', true );

        $csv_mime_types = array(
            'text/csv',
            'application/csv',
            'text/comma-separated-values',
            'application/excel',
            'application/vnd.ms-excel',
            'application/vnd.msexcel'
        );

        if ( in_array( $_FILES[ 'import_customers_file' ][ 'type' ], $csv_mime_types ) ) {
            $file = fopen ( $_FILES[ 'import_customers_file' ][ 'tmp_name' ], 'r' );
            while ( $line = fgetcsv( $file, null, $this->getParameter( 'import_customers_delimiter' ) ) ) {
                if ( !empty ( $line[ 0 ] ) ) {
                    $customer = new AB_Customer();
                    $customer->set( 'name', $line[ 0 ] );
                    if ( isset ( $line[ 1 ] ) ) {
                        $customer->set( 'phone', $line[ 1 ] );
                    }
                    if ( isset ( $line[ 2 ] ) ) {
                        $customer->set( 'email', $line[ 2 ] );
                    }
                    $customer->save();
                }
            }
        }
    }

    /**
     * Get angulars template for new customer dialog.
     */
    public function executeGetNgNewCustomerDialogTemplate() {
        $this->render( 'ng-new_customer_dialog', array(
            'custom_fields' => json_decode( get_option( 'ab_custom_fields' ) ),
            'module'        => $this->getParameter('module')
        ) );
        exit ( 0 );
    }

    /**
     * Delete a customer.
     */
    public function executeDeleteCustomer() {
        $this->getWpdb()->delete('ab_customer', array( 'id' => $this->getParameter( 'id' ) ), array( '%d' ) );
    }

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     */
    protected function registerWpActions( $prefix = '' ) {
        parent::registerWpActions( 'wp_ajax_ab_' );
    }
}