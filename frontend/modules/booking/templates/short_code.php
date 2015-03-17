<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php include '_css.php' ?>
<div id="ab-booking-form-<?php echo $form_id ?>" class="ab-booking-form"></div>
<script type="text/javascript">
    jQuery(function ($) {
        window.bookly({
            is_finished     : <?php echo (int)$booking_finished  ?>,
            is_cancelled    : <?php echo (int)$booking_cancelled  ?>,
            ajaxurl         : <?php echo json_encode( admin_url('admin-ajax.php') . ( isset( $_REQUEST[ 'lang' ] ) ? '?lang=' . $_REQUEST[ 'lang' ] : '' ) ) ?>,
            attributes      : <?php echo $attributes ?>,
            form_id         : <?php echo json_encode( $form_id ) ?>,
            start_of_week   : <?php echo intval( get_option( 'start_of_week' ) ) ?>,
            date_min        : <?php echo json_encode( AB_BookingConfiguration::getDateMin() ) ?>,
            final_step_url  : <?php echo json_encode( get_option('ab_settings_final_step_url') ) ?>,
            custom_fields   : <?php echo get_option( 'ab_custom_fields' ) ?>,
            day_one_column  : <?php echo intval( get_option( 'ab_appearance_show_day_one_column' ) ) ?>
        });
    });
</script>