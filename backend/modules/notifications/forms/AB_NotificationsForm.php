<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AB_NotificationsForm extends AB_Form {

    public $slugs = array(
        'client_info',
        'provider_info',
        'evening_next_day',
        'evening_after',
        'event_next_day'
    );

    public function __construct() {
        /*
         * make Visual Mode as default (instead of Text Mode)
         * allowed: tinymce - Visual Mode, html - Text Mode, test - no one Mode selected
         */
        add_filter( 'wp_default_editor', create_function( '', 'return \'tinymce\';' ) );

        $this->setFields( array(
            'active',
            'subject',
            'message',
            'copy',
        ) );

        $this->load();
    }

    public function bind( array $_post = array(), array $files = array() ) {
        foreach ( $this->slugs as $slug ) {
            foreach ( $this->fields as $field ) {
                if ( isset($_post[ $slug ][ $field ] ) ) {
                    $this->data[ $slug ][ $field ] = $_post[ $slug ][ $field ];
                }
            }
        }
    }

    /**
     * @return bool|void
     */
    public function save() {
        foreach ( $this->slugs as $slug ) {
            if ( $object = new AB_Notifications() ) {

                if ( ! $object->loadBy( array( 'slug' => $slug ) ) ) {
                    $this->data[ $slug ][ 'slug' ] = $slug;
                }

                $object->setData( $this->data[ $slug ] );
                $object->save();
            }
        }
    }

    public function load() {
        foreach ( $this->slugs as $slug) {
            if ( $object = new AB_Notifications() and $object->loadBy( array( 'slug' => $slug ) ) ) {
                $this->data[ $slug ][ 'active' ]    = $object->get( 'active' );
                $this->data[ $slug ][ 'subject' ]   = $object->get( 'subject' );
                $this->data[ $slug ][ 'message' ]   = $object->get( 'message' );
                $this->data[ $slug ][ 'name' ]      = $this->getNotificationName( $slug );
                if ( 'provider_info' == $slug ) {
                    $this->data[ $slug ][ 'copy' ]  = $object->get( 'copy' );
                }
            }
        }
    }

    /**
     * @param $slug
     * @return mixed
     */
    public function getNotificationName ( $slug ) {
        $notifications_name = array(
            'client_info'      => __( 'Notification to Customer about Appointment details', 'ab' ),
            'provider_info'    => __( 'Notification to Staff Member about Appointment details', 'ab' ),
            'evening_next_day' => __( 'Evening reminder to Customer about next day Appointment', 'ab' ),
            'evening_after'    => __( 'Follow-up message on the day after Appointment', 'ab' ),
            'event_next_day'   => __( 'Evening notification with the next day agenda to Staff Member', 'ab' )
        );

        return $notifications_name[ $slug ];
    }

    /**
     * Render the "active" form
     */
    public function renderActive( $slug ) {
        $id         = $slug . '_active';
        $name       = $slug . '[active]';
        $checked    = (isset($this->data[$slug]['active']) and intval($this->data[$slug]['active'])) ? "checked='checked'" : '';
        $title      = isset($this->data[$slug]['name']) ? $this->data[$slug]['name'] : '';

        return "<legend id='legend_{$slug}_active'>
            <input name='{$name}' value=0 type=hidden />
            <input id='{$id}' name='{$name}' value=1 type=checkbox {$checked} />
            <label for='{$id}'> {$title}</label>
        </legend>"
        ;
    }

    /**
     * @param $slug
     * @return string
     */
    public function renderSubject( $slug ) {
        $id     = $slug . '_subject';
        $name   = $slug . '[subject]';
        $value  = isset($this->data[$slug]['subject']) ? $this->data[$slug]['subject'] : '';

        return "<label class='ab-form-label'>" . __( 'Subject','ab') . "</label><input type='text' size='70' id='{$id}' name='{$name}' value='{$value}'/>";
    }

    /**
     * @param $slug
     * @return string
     */
    public function renderMessage( $slug ) {
        $id     = $slug . '_message';
        $name   = $slug . '[message]';
        $value  = isset($this->data[$slug]['message']) ? $this->data[$slug]['message'] : '';

        $settings = array(
            'textarea_name' => $name,
            'media_buttons' => false,
            'tinymce' => array(
                'theme_advanced_buttons1' => 'formatselect,|,bold,italic,underline,|,' .
                    'bullist,blockquote,|,justifyleft,justifycenter' .
                    ',justifyright,justifyfull,|,link,unlink,|' .
                    ',spellchecker,wp_fullscreen,wp_adv'
            )
        );

        wp_editor( $value, $id, $settings );
    }

    /**
     * Render the "copy" form
     */
    public function renderCopy( $slug ) {
        $id         = $slug . '_copy';
        $name       = $slug . '[copy]';
        $checked    = (isset($this->data[$slug]['copy']) and intval($this->data[$slug]['copy'])) ? "checked='checked'" : '';
        $title      = __('Send copy to administrators', 'ab');

        return "
        <div class='ab-form-row'>
            <label class='ab-form-label'></label>
            <div class='left'>
                <legend>
                    <input name='{$name}' type=hidden value=0 />
                    <input id='{$id}' name='{$name}' type=checkbox value=1 {$checked} />
                    <label for='{$id}'> {$title}</label>
                </legend>
            </div>
        </div>
        ";
    }
}
