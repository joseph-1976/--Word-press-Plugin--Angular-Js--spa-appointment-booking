<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_Customer
 */
class AB_Customer extends AB_Entity {

    protected static $table_name = 'ab_customer';

    protected static $schema = array(
        'id'      => array( 'format' => '%d' ),
        'name'    => array( 'format' => '%s', 'default' => '' ),
        'phone'   => array( 'format' => '%s', 'default' => '' ),
        'email'   => array( 'format' => '%s', 'default' => '' ),
        'notes'   => array( 'format' => '%s', 'default' => '' ),

    );
}