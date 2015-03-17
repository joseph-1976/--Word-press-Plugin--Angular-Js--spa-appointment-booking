<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="ab-title"><?php _e('Notifications','ab') ?></div>
<div style="min-width: 800px;margin-top: -20px">

    <?php if (isset($message)) : ?>
        <div id="message" style="margin: 0px!important;" class="updated below-h2"><p><?php echo $message ?></p></div>
    <?php endif ?>

    <form method="post">
        <div class="ab-notifications">
            <?php
                $sender_name  = get_option( 'ab_settings_sender_name' ) == '' ?
                    get_option( 'blogname' )    : get_option( 'ab_settings_sender_name' );
                $sender_email = get_option( 'ab_settings_sender_email' ) == ''  ?
                    get_option( 'admin_email' ) : get_option( 'ab_settings_sender_email' );
            ?>
            <!-- sender name -->
            <label for="sender_name" style="display: inline;"><?php _e( 'Sender name', 'ab' ); ?></label>
            <input id="sender_name" name="sender_name" class="ab-sender" type="text" value="<?php echo $sender_name ; ?>"/><br>
            <!-- sender email -->
            <label for="sender_email" style="display: inline;"><?php _e( 'Sender email', 'ab' ); ?></label>
            <input id="sender_email" name="sender_email" class="ab-sender" type="text" value="<?php echo $sender_email; ?>"/>
        </div>
        <?php $data = $form->getData() ?>
        <?php foreach ( $form->slugs as $slug ): ?>
        <div class="ab-notifications">
            <div class="ab-toggle-arrow"></div>
            <?php echo $form->renderActive($slug) ?>
            <div class="ab-form-field">
                <div class="ab-form-row">
                    <?php echo $form->renderSubject($slug) ?>
                </div>
                <div id="message_editor" class="ab-form-row">
                    <label class="ab-form-label" style="margin-top: 35px;"><?php _e( 'Message', 'ab' ) ?></label>
                    <?php echo $form->renderMessage($slug) ?>
                </div>
                <?php if ('provider_info' == $slug): ?>
                    <?php echo $form->renderCopy($slug) ?>
                <?php endif ?>
                <div class="ab-form-row">
                    <label class="ab-form-label"><?php _e( 'Codes','ab' ) ?></label>
                    <div class="ab-codes left">
                        <table>
                            <tbody>
                                <?php include $slug == 'event_next_day' ? '_codes_event_next_day.php' : '_codes.php' ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="ab-notifications" style="border: 0">
            <input type="submit" value="<?php _e( 'Save Changes', 'ab' )?>" class="btn btn-info ab-update-button" />
            <button class="ab-reset-form" type="reset"><?php _e( 'Reset', 'ab' )?></button>
        </div>
    </form>
    <div class="ab-notification-info">
        <?php
            echo '<i>' . __( 'To send scheduled notifications please execute the following script hourly with your cron:', 'ab' ) . '</i><br />';
            echo '<b>php -f ' . realpath( AB_PATH . '/lib/utils/send_notifications_cron.php' ) . '</b>';
        ?>
    </div>
</div>
<script type="text/javascript">
    jQuery(function($) {
        // menu fix for WP 3.8.1
        $('#toplevel_page_ab-system > ul').css('margin-left', '0px');
        // Show-hide Notifications
        $('input:checkbox[id!=_active]').each(function() {
            $(this).change(function() {
                if ( $(this).attr('checked') ) {
                    $(this).parent().next('div.ab-form-field').show(200);
                    $(this).parents('.ab-notifications').find('.ab-toggle-arrow').css('background','url(<?php echo plugins_url( 'backend/resources/images/notifications-arrow-up.png', AB_PATH . '/main.php' ) ?>) 100% 0 no-repeat');
                } else {
                    $(this).parent().next('div.ab-form-field').hide(200);
                    $(this).parents('.ab-notifications').find('.ab-toggle-arrow').css('background','url(<?php echo plugins_url( 'backend/resources/images/notifications-arrow-down.png', AB_PATH . '/main.php' ) ?>) 100% 0 no-repeat');
                }
            }).change();
        });
        $('.ab-toggle-arrow').click(function() {
            $(this).nextAll('.ab-form-field').toggle(200, function() {
                if ( $('.ab-form-field').css('display') == 'block' ) {
                    $(this).prevAll('.ab-toggle-arrow').css('background','url(<?php echo plugins_url( 'backend/resources/images/notifications-arrow-up.png', AB_PATH . '/main.php' ) ?>) 100% 0 no-repeat');
                } else {
                    $(this).prevAll('.ab-toggle-arrow').css('background','url(<?php echo plugins_url( 'backend/resources/images/notifications-arrow-down.png', AB_PATH . '/main.php' ) ?>) 100% 0 no-repeat');
                }
            });
        });
        // filter sender name and email
        var escapeXSS = function (infected) {
            var regexp = /([<|(]("[^"]*"|'[^']*'|[^'">])*[>|)])/gi;
            return infected.replace(regexp, '');
        };
        $('input.ab-sender').on('change', function() {
            var $val = $(this).val();
            $(this).val(escapeXSS($val));
        });

    });
</script>