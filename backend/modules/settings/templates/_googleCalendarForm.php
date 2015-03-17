<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<form method="post" action="<?php echo add_query_arg( 'type', '_google_calendar' ) ?>" enctype="multipart/form-data" class="ab-settings-form">

    <?php if (isset($message_gc)) : ?>
        <div id="message" style="margin: 0px!important;" class="updated below-h2">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <p><?php echo $message_gc ?></p>
        </div>
    <?php endif ?>

    <table class="form-horizontal">
        <tr>
            <td colspan="3">
                <fieldset class="ab-gc-instruction">
                    <legend><?php _e( 'Google Instructions', 'ab' ) ?></legend>
                    <div>
                        <div style="margin-bottom: 10px">
                            <?php _e( 'To find your client ID and client secret, do the following:', 'ab' ) ?>
                        </div>
                        <ol>
                            <li><?php _e( 'Go to the <a href="https://console.developers.google.com/" target="_blank">Google Developers Console</a>.', 'ab' ) ?></li>
                            <li><?php _e( 'Select a project, or create a new one.', 'ab' ) ?></li>
                            <li><?php _e( 'In the sidebar on the left, expand <b>APIs & auth</b>. Next, click <b>APIs</b>. In the list of APIs, make sure the status is <b>ON</b> for the Google Calendar API.', 'ab' ) ?></li>
                            <li><?php _e( 'In the sidebar on the left, select <b>Credentials</b>.', 'ab' ) ?></li>
                            <li><?php _e( 'Create your project\'s OAuth 2.0 credentials by clicking <b>Create new Client ID</b>, selecting <b>Web application</b>, and providing the information needed to create the credentials. For <b>AUTHORIZED REDIRECT URIS</b> enter the <b>Redirect URI</b> found below on this page.', 'ab' ) ?></li>
                            <li><?php _e( 'Look for the <b>Client ID</b> and <b>Client secret</b> in the table associated with each of your credentials.', 'ab' ) ?></li>
                        </ol>
                    </div>
                </fieldset>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <h4><?php _e( 'Google Calendar', 'ab' ) ?></h4>
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( 'Client ID', 'ab' ) ?></label>
            </td>
            <td colspan="2">
                <input type="text" name="ab_settings_google_client_id" value="<?php echo get_option( 'ab_settings_google_client_id' ) ?>" >
                <img
                    src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'The client ID obtained from the Developers Console', 'ab' ) ) ?>"
                    />
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( 'Client secret', 'ab' ) ?></label>
            </td>
            <td colspan="2">
                <input type="text" name="ab_settings_google_client_secret" value="<?php echo get_option( 'ab_settings_google_client_secret' ) ?>" >
                <img
                    src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'The client secret obtained from the Developers Console', 'ab' ) ) ?>"
                    />
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( 'Redirect URI', 'ab' ) ?></label>
            </td>
            <td>
                <input type="text" readonly value="<?php echo AB_Google::generateRedirectURI() ?>" onclick="this.select();" style="cursor: pointer;">
                <img
                    src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php _e('Enter this URL as a redirect URI in the Developers Console', 'ab') ?>"
                    />
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( '2 way sync', 'ab' ) ?></label>
            </td>
            <td>
                <select name="ab_settings_google_two_way_sync" style="width: 200px;">
                    <?php foreach ( array( __( 'Disabled', 'ab' ) => '0', __( 'Enabled', 'ab' ) => '1' ) as $text => $mode ): ?>
                        <option value="<?php echo $mode ?>" <?php selected( get_option( 'ab_settings_google_two_way_sync' ), $mode ) ?> ><?php echo $text ?></option>
                    <?php endforeach ?>
                </select>
                <img
                    src="<?php echo esc_attr( plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'By default Bookly pushes new appointments and any further changes to Google Calendar. If you enable this option then Bookly will fetch events from Google Calendar and remove corresponding time slots before displaying the second step of the booking form (this may lead to a delay when users click Next at the first step).', 'ab' ) ) ?>"
                    />
            </td>
        </tr>
        <tr>
            <td>
                <label><?php _e( 'Limit number of fetched events', 'ab' ) ?></label>
            </td>
            <td>
                <select name="ab_settings_google_limit_events" style="width: 200px;">
                    <?php foreach ( array( __( 'Disabled', 'ab' ) => '0', 250 => 250, 500 => 500, 1500 => 1500, 2500 => 2500 ) as $text => $limit ): ?>
                        <option value="<?php echo $limit ?>" <?php selected( get_option( 'ab_settings_google_limit_events' ), $limit ) ?> ><?php echo $text ?></option>
                    <?php endforeach ?>
                </select>
                <img
                    src="<?php echo esc_attr( plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ) ?>"
                    alt=""
                    class="ab-popover"
                    data-content="<?php echo esc_attr( __( 'If there is a lot of events in Google Calendar sometimes this leads to a lack of memory in PHP when Bookly tries to fetch all events. You can limit the number of fetched events here. This only works when 2 way sync is enabled.', 'ab' ) ) ?>"
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

