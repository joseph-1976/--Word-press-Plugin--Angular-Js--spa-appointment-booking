<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @var AB_UserBookingData $userData
 * @var string $info_text
 */

// Show Progress Tracker if enabled in settings
if ( get_option( 'ab_appearance_show_progress_tracker' ) == 1 ) {
    echo $progress_tracker;
}
?>

<div class="ab-first-step">
    <div class="ab-row-fluid">
        <div class="ab-bold ab-desc"><?php _e( $info_text, 'ab' ) ?></div>
    </div>
    <form>
        <div class="ab-mobile-step_1 ab-row-fluid">
            <div class="ab-formGroup ab-category ab-left">
                <label class="ab-formLabel"><?php echo esc_html( __( get_option( 'ab_appearance_text_label_category' ), 'ab' ) ) ?></label>
                <div class="ab-formField">
                    <select class="ab-formElement ab-select-mobile ab-select-category">
                      <option value=""><?php echo esc_html( __( get_option( 'ab_appearance_text_option_category' ), 'ab' ) ) ?></option>
                    </select>
                </div>
            </div>
            <div class="ab-formGroup ab-service ab-left">
                <label class="ab-formLabel"><?php echo esc_html( __( get_option( 'ab_appearance_text_label_service' ), 'ab' ) ) ?></label>
                <div class="ab-formField">
                    <select class="ab-formElement ab-select-mobile ab-select-service">
                        <option value=""><?php echo esc_html( __( get_option( 'ab_appearance_text_option_service' ), 'ab' ) ) ?></option>
                    </select>
                </div>
                <div class="ab-select-service-error ab-label-error ab-bold" style="padding-top: 5px; display: none"><?php _e( '* Please select a service', 'ab' ) ?></div>
            </div>
            <div class="ab-formGroup ab-employee ab-lastGroup ab-left">
                <label class="ab-formLabel"><?php echo esc_html( __( get_option( 'ab_appearance_text_label_employee' ), 'ab' ) ) ?></label>
                <div class="ab-formField">
                    <select class="ab-formElement ab-select-mobile ab-select-employee">
                      <option value=""><?php echo esc_html( __( get_option( 'ab_appearance_text_option_employee' ), 'ab' ) ) ?></option>
                    </select>
                </div>
            </div>
<?php /*            <div class="ab-formGroup ab-lastGroup ab-left">
                <label class="ab-formLabel"><?php echo esc_html( __( get_option( 'ab_appearance_text_label_place' ), 'ab') ) ?></label>
                <div class="ab-formField">
                    <input class="ab-formElement ab-select-mobile ab-select-place" type="number" max="1" min="1" value="1">
                </div>
            </div>
 */ ?>
            <div class="ab-nav-steps ab-clear ab-row-fluid">
                <button class="ab-right ab-mobile-next-step ab-btn ab-none ladda-button" data-style="zoom-in" data-spinner-size="40">
                    <span class="ladda-label"><?php _e( 'Next', 'ab' ) ?></span>
                </button>
            </div>
        </div>
        <div class="ab-mobile-step_2">
            <div class="ab-row-fluid">
                <div class="ab-left ab-available-date ab-formGroup ab-lastGroup" style="width: auto">
                    <label class="ab-nowrap"><b><?php echo esc_html( __( get_option( 'ab_appearance_text_label_select_date' ), 'ab' ) ) ?></b></label>
                    <div class="ab-input-wrap ab-formField">
                        <span class="ab-date-wrap">
                           <input class="ab-date-from ab-formElement" type="text" value="<?php echo esc_attr( date_i18n( 'j F, Y', strtotime( $userData->get( 'date_from' ) ) ) ) ?>" />
                        </span>
                    </div>
                </div>
                <?php if ( !empty( $work_day_time_data['available_days'] ) ) : ?>
                    <div class="ab-left ab-available-days">
                        <ul class="ab-week-days">
                            <?php foreach ( $work_day_time_data['available_days'] as $key => $day ) : ?>
                                <li>
                                    <div class="ab-bold"><?php echo $day ?></div>
                                    <label class="active">
                                        <input class="ab-week-day ab-week-day-<?php echo $key ?>" value="<?php echo $key ?>" checked="checked" type="checkbox"/>
                                    </label>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if ( !empty( $work_day_time_data['time_range'] ) ) : ?>
                    <?php $time_list = $work_day_time_data['time_range'] ?>
                    <div class="ab-left ab-time-range">
                        <div class="ab-left ab-time-from">
                            <label><b><?php echo esc_html( __( get_option( 'ab_appearance_text_label_start_from' ), 'ab' ) ) ?></b></label>
                            <div class="ab-select-wrap">
                                <select class="select-list ab-select-time-from" style="width: auto">
                                    <?php foreach ($time_list as $key => $time) : ?>
                                        <option value="<?php echo $key ?>"<?php if ( $userData->get( 'time_from' ) == $key ) echo ' selected="selected"' ?>><?php echo $time ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                        <div class="ab-left ab-time-to">
                            <label><b><?php echo esc_html( __( get_option( 'ab_appearance_text_label_finish_by' ), 'ab' ) ) ?></b></label>
                            <div class="ab-select-wrap">
                                <select class="select-list ab-select-time-to" style="width: auto">
                                    <?php foreach ($time_list as $key => $time) : ?>
                                        <option value="<?php echo $key ?>"<?php if ( $userData->get( 'time_to' ) == $key ) echo ' selected="selected"' ?>><?php echo $time ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                        <div class="ab-select-time-error ab-bold ab-clear" style="text-align:right; padding-top: 5px; display: none"><?php _e( '* The start time must be less than the end time', 'ab' ) ?></div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="ab-nav-steps ab-clear ab-row-fluid">
                <button class="ab-left ab-mobile-prev-step ab-btn ab-none ladda-button" data-style="zoom-in" data-spinner-size="40">
                    <span class="ladda-label"><?php _e( 'Back', 'ab' ) ?></span>
                </button>
                <button class="ab-right ab-next-step ab-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
                    <span class="ladda-label"><?php _e( 'Next', 'ab' ) ?></span>
                </button>
            </div>
        </div>
    </form>
</div>