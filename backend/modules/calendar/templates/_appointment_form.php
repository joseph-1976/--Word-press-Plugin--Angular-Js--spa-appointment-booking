<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<div ng-controller=appointmentDialogCtrl id=ab_appointment_dialog style="display: none">

    <div ng-hide=loading class=dialog-content>
        <form ng-submit=processForm() class=form-horizontal>

            <div class=control-group>
                <label class=control-label><?php _e('Provider', 'ab') ?></label>

                <div class=controls>
                    <select class="field" ng-model=form.staff ng-options="s.full_name for s in dataSource.data.staff"></select>
                </div>
            </div>

            <div class=control-group>
                <label class=control-label><?php _e('Service', 'ab') ?></label>

                <div class=controls>
                    <div my-slide-up="errors.service_required" style="color: red;">
                        <?php _e('Please select a service', 'ab') ?>
                    </div>
                    <select class="field" ng-model=form.service ng-options="s.title for s in form.staff.services" ng-change=onServiceChange()>
                        <option value=""><?php _e('-- Select a service --', 'ab') ?></option>
                    </select>
                </div>
            </div>

            <div class=control-group>
                <label class=control-label><?php _e('Date', 'ab') ?></label>

                <div class=controls>
                    <input class="field" type=text ng-model=form.date ui-date="dateOptions"/>
                </div>
            </div>

            <div class=control-group>
                <label class=control-label><?php _e('Period', 'ab') ?></label>

                <div class=controls>
                    <div my-slide-up=errors.date_interval_not_available id=date_interval_not_available_msg>
                        <?php _e('The selected period is occupied by another appointment', 'ab') ?>
                    </div>
                    <select class="field-col-2" ng-model=form.start_time ng-options="t.title for t in dataSource.data.time" ng-change=onStartTimeChange()></select>
                    <span><?php _e(' to ', 'ab') ?></span>
                    <select class="field-col-2" ng-model=form.end_time
                            ng-options="t.title for t in dataSource.getDataForEndTime()"
                            ng-change=onEndTimeChange()></select>

                    <div my-slide-up=errors.date_interval_warning id=date_interval_warning_msg>
                        <?php _e('The selected period does\'t match default duration for the selected service', 'ab') ?>
                    </div>
                    <div my-slide-up="errors.time_interval" ng-bind="errors.time_interval" style="color: red;"></div>
                </div>
            </div>

            <div class=control-group>
                <label class=control-label>
                    <?php _e('Customers', 'ab') ?><br/>
                    <span ng-show="form.service" title="<?php echo esc_attr( __( 'Selected / maximum', 'ab' ) ) ?>">{{form.customers.length}}/{{form.service.capacity}}</span>
                </label>
                <div class=controls>
                    <ul class="ab-customer-list">
                        <li ng-repeat="customer in form.customers">
                            <a ng-click="editCustomFields(customer)" title="<?php echo esc_attr( __( 'Edit custom fields values', 'ab' ) ) ?>">{{customer.name}}</a>
                            <span ng-click="removeCustomer(customer)" class="icon icon-remove" title="<?php echo esc_attr( __( 'Remove customer', 'ab' ) ) ?>"></span>
                        </li>
                    </ul>

                    <div ng-show="!form.service || form.customers.length < form.service.capacity">
                        <div>
                            <div my-slide-up="errors.customers_required" style="color: red;"><?php _e('Please select a customer', 'ab') ?></div>

                            <div my-slide-up="errors.overflow_capacity" ng-bind="errors.overflow_capacity" style="color: red;"></div>

                            <select id="chosen" multiple data-placeholder="<?php _e('-- Search customers --', 'ab') ?>"
                                    class="field chzn-select" chosen="dataSource.data.customers"
                                    ng-model="form.customers" ng-options="c.name for c in dataSource.data.customers">
                            </select>
                        </div>

                        <div style="margin-bottom: 2px;" class="ab-inline-block ab-create-customer" new-customer-dialog=createCustomer(customer) backdrop=false btn-class=""></div>
                    </div>
                </div>
            </div>

            <div class=control-group>
                <label class=control-label></label>

                <div class=controls>
                    <input style="margin-top: 0" type="checkbox" ng-model=form.email_notification /> <?php _e('Send email notifications', 'ab') ?>
                    <img
                        src="<?php echo plugins_url('backend/resources/images/help.png', AB_PATH . '/main.php') ?>"
                        alt=""
                        class="ab-popover"
                        popover="<?php echo esc_attr(__('If email notifications are enabled and you want the customer or the staff member to be notified about this appointment after saving, tick this checkbox before clicking Save.', 'ab')) ?>"
                        style="width:16px;margin-left:0;"
                        />
                </div>
            </div>

            <div class=control-group>
                <label class=control-label></label>

                <div class=controls>
                    <div class=dialog-button-wrapper>
                        <input type=submit class="btn btn-info ab-update-button" value="<?php _e('Save') ?>"/>
                        <a ng-click=closeDialog() class=ab-reset-form href=""><?php _e('Cancel') ?></a>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <div ng-show=loading class=loading-indicator>
        <img src="<?php echo plugins_url( 'backend/resources/images/ajax_loader_32x32.gif', AB_PATH . '/main.php' ) ?>" alt="" />
    </div>

    <?php include '_custom_fields_form.php' ?>

</div>

<style>
    .search-choice {
        display: none;
    }
</style>