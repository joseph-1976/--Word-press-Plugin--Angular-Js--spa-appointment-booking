<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_Notifications
 */
class AB_Notifications extends AB_Entity {

    protected static $table_name = 'ab_notifications';

    protected static $schema = array(
        'id'        => array( 'format' => '%d' ),
        'slug'      => array( 'format' => '%s', 'default' => '' ),
        'active'    => array( 'format' => '%d', 'default' => 0 ),
        'copy'      => array( 'format' => '%d', 'default' => 0 ),
        'subject'   => array( 'format' => '%s', 'default' => '' ),
        'message'   => array( 'format' => '%s', 'default' => '' ),
    );

    public function getSubject() {
        return $this->get('subject');
    }

    /**
     * Return the message with replacements
     */
    public function getMessage() {

        $message = $this->get('message');
        $message = str_replace('[[COMPANY_NAME]]',      get_option( 'ab_settings_company_name' ), $message);
        $message = str_replace('[[COMPANY_LOGO]]',      '<img src="' . get_option( 'ab_settings_company_logo_url' ) . '" />', $message);
        $message = str_replace('[[COMPANY_ADDRESS]]',   nl2br( get_option( 'ab_settings_company_address' ) ), $message);
        $message = str_replace('[[COMPANY_PHONE]]',     get_option( 'ab_settings_company_phone' ), $message);
        $message = str_replace('[[COMPANY_WEBSITE]]',   get_option( 'ab_settings_company_website' ), $message);

        return $message;
    }
}
