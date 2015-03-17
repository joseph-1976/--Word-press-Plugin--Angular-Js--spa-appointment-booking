<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/** @var WP_Locale $wp_locale */
global $wp_locale;
?>
<div class="ab-booking-form">

    <!-- Progress Tracker-->
    <?php $step = 1; include '_progress_tracker.php'; ?>

    <div class="ab-first-step">
        <div class="ab-row-fluid">
            <span data-inputclass="input-xxlarge" data-default="<?php echo esc_attr( get_option( 'ab_appearance_text_info_first_step' ) ); ?>" data-link-class="ab-text-info-first" class="ab-bold ab_editable" id="ab-text-info-first" data-rows="7" data-type="textarea" data-pk="1"><?php echo esc_html( get_option( 'ab_appearance_text_info_first_step' ) ) ?></span>
        </div>
        <div class=ab-service-form>
            <div class="ab-mobile-step_1 ab-row-fluid">
                <div class="ab-formGroup ab-left">
                    <label data-default="<?php echo get_option( 'ab_appearance_text_label_category' ); ?>" data-link-class="ab-text-option-category" class="ab-formLabel text_category_label" id="ab-text-label-category"  data-type="multiple" data-pk="1"><?php echo esc_html(get_option( 'ab_appearance_text_label_category' )) ?></label>
                    <div class="ab-formField">
                        <select class="ab-formElement ab-select-mobile ab-select-category" style="width: 100%">
                            <option value="" class="editable" id="ab-text-option-category" data-default="<?php echo esc_attr( get_option( 'ab_appearance_text_option_category' ) ); ?>"><?php echo esc_attr( get_option( 'ab_appearance_text_option_category' ) ); ?></option>
                            <option value="1">Cosmetic Dentistry</option>
                            <option value="2">Invisalign</option>
                            <option value="3">Orthodontics</option>
                            <option value="4">Dentures</option>
                        </select>
                    </div>
                </div>
                <div class="ab-formGroup ab-left">
                    <label data-default="<?php echo get_option( 'ab_appearance_text_label_service' ); ?>" data-link-class="ab-text-option-service" class="ab-formLabel text_service_label" id="ab-text-label-service" data-type="multiple" data-pk="1"><?php echo esc_html(get_option( 'ab_appearance_text_label_service' )) ?></label>
                    <div class="ab-formField">
                        <select class="ab-formElement ab-select-mobile ab-select-service" style="width: 100%">
                            <option value="" class="editable" id="ab-text-option-service" data-default="<?php echo esc_attr( get_option( 'ab_appearance_text_option_service' ) ); ?>"><?php echo esc_attr( get_option( 'ab_appearance_text_option_service' ) ); ?></option>
                            <option value="1">Crown and Bridge</option>
                            <option value="2">Teeth Whitening</option>
                            <option value="3">Veneers</option>
                            <option value="4">Invisalign (invisable braces)</option>
                            <option value="5">Orthodontics (braces)</option>
                            <option value="6">Wisdom tooth Removal</option>
                            <option value="7">Root Canal Treatment</option>
                            <option value="8">Dentures</option>
                        </select>
                    </div>
                </div>
                <div class="ab-formGroup ab-lastGroup ab-left">
                    <label data-default="<?php echo get_option( 'ab_appearance_text_label_employee' ); ?>" data-link-class="ab-text-option-employee" class="ab-formLabel text_employee_label" id="ab-text-label-employee" data-type="multiple" data-pk="1"><?php echo esc_html(get_option( 'ab_appearance_text_label_employee' )) ?></label>
                    <div class="ab-formField">
                        <select class="ab-formElement ab-select-mobile ab-select-employee" style="width: 100%">
                            <option value="" class="editable" id="ab-text-option-employee" data-default="<?php echo esc_attr( get_option( 'ab_appearance_text_option_employee' ) ); ?>"><?php echo esc_attr( get_option( 'ab_appearance_text_option_employee' ) ); ?></option>
                            <option value="1">Nick Knight</option>
                            <option value="2">Jane Howard</option>
                            <option value="3">Ashley Stamp</option>
                            <option value="4">Bradley Tannen</option>
                            <option value="5">Wayne Turner</option>
                            <option value="6">Emily Taylor</option>
                            <option value="7">Hugh Canberg</option>
                            <option value="8">Jim Gonzalez</option>
                            <option value="9">Nancy Stinson</option>
                            <option value="10">Marry Murphy</option>
                        </select>
                    </div>
                </div>
