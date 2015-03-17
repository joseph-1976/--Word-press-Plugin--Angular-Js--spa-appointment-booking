<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="ab-title">
    <?php _e( 'Appearance', 'ab' ) ?>
    <div class="updated below-h2" style="margin: 0!important; display: none">
        <button type="button" class="close" onclick="jQuery('.updated').hide()">&times;</button>
        <p><?php _e( 'Settings saved.', 'ab' ); ?></p>
    </div>
</div>

<input type=text class="wp-color-picker appearance-color-picker" name=color
       value="<?php echo get_option( 'ab_appearance_color' ) ?>"
       data-selected="<?php echo get_option( 'ab_appearance_color' ) ?>" />

<div id="ab-appearance" style="max-width: 960px;">
    <form method=post id=common_settings style="margin-right: 15px">
        <div class="row">
            <div class="span9">
                <legend id=main_form>
                    <label style="display: inline">
                        <input id=ab-progress-tracker-checkbox name=ab-progress-tracker-checkbox <?php if (get_option( 'ab_appearance_show_progress_tracker' )): ?>checked=checked<?php endif ?> type=checkbox />
                        <b><?php _e( 'Show form progress tracker', 'ab' ) ?></b>
                    </label>
                </legend>
            </div>
        </div>
        <div class="row">
            <div style="display: none" class="span3">
                <label class="checkbox">
                    <input id="" name="" type="checkbox" /> <b><?php _e( 'Show calendar', 'ab' ) ?></b>
                </label>
            </div>
            <div class="span3">
                <label class="checkbox">
                    <input id="ab-blocked-timeslots-checkbox" name="ab-blocked-timeslots-checkbox" <?php if (get_option( 'ab_appearance_show_blocked_timeslots' )): ?>checked=checked<?php endif ?> type="checkbox" />
                    <b><?php _e( 'Show blocked timeslots', 'ab' ) ?></b>
                </label>
            </div>
            <div class="span3">
                <label class="checkbox">
                    <input id="ab-day-one-column-checkbox" name="ab-day-one-column-checkbox" <?php if (get_option( 'ab_appearance_show_day_one_column' )): ?>checked=checked<?php endif ?> type="checkbox" />
                    <b><?php _e( 'Show each day in one column', 'ab' ) ?></b>
                </label>
            </div>
        </div>
    </form>
    <!-- Tabs -->
    <div class=tabbable style="margin-top: 20px;">
        <ul class="nav nav-tabs ab-nav-tabs">
            <?php foreach ( $steps as $step_id => $step_name ): ?>
                <li class="ab-step-tab-<?php echo $step_id ?> ab-step-tabs<?php if ( $step_id == 1 ): ?> active<?php endif ?>" data-step-id="<?php echo $step_id ?>">
                    <a href="#" data-toggle=tab><?php echo $step_id ?>. <span class="text_step_<?php echo $step_id ?>" ><?php echo esc_html( $step_name ) ?></span></a>
                </li>
            <?php endforeach ?>
        </ul>
        <!-- Tabs-Content -->
        <div class=tab-content>
            <?php foreach ( $steps as $step_id => $step_name ) : ?>
                <div class="tab-pane-<?php echo $step_id ?><?php if ( $step_id == 1 ): ?> active<?php endif ?>" data-step-id="<?php echo $step_id ?>"<?php if ( $step_id != 1 ): ?> style="display: none"<?php endif ?>>
                    <?php
                        // Render unique data per step
                        switch ( $step_id ) {
                            // Service
                            case 1:
                                include '_1_service.php';
                                break;
                            // Time
                            case 2:
                                include '_2_time.php';
                                break;
                            // Details
                            case 3:
                                include '_3_details.php';
                                break;
                            // Payment
                            case 4:
                                include '_4_payment.php';
                                break;
                            // Done
                            case 5:
                                include '_5_done.php';
                                break;
                        }
                    ?>
                </div>
            <?php endforeach ?>
        </div>
        <div class="right" style="margin-right: 20px">
                <p><?php _e('Click on the underlined text to edit.', 'ab') ?></p>
        </div>
        <div class="clear"></div>
        <!-- controls -->
        <div class=controls>
            <!-- spinner -->
            <span id="update_spinner" class="spinner"></span>
            <!-- update button -->
            <button id="update_button" class="btn btn-info ab-update-button ab-appearance-update">
                <?php _e( 'Update', 'ab' ) ?>
            </button>
            <!-- reset button -->
            <button id="reset_button" class="ab-reset-form ab-appearance-reset" type="reset">
                <?php _e( 'Reset', 'ab' ) ?>
            </button>
        </div>
    </div>
</div>