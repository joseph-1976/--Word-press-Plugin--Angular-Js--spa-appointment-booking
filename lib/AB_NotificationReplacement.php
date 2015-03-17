<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
class AB_NotificationReplacement {

    /**
     * Source data for all replacements.
     * @var array
     */
    private $data = array(
        'appointment_datetime' => '',
        'appointment_token'    => '',
        'client_email'         => '',
        'client_name'          => '',
        'client_phone'         => '',
        'custom_fields'        => '',
        'service_name'         => '',
        'service_price'        => '',
        'staff_email'          => '',
        'staff_name'           => '',
        'staff_phone'          => '',
        'staff_photo'          => '',
        'category_name'        => '',
        'next_day_agenda'      => '',
    );

    /**
     * Set data parameter.
     *
     * @param string $name
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function set( $name, $value ) {
        if ( !array_key_exists( $name, $this->data ) ) {
            throw new InvalidArgumentException( sprintf( 'Trying to set unknown replacement "%s" for email notifications', $name ) );
        }

        $this->data[ $name ] = $value;
    }

    /**
     * Get data parameter.
     *
     * @param string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get( $name ) {
        if ( !array_key_exists( $name, $this->data ) ) {
            throw new InvalidArgumentException( sprintf( 'Trying to get unknown replacement "%s" for email notifications', $name ) );
        }

        return $this->data[ $name ];
    }

    /**
     * Do replacements.
     *
     * @param string $text
     * @return string
     */
    public function replace( $text ) {
        // Company logo as <img> tag.
        $company_logo = '';
        if ( get_option( 'ab_settings_company_logo_url' ) != '' ) {
            $company_logo = sprintf(
                '<img src="%s" alt="%s" />',
                esc_attr( get_option( 'ab_settings_company_logo_url' ) ),
                esc_attr( get_option( 'ab_settings_company_name' ) )
            );
        }

        // Staff photo as <img> tag.
        $staff_photo = '';
        if ( $this->data[ 'staff_photo' ] != '' ) {
            $staff_photo = sprintf(
                '<img src="%s" alt="%s" />',
                esc_attr( $this->get( 'staff_photo' ) ),
                esc_attr( $this->get( 'staff_name' ) )
            );
        }

        // Cancel appointment URL and <a> tag.
        $cancel_appointment_url = admin_url( 'admin-ajax.php' ) . '?action=ab_cancel_appointment&token=' . $this->get( 'appointment_token' );
        $cancel_appointment = sprintf(
            '<a href="%1$s">%1$s</a>',
            $cancel_appointment_url
        );

        // Replacements.
        $replacement = array(
            '[[APPOINTMENT_TIME]]'       => date_i18n( get_option( 'time_format' ), strtotime( $this->get( 'appointment_datetime' ) ) ),
            '[[APPOINTMENT_DATE]]'       => date_i18n( get_option( 'date_format' ), strtotime( $this->get( 'appointment_datetime' ) ) ),
            '[[CUSTOM_FIELDS]]'          => $this->get( 'custom_fields' ),
            '[[CLIENT_NAME]]'            => $this->get( 'client_name' ),
            '[[CLIENT_PHONE]]'           => $this->get( 'client_phone' ),
            '[[CLIENT_EMAIL]]'           => $this->get( 'client_email' ),
            '[[SERVICE_NAME]]'           => $this->get( 'service_name' ),
            '[[SERVICE_PRICE]]'          => AB_CommonUtils::formatPrice( $this->get( 'service_price' ) ),
            '[[STAFF_EMAIL]]'            => $this->get( 'staff_email' ),
            '[[STAFF_NAME]]'             => $this->get( 'staff_name' ),
            '[[STAFF_PHONE]]'            => $this->get( 'staff_phone' ),
            '[[STAFF_PHOTO]]'            => $staff_photo,
            '[[CANCEL_APPOINTMENT]]'     => $cancel_appointment,
            '[[CANCEL_APPOINTMENT_URL]]' => $cancel_appointment_url,
            '[[CATEGORY_NAME]]'          => $this->get( 'category_name' ),
            '[[COMPANY_ADDRESS]]'        => nl2br( get_option( 'ab_settings_company_address' ) ),
            '[[COMPANY_LOGO]]'           => $company_logo,
            '[[COMPANY_NAME]]'           => get_option( 'ab_settings_company_name' ),
            '[[COMPANY_PHONE]]'          => get_option( 'ab_settings_company_phone' ),
            '[[COMPANY_WEBSITE]]'        => get_option( 'ab_settings_company_website' ),
            '[[NEXT_DAY_AGENDA]]'        => $this->get( 'next_day_agenda' ),
            '[[TOMORROW_DATE]]'          => date_i18n( get_option( 'date_format' ), strtotime( $this->get( 'appointment_datetime' ) ) ),
        );

        return str_replace( array_keys( $replacement ), array_values( $replacement ), $text );
    }
}
