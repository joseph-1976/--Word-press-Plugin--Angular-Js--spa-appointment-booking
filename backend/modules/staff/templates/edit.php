<?php
/**
 * @var AB_Staff $staff
 * @var string $authUrl
 * @var array $staff_errors
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div id="ab-edit-staff">
    <?php if( isset($update) ): ?>
        <div style="margin: 0!important;" class="updated below-h2">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <p><?php _e( 'Settings saved.', 'ab' )?></p>
        </div>
    <?php endif ?>
    <?php if ($staff_errors): ?>
        <div class="error">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php
                foreach ($staff_errors as $staff_error){
                    echo $staff_error . "<br>";
                }
            ?>
        </div>
    <?php endif ?>
    <div style="overflow: hidden; position: relative">
        <h2 class="left"><?php echo $staff->get( 'full_name' ) ?></h2>
        <a class="btn btn-info" id="ab-staff-delete"><?php _e( 'Delete this staff member', 'ab' ) ?></a>
    </div>
    <div class="tabbable">
        <ul class="nav nav-tabs ab-nav-tabs">
            <li class="active"><a id="ab-staff-details-tab" href="#tab1" data-toggle="tab"><?php _e( 'Details', 'ab' ) ?></a></li>
            <li><a id="ab-staff-services-tab" href="#tab2" data-toggle="tab"><?php _e( 'Services', 'ab' ) ?></a></li>
            <li><a id="ab-staff-schedule-tab" href="#tab3" data-toggle="tab"><?php _e( 'Schedule', 'ab') ?></a></li>
            <li><a id="ab-staff-holidays-tab" href="#tab4" data-toggle="tab"><?php _e( 'Days off', 'ab') ?></a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab1">
                <div id="ab-staff-details-container" class="ab-staff-tab-content">
                    <form class="ab-staff-form bs-docs-example form-horizontal" action="" name="ab_staff" method="POST" enctype="multipart/form-data">
                        <table cellspacing="0">
                            <tbody>
                            <tr>
                                <td class="control-group">
                                    <label class="control-label" for="ab-staff-wpuser"><?php _e( 'User', 'ab') ?></label>
                                    <div class="controls">
                                        <select name="wp_user_id" id="ab-staff-wpuser">
                                            <option value=""><?php _e( 'Select from WP users', 'ab') ?></option>
                                            <?php foreach ( $form->getUsersForStaff( $staff->id ) as $user ) : ?>
                                                <option value="<?php echo $user->ID ?>" data-email="<?php echo $user->user_email ?>" <?php selected($user->ID, $staff->get( 'wp_user_id' )) ?>><?php echo $user->display_name ?></option>
                                            <?php endforeach ?>
                                        </select>
                                        <img
                                            src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>"
                                            alt=""
                                            class="ab-popover-ext"
                                            data-ext_id="ab-staff-popover-ext"
                                            />
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="control-group">
                                    <label class="control-label" for="ab-staff-full-name"><?php _e( 'Photo', 'ab') ?></label>
                                    <div class="controls">
                                        <div id="ab-staff-avatar-image">
                                            <?php if ( $staff->get( 'avatar_url' ) ) : ?>
                                                <img src="<?php echo $staff->get( 'avatar_url' ) ?>" alt="<?php _e( 'Avatar', 'ab') ?>"/>
                                                <a id="ab-delete-avatar" href="javascript:void(0)"><?php _e( 'Delete current photo', 'ab') ?></a>
                                            <?php endif ?>
                                        </div>
                                        <input id="ab-staff-avatar" name="avatar" type="file"/>
                                    </div>
                                </td>
                            </tr>
                            <tr class="form-field form-required">
                                <td class="control-group">
                                    <label class="control-label" for="ab-staff-full-name"><?php _e( 'Full name', 'ab') ?></label>
                                    <div class="controls">
                                        <input id="ab-staff-full-name" name="full_name" value="<?php echo esc_attr($staff->get('full_name')) ?>" type="text"/><span class="ab-red"> *</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="control-group">
                                    <label class="control-label" for="ab-staff-email"><?php _e( 'Email', 'ab') ?></label>
                                    <div class="controls">
                                        <input id="ab-staff-email" name="email" value="<?php echo esc_attr($staff->get( 'email' )) ?>" type="text"/>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="control-group">
                                    <label class="control-label" for="ab-staff-phone"><?php _e( 'Phone', 'ab') ?></label>
                                    <div class="controls">
                                        <input id="ab-staff-phone" name="phone" value="<?php echo esc_attr($staff->get( 'phone')) ?>" type="text"/>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="control-group">
                                    <h4 style="float: left"><?php _e( 'Google Calendar integration', 'ab' ) ?></h4>
                                    <img style="float: left;margin-top: 8px;margin-left:15px;"
                                         src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>"
                                         alt=""
                                         class="ab-popover-ext"
                                         data-ext_id="ab-staff-google-popover-ext"
                                        />
                                </td>
                            </tr>
                            <tr>
                                <td class="control-group">
                                    <label class="control-label" for="ab-staff-google-pass">
                                        <?php if ( isset( $authUrl ) ): ?>
                                            <?php if ( $authUrl ): ?>
                                                <a href="<?php echo $authUrl ?>"><?php _e( 'Connect', 'ab' ) ?></a>
                                            <?php else: ?>
                                                <?php _e( 'Please configure Google Calendar <a href="?page=ab-settings&type=_google_calendar">settings</a> first', 'ab' ) ?>
                                            <?php endif ?>
                                        <?php else: ?>
                                            <?php _e( 'Connected', 'ab' ) ?> (<a href="?page=ab-system-staff&google_logout=<?php echo $staff->get('id') ;?>"><?php _e( 'disconnect', 'ab' ) ?></a>)
                                        <?php endif ?>
                                    </label>
                                    <div class="controls">
                                    </div>
                                </td>
                            </tr>
                            <?php if (!isset($authUrl)): ?>
                            <tr>
                                <td class="control-group">
                                    <label class="control-label" for="ab-staff-google-pass"><?php _e( 'Calendar ID', 'ab' ) ?></label>
                                    <div class="controls">
                                        <input id="ab-staff-phone" <?php disabled(isset($authUrl)) ?> name="google_calendar_id" value="<?php echo esc_attr($staff->get('google_calendar_id')) ?>" type="text"/>
                                        <img
                                            src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>"
                                            alt=""
                                            class="ab-popover-ext"
                                            data-ext_id="ab-staff-calendar-id-popover-ext"
                                            />
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="control-group">
                                    <label class="control-label"></label>
                                    <div class="controls">
                                        <input id="ab-update-staff" type="submit" value="<?php _e( 'Update', 'ab') ?>" class="btn btn-info ab-update-button">
                                        <button class="ab-reset-form" type="reset"><?php _e( 'Reset', 'ab') ?></button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <input type="hidden" name="id" value="<?php echo $staff->get( 'id' ) ?>"/>
                        <input type="hidden" name="action" value="ab_update_staff"/>
                    </form>
                </div>
            </div>
            <div class="tab-pane" id="tab2">
                <div id="ab-staff-services-container" class="ab-staff-tab-content" style="display: none"></div>
            </div>
            <div class="tab-pane" id="tab3">
                <div id="ab-staff-schedule-container" class="ab-staff-tab-content" style="display: none"></div>
            </div>
            <div class="tab-pane" id="tab4">
                <div id="ab-staff-holidays-container" class="ab-staff-tab-content" style="display: none"></div>
            </div>
        </div>
    </div>
</div>