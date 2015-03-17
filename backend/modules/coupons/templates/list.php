<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php if ( count( $coupons_collection ) ) : ?>
    <table class="table table-striped" cellspacing="0" cellpadding="0" border="0" id="coupons_list">
        <thead>
            <tr>
                <th class="first"><?php echo _e( 'Code', 'ab' ) ?></th>
                <th width="100"><?php echo _e( 'Discount (%)', 'ab' ) ?></th>
                <th><?php echo _e( 'Used', 'ab' ) ?></th>
                <th class="last">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ( $coupons_collection as $i => $coupon ) {
                    $row_class  = 'coupon-row ';
                    $row_class .= $i % 2 ? 'even' : 'odd';
                    if ( 0 == $i ) {
                        $row_class .= ' first';
                    }
                    if ( ! isset( $coupons_collection[$i + 1] ) ) {
                        $row_class .= ' last';
                    }
                    include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'list_item.php';
                }
            ?>
        </tbody>
    </table>
<?php endif ?>