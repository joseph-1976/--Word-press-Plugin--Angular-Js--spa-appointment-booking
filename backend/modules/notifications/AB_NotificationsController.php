<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AB_NotificationsController extends AB_Controller {

    public function index() {
        $this->enqueueStyles( array(
            'backend' => array(
                'css/ab_style.css',
                'bootstrap/css/bootstrap.min.css',
            )
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
            )
        ) );

        $this->form = new AB_NotificationsForm();

        // save action
        if ( !empty ( $_POST ) ) {
            $this->form->bind( $this->getPostParameters(), $_FILES );
            $this->form->save();
            $this->message = __( 'Notification settings were updated successfully.', 'ab' );
            // sender name
            if ( $this->hasParameter( 'sender_name' ) ) {
                update_option( 'ab_settings_sender_name', esc_html( $this->getParameter( 'sender_name' ) ) );
            }
            // sender email
            if ( $this->hasParameter( 'sender_email' ) ) {
                update_option( 'ab_settings_sender_email', esc_html( $this->getParameter( 'sender_email' ) ) );
            }
        }

        $this->render( 'index' );
    }

    // Protected methods.

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     */
    protected function registerWpActions( $prefix = '' ) {
        parent::registerWpActions( 'wp_ajax_ab_' );
    }
}