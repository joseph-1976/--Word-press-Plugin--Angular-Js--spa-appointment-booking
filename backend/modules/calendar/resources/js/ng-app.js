;(function() {

    var module = angular.module('appointmentForm', ['ui.date', 'newCustomerDialog']);

    /**
     * DataSource service.
     */
    module.factory('dataSource', function($q, $rootScope, $filter) {
        var ds = {
            data : {
                staff         : [],
                customers     : [],
                time          : [],
                time_interval : 900
            },
            form : {
                id         : null,
                staff      : null,
                service    : null,
                date       : null,
                start_time : null,
                end_time   : null,
                customers  : [],
                custom_fields : [],
                email_notification : null
            },
            loadData : function() {
                var deferred = $q.defer();
                jQuery.get(
                    ajaxurl,
                    { action : 'ab_get_data_for_appointment_form' },
                    function(data) {
                        ds.data = data;
                        // Add empty element to beginning of array for single-select customer form
                        ds.data.customers.unshift({name: ''});

                        if (data.staff.length) {
                            ds.form.staff = data.staff[0];
                        }
                        ds.form.start_time = data.time[0];
                        ds.form.end_time   = data.time[1];
                        deferred.resolve();
                    },
                    'json'
                );
                return deferred.promise;
            },
            findStaff : function(id) {
                var result = null;
                jQuery.each(ds.data.staff, function(key, item) {
                    if (item.id == id) {
                        result = item;
                        return false;
                    }
                });
                return result;
            },
            findService : function(staff_id, id) {
                var result = null,
                    staff  = ds.findStaff(staff_id);

                if (staff !== null) {
                    jQuery.each(staff.services, function(key, item) {
                        if (item.id == id) {
                            result = item;
                            return false;
                        }
                    });
                }
                return result;
            },
            findTime : function(date) {
                var result = null,
                    value_to_find = $filter('date')(date, 'HH:mm');

                jQuery.each(ds.data.time, function(key, item) {
                    if (item.value === value_to_find) {
                        result = item;
                        return false;
                    }
                });
                return result;
            },
            findCustomer : function(id) {
                var result = null;
                jQuery.each(ds.data.customers, function(key, item) {
                    if (item.id == id) {
                        result = item;
                        return false;
                    }
                });
                return result;
            },
            getDataForEndTime : function() {
                var result = [];
                jQuery.each(ds.data.time, function(key, item) {
                    if (
                        ds.form.start_time === null ||
                            item.value > ds.form.start_time.value
                        ) {
                        result.push(item);
                    }
                });
                return result;
            },
            setEndTimeBasedOnService : function() {
                var i = jQuery.inArray(ds.form.start_time, ds.data.time),
                    d = ds.form.service ? ds.form.service.duration : ds.data.time_interval;
                if (i !== -1) {
                    for (; i < ds.data.time.length; ++ i) {
                        d -= ds.data.time_interval;
                        if (d < 0) {
                            break;
                        }
                    }
                    ds.form.end_time = ds.data.time[i];
                }
            },
            getStartAndEndDates : function() {
                var date = $filter('date')(ds.form.date, 'yyyy-MM-dd');
                return {
                    start_date : ds.form.start_time ? date + ' ' + ds.form.start_time.value : '',
                    end_date   : ds.form.end_time ? date + ' ' + ds.form.end_time.value : ''
                };
            }
        };

        return ds;
    });

    /**
     * Controller for "create/edit appointment" dialog form.
     */
    module.controller('appointmentDialogCtrl', function($scope, $element, dataSource) {
        // Set up initial data.
        $scope.loading = true;
        $scope.$week_calendar = null;
        $scope.calendar_mode = 'week';
        // Set up data source.
        $scope.dataSource = dataSource;
        $scope.form = dataSource.form;  // shortcut
        // Populate data source.
        dataSource.loadData().then(function() {
            $scope.loading = false;
        });
        // Error messages.
        $scope.errors = {};
        // Id of the staff whos events are currently being edited/created.
        var current_staff_id = null;

        /**
         * Prepare the form for new event.
         *
         * @param int  staff_id
         * @param Date start_date
         */
        $scope.configureNewForm = function(staff_id, start_date) {
            jQuery.extend($scope.form, {
                id         : null,
                staff      : dataSource.findStaff(staff_id),
                service    : null,
                date       : start_date,
                start_time : dataSource.findTime(start_date),
                end_time   : null,
                customers  : [],
                custom_fields: [],
                email_notification : null
            });
            $scope.errors = {};
            dataSource.setEndTimeBasedOnService();
            current_staff_id = staff_id;

            $scope.reInitChosen();
        };

        /**
         * Prepare the form for editing event.
         */
        $scope.configureEditForm = function(appointment_id, staff_id, start_date, end_date) {
            $scope.loading = true;
            jQuery.post(
                ajaxurl,
                { action : 'ab_get_data_for_appointment', id : appointment_id },
                function(response) {
                    $scope.$apply(function($scope) {
                        if (response.status === 'ok') {
                            jQuery.extend($scope.form, {
                                id         : appointment_id,
                                staff      : $scope.dataSource.findStaff(staff_id),
                                service    : $scope.dataSource.findService(staff_id, response.data.service_id),
                                date       : start_date,
                                start_time : $scope.dataSource.findTime(start_date),
                                end_time   : $scope.dataSource.findTime(end_date),
                                customers  : [],
                                custom_fields : []
                            });

                            $scope.reInitChosen();

                            response.data.customers.forEach(function(item, i, arr){
                                $scope.form.customers.push($scope.dataSource.findCustomer(item))
                            });

                            if (response.data.custom_fields) {
                                response.data.custom_fields.forEach(function (item, i, arr) {
                                    $scope.form.custom_fields.push(item);
                                });
                            }
                        }
                        $scope.loading = false;
                    });
                },
                'json'
            );
            $scope.errors = {};
            current_staff_id = staff_id;
        };

        var checkTimeInterval = function() {
            var dates = $scope.dataSource.getStartAndEndDates();
            jQuery.get(
                ajaxurl,
                {
                    action         : 'ab_check_appointment_date_selection',
                    start_date     : dates.start_date,
                    end_date       : dates.end_date,
                    appointment_id : $scope.form.id,
                    staff_id       : $scope.form.staff ? $scope.form.staff.id : null,
                    service_id     : $scope.form.service ? $scope.form.service.id : null
                },
                function(response){
                    $scope.$apply(function($scope) {
                        $scope.errors = response;
                    });
                },
                'json'
            );
        };

        $scope.onServiceChange = function() {
            $scope.dataSource.setEndTimeBasedOnService();
            $scope.reInitChosen();
            checkTimeInterval();
        };

        $scope.onStartTimeChange = function() {
            $scope.dataSource.setEndTimeBasedOnService();
            checkTimeInterval();
        };

        $scope.onEndTimeChange = function() {
            checkTimeInterval();
        };

        $scope.processForm = function() {
            $scope.loading = true;
            var dates = $scope.dataSource.getStartAndEndDates(),
                customers = [],
                custom_fields = [];

            $scope.form.customers.forEach(function(item, i, arr){
                customers.push(item.id);
            });

            $scope.form.custom_fields.forEach(function(item, customer_id, arr){
                custom_fields.push(item);
            });

            jQuery.post(
                ajaxurl,
                {
                    action      : 'ab_save_appointment_form',
                    id          : $scope.form.id,
                    staff_id    : $scope.form.staff ? $scope.form.staff.id : null,
                    service_id  : $scope.form.service ? $scope.form.service.id : null,
                    start_date  : dates.start_date,
                    end_date    : dates.end_date,
                    customers   : JSON.stringify(customers),
                    custom_fields   : JSON.stringify(custom_fields),
                    email_notification : $scope.form.email_notification
                },
                function (response) {
                    $scope.$apply(function($scope) {
                        if (response.status === 'ok') {
                            if ($scope.$week_calendar) {
                                if ($scope.calendar_mode === 'day' || current_staff_id === response.data.userId) {
                                    // Update/create event in current calendar when:
                                    //  - current view mode is "day"
                                    //  OR
                                    //  - ID of event owner matches the ID of active staff ("week" mode)
                                    $scope.$week_calendar.weekCalendar('updateEvent', response.data);
                                } else {
                                    // Else switch to the event owner tab ("week" mode).
                                    jQuery('li.ab-staff-tab-' + response.data.userId).click();
                                }
                            }
                            // Close the dialog.
                            $element.dialog('close');
                        } else {
                            $scope.errors = response.errors;
                        }
                        $scope.loading = false;
                    });
                },
                'json'
            );
        };

        // On 'Cancel' button click.
        $scope.closeDialog = function() {
            // Close the dialog.
            $element.dialog('close');
        };

        $scope.reInitChosen = function(){
            jQuery('#chosen')
                .chosen('destroy')
                .chosen({
                    search_contains     : true,
                    width               : '400px',
                    max_selected_options: dataSource.form.service ? dataSource.form.service.capacity : 0
                });
        };

        /**************************************************************************************************************
         * New customer                                                                                               *
         **************************************************************************************************************/

        /**
         * Create new customer.
         * @param customer
         */
        $scope.createCustomer = function(customer) {
            // Add new customer to the list.
            var new_customer = {id : customer.id.toString(), name : customer.name};

            if (customer.email || customer.phone){
                new_customer.name += ' (' + [customer.email, customer.phone].filter(Boolean).join(', ') + ')';
            }

            dataSource.data.customers.push(new_customer);
            $scope.form.custom_fields.push({ customer_id: customer.id, fields: customer.custom_fields });

            // Make it selected.
            if (!dataSource.form.service || dataSource.form.customers.length < dataSource.form.service.capacity){
                dataSource.form.customers.push(new_customer);
            }

            setTimeout(function() { jQuery("#chosen").trigger("chosen:updated"); }, 0);
        };

        $scope.removeCustomer = function(item) {
            $scope.form.customers.splice($scope.form.customers.indexOf(item), 1);

            $scope.form.custom_fields.forEach(function(customer, i, arr){
                if (customer.customer_id == item.id) {
                    delete $scope.form.custom_fields[i];
                }
            });
        };

        /**************************************************************************************************************
         * Custom fields                                                                                              *
         **************************************************************************************************************/

        $scope.editCustomFields = function(customer) {
            // get the fields of this custom form
            var fields = [];
            $scope.form.custom_fields.forEach(function(item, i, arr) {
                if (item.customer_id == customer.id) {
                    fields = item.fields;
                }
            });

            var $form = jQuery('#ab_custom_fields_dialog form');
            $form.find('input.ab-custom-field:text, textarea.ab-custom-field, select.ab-custom-field').val('');
            $form.find('input.ab-custom-field:checkbox, input.ab-custom-field:radio').prop('checked', false);

            jQuery.each(fields, function(key, field) {
                var $field = $form.find('.ab-formField[data-id="' + field.id + '"]');
                switch ($field.data('type')) {
                    case 'checkboxes':
                        jQuery.each(field.value, function(key, value) {
                            $field.find('.ab-custom-field').filter(function() {
                                return this.value == value;
                            }).prop('checked', true);
                        });
                        break;
                    case 'radio-buttons':
                        $field.find('.ab-custom-field').filter(function() {
                            return this.value == field.value;
                        }).prop('checked', true);
                        break;
                    default:
                        $field.find('.ab-custom-field').val(field.value);
                        break;
                }
            });

            // this is used in SaveCustomFields()
            $scope.edit_customer = customer;

            jQuery('#ab_custom_fields_dialog').modal({show:true, backdrop: false});
        };

        $scope.saveCustomFields = function() {
            var result  = [];
            var $fields = jQuery('#ab_custom_fields_dialog .ab-formField');

            $fields.each(function() {
                var $this = jQuery(this);
                var value;
                switch ($this.data('type')) {
                    case 'checkboxes':
                        value = [];
                        $this.find('.ab-custom-field:checked').each(function() {
                            value.push(this.value);
                        });
                        break;
                    case 'radio-buttons':
                        value = $this.find('.ab-custom-field:checked').val();
                        break;
                    default:
                        value = $this.find('.ab-custom-field').val();
                        break;
                }
                result.push({ id: $this.data('id'), value: value });
            });

            $scope.form.custom_fields.forEach(function(customer, i, arr) {
                if (customer.customer_id == $scope.edit_customer.id) {
                    delete $scope.form.custom_fields[i];
                }
            });

            $scope.form.custom_fields.push({ customer_id: $scope.edit_customer.id, fields: result });

            jQuery('#ab_custom_fields_dialog').modal('hide');
        };

        /**
         * Datepicker options.
         */
        $scope.dateOptions = {
            dateFormat      : 'M, dd yy',
            dayNamesMin     : BooklyL10n['shortDays'],
            monthNames      : BooklyL10n['longMonths'],
            monthNamesShort : BooklyL10n['shortMonths']
        };
    });

    /**
     * Directive for slide up/down.
     */
    module.directive('mySlideUp', function() {
        return function(scope, element, attrs) {
            element.hide();
            // watch the expression, and update the UI on change.
            scope.$watch(attrs.mySlideUp, function(value) {
                if (value) {
                    element.delay(0).slideDown();
                } else {
                    element.slideUp();
                }
            });
        };
    });

    /**
     * Directive for chosen.
     */
    module.directive('chosen',function($timeout) {
        var linker = function(scope,element,attrs) {
            scope.$watch(attrs['chosen'], function() {
                element.trigger("chosen:updated");
            });

            scope.$watchCollection(attrs['ngModel'], function() {
                $timeout(function() {
                    element.trigger("chosen:updated");
                });
            });

            scope.reInitChosen();
        };

        return {
            restrict:'A',
            link: linker
        };
    });

    /**
     * Directive for Popover jQuery plugin.
     */
    module.directive('popover', function() {
        return function(scope, element, attrs) {
            element.popover({
                trigger : 'hover',
                content : attrs.popover,
                html    : true
            });
        };
    });

})();

var showAppointmentDialog = function(appointment_id, staff_id, start_date, end_date, calendar, mode) {
    var $scope = angular.element(document.getElementById('ab_appointment_dialog')).scope(),
        title  = null;
    $scope.$apply(function($scope){
        $scope.$week_calendar = calendar;
        $scope.calendar_mode = mode;
        if (appointment_id) {
            $scope.configureEditForm(appointment_id, staff_id, start_date, end_date);
            title = BooklyL10n['edit_appointment'];
        } else {
            $scope.configureNewForm(staff_id, start_date);
            title = BooklyL10n['new_appointment'];
        }
    });
    jQuery('#ab_appointment_dialog').dialog({
        width: 700,
        position: jQuery.ui.version < '1.11' ? ['center', 150] : { at: 'center center-150' },
        modal: true,
        dialogClass: 'ab-appointment-popup',
        title: title
    });
};