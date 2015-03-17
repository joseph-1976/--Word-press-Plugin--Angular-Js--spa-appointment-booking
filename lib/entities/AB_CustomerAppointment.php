<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_CustomerAppointment
 */
class AB_CustomerAppointment extends AB_Entity {

    protected static $table_name = 'ab_customer_appointment';

    protected static $schema = array(
        'id'             => array( 'format' => '%d' ),
        'customer_id'    => array( 'format' => '%d' ),
        'appointment_id' => array( 'format' => '%d' ),
        'token'          => array( 'format' => '%s' ),
        'custom_fields'  => array( 'format' => '%s' ),
    );

    /** @var AB_Customer */
    public $customer = null;

    /**
     * Save entity to database.
     * Generate token before saving.
     *
     * @return int|false
     */
    public function save() {
        // Generate new token if it is not set.
        if ( $this->get( 'token' ) == '' ) {
            $test = new self();
            do {
                $token = md5( uniqid( time(), true ) );
            }
            while ( $test->loadBy( array( 'token' => $token ) ) === true );

            $this->set( 'token', $token );
        }

        return parent::save();
    }

    /**
     * Get array of custom fields with labels and values.
     *
     * @return array
     */
    public function getCustomFields() {
        $result = array();
        if ( $this->get( 'custom_fields' ) != '' ) {
            $custom_fields = array();
            foreach ( json_decode( get_option( 'ab_custom_fields' ) ) as $field ) {
                $custom_fields[ $field->id ] = $field;
            }
            $data = json_decode( $this->get( 'custom_fields' ) );
            foreach ($data as $value) {
                if ( array_key_exists( $value->id, $custom_fields ) ) {
                    $result[] = array(
                        'id'    => $value->id,
                        'label' => $custom_fields[ $value->id ]->label,
                        'value' => is_array( $value->value ) ? implode( ', ', $value->value ) : $value->value
                    );
                }
            }
        }

        return $result;
    }
}