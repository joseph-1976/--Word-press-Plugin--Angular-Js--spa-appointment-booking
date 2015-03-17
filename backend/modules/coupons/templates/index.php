<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="ab-title"><?php _e('Coupons', 'ab') ?></div>
<div style="min-width: 800px;">
    <div class="ab-right-content" style="border: 0" id="ab_coupons_wrapper">
        <div class="no-result"<?php if (count($coupons_collection)) : ?> style="display: none"<?php endif; ?>><?php _e( 'No coupons found','ab' ) ?></div>
        <div class="list-wrapper">
            <div id="ab-coupons-list">
                <?php include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'list.php' ?>
            </div>
            <div class="list-actions">
                <a class="add-coupon btn btn-info" href="#"><?php _e('Add Coupon','ab') ?></a>
                <a class="delete btn btn-info" href="#"><?php _e('Delete','ab') ?></a>
            </div>
        </div>
    </div>
</div>