<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @var AB_UserBookingData $userData
 * @var string $progress_tracker
 * @var string $info_text
 * @var array $custom_fields
 */

// Show Progress Tracker if enabled in settings
if ( get_option( 'ab_appearance_show_progress_tracker' ) == 1 ) {
    echo $progress_tracker;
}

$current_user = wp_get_current_user();
?>

<div class="ab-row-fluid"><div class="ab-desc"><?php _e( $info_text, 'ab' ) ?></div></div>

<form class="ab-third-step">
    <div class="ab-row-fluid">
        <div class="ab-formGroup ab-left">
            <label class="ab-formLabel"><?php echo esc_html( __( get_option( 'ab_appearance_text_label_name' ), 'ab' ) ) ?></label>
            <div class="ab-formField">
                <input class="ab-formElement ab-full-name" type="text" value="<?php echo (!$userData->get( 'name' ) && $current_user)? $current_user->display_name : $userData->get( 'name' ) ?>" maxlength="60"/>
            </div>
            <div class="ab-full-name-error ab-label-error ab-bold"></div>
        </div>
        <div class="ab-formGroup ab-left">
            <label class="ab-formLabel"><?php echo esc_html( __( get_option( 'ab_appearance_text_label_phone' ), 'ab' ) ) ?></label>
            <div class="ab-formField">
                <input class="ab-formElement ab-user-phone" maxlength="30" type="text" value="<?php echo $userData->get( 'phone' ) ?>"/>
            </div>
            <div class="ab-user-phone-error ab-label-error ab-bold"></div>
        </div>
        <div class="ab-formGroup ab-left">
            <label class="ab-formLabel"><?php echo esc_html( __( get_option( 'ab_appearance_text_label_email' ), 'ab' ) ) ?></label>
            <div class="ab-formField" style="margin-right: 0">
                <input class="ab-formElement ab-user-email" maxlength="40" type="text" value="<?php echo (!$userData->get( 'email' ) && $current_user)? $current_user->user_email : $userData->get( 'email' ) ?>"/>
            </div>
            <div class="ab-user-email-error ab-label-error ab-bold"></div>
        </div>
    </div>

    <?php foreach ( $custom_fields as $custom_field ): ?>
        <div class="ab-row-fluid">
            <div class="ab-formGroup ab-full ab-lastGroup">
                <label class="ab-formLabel"><?php echo $custom_field->label ?></label>
                <div class="ab-formField">
                    <?php if ( $custom_field->type == 'text-field' ): ?>
                        <input class="ab-formElement ab-user-notes ab-custom-field" name="ab-custom-field-<?php echo $custom_field->id ?>">
                    <?php elseif ( $custom_field->type == 'textarea' ): ?>
                        <textarea rows="3" class="ab-formElement ab-user-notes ab-custom-field" name="ab-custom-field-<?php echo $custom_field->id ?>"></textarea>
                    <?php elseif ( $custom_field->type == 'checkboxes' ): ?>
                        <?php foreach ( $custom_field->items as $item ): ?>
                            <label>
                                <input class="ab-custom-field" type="checkbox" value="<?php echo $item ?>" name="ab-custom-field-<?php echo $custom_field->id ?>" />
                                <?php echo $item ?>
                            </label><br/>
                        <?php endforeach ?>
                    <?php elseif ( $custom_field->type == 'radio-buttons' ): ?>
                        <?php foreach ( $custom_field->items as $item ): ?>
                            <label>
                                <input type="radio" class="ab-custom-field" value="<?php echo $item ?>" name="ab-custom-field-<?php echo $custom_field->id ?>" />
                                <?php echo $item ?>
                            </label><br/>
                        <?php endforeach ?>
                    <?php elseif ( $custom_field->type == 'drop-down' ): ?>
                        <select class="ab-custom-field ab-formElement" name="ab-custom-field-<?php echo $custom_field->id ?>">
                            <?php if ( !$custom_field->required ): ?>
                                <option value=""></option>
                            <?php endif ?>
                            <?php foreach ( $custom_field->items as $item ): ?>
                                <option value="<?php echo $item ?>"><?php echo $item ?></option>
                            <?php endforeach ?>
                        </select>
                    <?php endif ?>
                </div>
                <div class="ab-label-error ab-bold ab-custom-field-error ab-custom-field-<?php echo $custom_field->id ?>-error"></div>
            </div>
        </div>
    <?php endforeach ?>

</form>
<div class="ab-row-fluid ab-nav-steps ab-clear">
    <button class="ab-left ab-to-second-step ab-btn ladda-button" data-style="zoom-in" style="margin-right: 10px;" data-spinner-size="40">
        <span class="ladda-label"><?php _e( 'Back', 'ab' ) ?></span>
    </button>
    <button class="ab-right ab-to-fourth-step ab-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php _e( 'Next', 'ab' ) ?></span>
    </button>
</div>