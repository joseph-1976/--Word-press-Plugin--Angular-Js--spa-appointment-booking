<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<form method="post" action="<?php echo add_query_arg( 'type', '_general' ) ?>" enctype="multipart/form-data" class="ab-settings-form">

    <?php if (isset($message_g)) : ?>
        <div id="message" style="margin: 0px!important;" class="updated below-h2">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <p><?php echo $message_g ?></p>
        </div>
    <?php endif ?>

    <table class="form-horizontal">
        <tr>
            <td><?php _e('Time slot length','ab') ?></td>
            <td class="ab-valign-top">
                <select name="ab_settings_time_slot_length">
                    <?php
                    foreach ( array( 5, 10, 12, 15, 20, 30, 60 ) as $duration ) {
                        $duration_output = AB_Service::durationToString( $duration * 60 );
                        ?>
                        <option value="<?php echo $duration ?>" <?php selected( get_option( 'ab_settings_time_slot_length' ), $duration ); ?>>
                            <?php echo $duration_output ?>
                        </option>
                    <?php } ?>
                </select>
            </td>
            <td class="ab-valign-top">
                <img
                    src="<?php echo esc_attr( plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'Select the time interval that will be used in frontend and backend, e.g. in calendar, second step of the booking process, while indicating the working hours, etc.', 'ab' ) ) ?>"
                    />
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( 'Minimum time requirement prior to booking', 'ab' ) ?></label>
            </td>
            <td class="ab-valign-top">
                <select name="ab_settings_minimum_time_prior_booking">
                    <option value="0" <?php selected( get_option( 'ab_settings_minimum_time_prior_booking' ), 0 ) ?>><?php _e( 'Disabled', 'ab' ) ?></option>
                    <?php foreach ( array_merge(range(1, 12), array(24, 48)) as $hour): ?>
                        <option value="<?php echo $hour ?>" <?php selected( get_option( 'ab_settings_minimum_time_prior_booking' ), $hour ) ?>><?php echo AB_Service::durationToString( $hour * 3600 ) ?></option>
                    <?php endforeach ?>
                </select>
            </td>
            <td class="ab-valign-top">
                <img
                    src="<?php echo esc_attr( plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'Set a minimum amount of time before the chosen appointment (for example, require the customer to book at least 1 hour before the appointment time).', 'ab' ) ) ?>"
                    />
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( 'Number of days available for booking', 'ab' ) ?></label>
            </td>
            <td class="ab-valign-top">
                <input type="number" name="ab_settings_maximum_available_days_for_booking" min="1" max="365" value="<?php echo esc_attr( get_option( 'ab_settings_maximum_available_days_for_booking', 365 ) ) ?>" />
            </td>
            <td class="ab-valign-top">
                <img
                    src="<?php echo esc_attr( plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'Specify the number of days that should be available for booking at step 2 starting from the current day.', 'ab' ) ) ?>"
                    />
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( 'Display available time slots in client\'s time zone', 'ab' ) ?></label>
            </td>
            <td class="ab-valign-top">
                <select name="ab_settings_use_client_time_zone">
                    <?php foreach ( array( __( 'Disabled', 'ab' ) => '0', __( 'Enabled', 'ab' ) => '1' ) as $text => $mode ): ?>
                        <option value="<?php echo $mode ?>" <?php selected( get_option( 'ab_settings_use_client_time_zone' ), $mode ) ?> ><?php echo $text ?></option>
                    <?php endforeach ?>
                </select>
            </td>
            <td class="ab-valign-top">
                <img
                    src="<?php echo esc_attr( plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'The value is taken from client’s browser.', 'ab' ) ) ?>"
                    />
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( 'Cancel appointment page URL', 'ab' ) ?></label>
            </td>
            <td class="ab-valign-top">
                <input type="text" name="ab_settings_cancel_page_url" value="<?php echo esc_attr( get_option( 'ab_settings_cancel_page_url' ) ) ?>" placeholder="<?php echo esc_attr( __( 'Enter a URL', 'ab' ) ) ?>" />
            </td>
            <td class="ab-valign-top">
                <img
                    src="<?php echo esc_attr( plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'Insert a URL of a page that is shown to clients after they have cancelled their booking.', 'ab' ) ) ?>"
                    />
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( 'Final step URL', 'ab' ) ?></label>
            </td>
            <td class="ab-valign-top">
                <select id="ab_settings_final_step_url_mode">
                    <?php foreach ( array( __( 'Disabled', 'ab' ) => 0, __( 'Enabled', 'ab' ) => 1 ) as $text => $mode ): ?>
                        <option value="<?php echo $mode ?>" <?php selected( get_option( 'ab_settings_final_step_url' ), $mode ) ?> ><?php echo $text ?></option>
                    <?php endforeach ?>
                </select>
                <br>
                <input style="margin-top: 5px; <?php echo get_option( 'ab_settings_final_step_url' ) == ''? 'display: none':''; ?>" type="text" name="ab_settings_final_step_url" value="<?php echo esc_attr( get_option( 'ab_settings_final_step_url' ) ) ?>" placeholder="<?php echo esc_attr( __( 'Enter a URL', 'ab' ) ) ?>" />
            </td>
            <td class="ab-valign-top">
                <img
                    src="<?php echo esc_attr( plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'Set a URL of a page that the user will be forwarded to after successful booking. If disabled then the default step 5 is displayed.', 'ab' ) ) ?>"
                    />
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" value="<?php echo esc_attr( __( 'Save', 'ab' ) ) ?>" class="btn btn-info ab-update-button" />
                <button class="ab-reset-form" type="reset"><?php _e( ' Reset ', 'ab' ) ?></button>
            </td>
        </tr>
    </table>
</form>