<?php /*                <div class="ab-formGroup ab-lastGroup ab-left">
                    <label data-default="<?php echo esc_html(get_option( 'ab_appearance_text_label_place')); ?>" data-link-class="ab-text-option-place" class="ab-formLabel ab_editable" data-type="text" id="ab-text-label-place"><?php echo esc_html(get_option( 'ab_appearance_text_label_place', 'Places number' )); ?></label>
                    <div class="ab-formField">
                        <input class="ab-formElement ab-select-mobile ab-select-place" type="number" max="1" min="1" value="1">
                    </div>
                </div>
 */ ?>
                <button class="ab-right ab-mobile-next-step ab-btn ab-none ladda-button" onclick="return false">
                    <span><?php _e( 'Next', 'ab' ) ?></span>
                </button>
            </div>
            <div class="ab-mobile-step_2">
                <div class="ab-row-fluid">
                    <div class="ab-available-date ab-formGroup ab-lastGroup ab-left">
                        <label class="ab-nowrap" data-default="<?php echo get_option( 'ab_appearance_text_label_select_date' ); ?>" data-link-class="text_select_date_label" class="ab_editable" id="ab-text-label-select_date" data-type="text" data-pk="1"><?php echo esc_html(get_option( 'ab_appearance_text_label_select_date' )) ?></label>
                        <div class="ab-input-wrap ab-formField">
                            <span class="ab-date-wrap">
                               <input style="background: white" class="ab-date-from ab-formElement picker__input--active" type="text" value="29 November, 2013" />
                            </span>
                        </div>
                    </div>
                    <div class="ab-available-days ab-left">
                        <ul class="ab-week-days">
                            <?php foreach ( $wp_locale->weekday_abbrev as $weekday_abbrev ): ?>
                                <li>
                                    <div class="ab-bold"><?php echo $weekday_abbrev ?></div>
                                    <label class="active">
                                        <input class="ab-week-day" value="1" checked="checked" type="checkbox">
                                    </label>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                    <div class="ab-time-range ab-left">
                        <div class="ab-time-from ab-left">
                            <label data-default="<?php echo get_option( 'ab_appearance_text_label_start_from' ); ?>" data-link-class="text_start_from_label" class="ab_editable" id="ab-text-label-start_from" data-type="text" data-pk="1"><?php echo esc_html(get_option( 'ab_appearance_text_label_start_from' )) ?></label>
                            <div class="ab-select-wrap">
                                <select class="select-list ab-select-time-from">
                                    <option value="00:00">12:00 am</option>
                                    <option value="01:00">1:00 am</option>
                                    <option value="02:00">2:00 am</option>
                                    <option value="03:00">3:00 am</option>
                                    <option value="04:00">4:00 am</option>
                                    <option value="05:00">5:00 am</option>
                                    <option value="06:00">6:00 am</option>
                                    <option value="07:00">7:00 am</option>
                                    <option value="08:00" selected="selected">8:00 am</option>
                                    <option value="09:00">9:00 am</option>
                                    <option value="10:00">10:00 am</option>
                                    <option value="11:00">11:00 am</option>
                                    <option value="12:00">12:00 pm</option>
                                    <option value="13:00">1:00 pm</option>
                                    <option value="14:00">2:00 pm</option>
                                    <option value="15:00">3:00 pm</option>
                                    <option value="16:00">4:00 pm</option>
                                    <option value="17:00">5:00 pm</option>
                                    <option value="18:00">6:00 pm</option>
                                    <option value="19:00">7:00 pm</option>
                                    <option value="20:00">8:00 pm</option>
                                    <option value="21:00">9:00 pm</option>
                                    <option value="22:00">10:00 pm</option>
                                    <option value="23:00">11:00 pm</option>
                                </select>
                            </div>
                        </div>
                        <div class="ab-time-to ab-left">
                            <label data-default="<?php echo get_option( 'ab_appearance_text_label_finish_by' ); ?>" data-link-class="text_finish_by_label" class="ab_editable" id="ab-text-label-finish_by" data-type="text" data-pk="1"><?php echo esc_html(get_option( 'ab_appearance_text_label_finish_by' )) ?></label>
                            <div class="ab-select-wrap">
                                <select class="select-list ab-select-time-to">
                                    <option value="09:00">9:00 am</option>
                                    <option value="10:00">10:00 am</option>
                                    <option value="11:00">11:00 am</option>
                                    <option value="12:00">12:00 pm</option>
                                    <option value="13:00">1:00 pm</option>
                                    <option value="14:00">2:00 pm</option>
                                    <option value="15:00">3:00 pm</option>
                                    <option value="16:00">4:00 pm</option>
                                    <option value="17:00">5:00 pm</option>
                                    <option value="18:00">6:00 pm</option>
                                    <option value="19:00">7:00 pm</option>
                                    <option value="20:00">8:00 pm</option>
                                    <option value="21:00">9:00 pm</option>
                                    <option value="22:00">10:00 pm</option>
                                    <option value="23:00">11:00 pm</option>
                                    <option value="23:59">12:00 am</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ab-row-fluid ab-nav-steps last-row ab-clear">
                    <button class="ab-right ab-mobile-prev-step ab-btn ab-none ladda-button">
                        <span><?php _e( 'Back', 'ab' ) ?></span>
                    </button>
                    <button class="ab-right ab-next-step ab-btn ladda-button">
                        <span><?php _e( 'Next', 'ab' ) ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<style id="ab_update_arrow">
    .picker__nav--next:before { border-left: 6px solid <?php echo get_option( 'ab_appearance_color' ) ?>!important; }
    .picker__nav--prev:before { border-right: 6px solid <?php echo get_option( 'ab_appearance_color' ) ?>!important; }
</style>