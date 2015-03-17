<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_Coupon
 */
class AB_Coupon extends AB_Entity {

    protected static $table_name = 'ab_coupons';

    protected static $schema = array(
        'id'          => array( 'format' => '%d' ),
        'code'        => array( 'format' => '%s', 'default' => '' ),
        'discount'    => array( 'format' => '%d', 'default' => 0 ),
        'used'        => array( 'format' => '%d', 'default' => 0 ),
    );

    /**
     * Apply coupon.
     *
     * @param $price
     * @return float
     */
    public function apply( $price ) {
        return round( $price * ( 100 - $this->get( 'discount' ) ) / 100, 2 );
    }
}