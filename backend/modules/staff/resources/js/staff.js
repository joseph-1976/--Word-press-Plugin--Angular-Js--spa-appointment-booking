jQuery(function($) {

    var $name_input = $('#ab-newstaff-fullname'),
        $email_input = $('#ab-newstaff-email'),
        $list_item_number = $('#ab-list-item-number'),
        $wp_user_select = $('#ab-newstaff-wpuser'),
        $staff_list = $('#ab-staff-list'),
        $staff_member = $('.ab-staff-member'),
        $edit_form = $('#ab-edit-staff-member'),
        $new_form = $('#ab-new-satff');

    function saveNewForm() {
        var data = { action: 'ab_create_staff' };
        data.wp_user_id = $wp_user_select.val();
        data.full_name = $name_input.val();
        data.email = $email_input.val();

        if (validateForm($new_form)) {
            if ($wp_user_select.val()) {
                $wp_user_select.find('option:selected').removeAttr('selected').wrap('<span>').parent().hide();
            }
            $.post(ajaxurl, data, function (response) {
                $staff_list.append(response);
                $list_item_number.text(parseInt($list_item_number.text(), 10) + 1);
                $('.ab-staff-member', $staff_list).click(function () {
                    loadEditForm.call(this);
                }).last().click();
            });
            $name_input.val('').focus();
        }
    }

    // Saves new staff form
    $('#ab-save-newstaff').bind('click', function () {
        saveNewForm();
    });

    // Save new staff on enter press
    $name_input.bind('keypress', function (e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) {
            saveNewForm();
        }
    });

    // Close new staff form on esc
    $new_form.bind('keypress', function (e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 27) {
            $('.ab-popup-wrapper').ab_popup('close');
        }
    });

    function loadEditForm() {
        // Marks selected element as active
        $(this).parent().find('.ab-active').removeClass('ab-active');
        $(this).addClass('ab-active');

        var staff_id = $(this).data('staff-id');
        var active_tab_id = $('ul.nav li.active a').attr('id');
        $.get(ajaxurl, { action: 'ab_edit_staff', id: staff_id }, function (response) {
            $edit_form.html(response);

            // Deletes staff member
            $('#ab-staff-delete').bind('click', function () {
                if (confirm('Are you sure?')) {
                    $.post(ajaxurl, { action: 'ab_delete_staff', id: staff_id }, function (response) {
                        $edit_form.html('');
                        $wp_user_select.children(':not(:first)').remove();
                        for (var i in response) {
                            $wp_user_select.append($('<option>').data('email', response[i].user_email).val(response[i].ID).text(response[i].display_name));
                        }
                        $('#ab-list-staff-' + staff_id).remove();
                        $list_item_number.text(parseInt($list_item_number.text(), 10) - 1);
                    });
                }
            });

            // Deletes staff avatar
            $('#ab-delete-avatar').bind('click', function () {
                $.post(ajaxurl, { action: 'ab_delete_staff_avatar', id: staff_id }, function () {
                    $('#ab-staff-avatar-image', $edit_form).remove();
                });
            });

            $('#ab-update-staff').bind('click', function (e) {
                if (!validateForm($edit_form)) {
                    e.preventDefault(e);
                    e.stopPropagation(e);
                }
            });

            $('#ab-staff-wpuser').bind('change', function () {
                if ($(this).val()) {
                    $('#ab-staff-full-name').val($(this).find(':selected').text());
                    $('#ab-staff-email').val($(this).find(':selected').data('email'));
                }
            });

            helpInit();

            var service_container  = $('#ab-staff-services-container'),
                details_container  = $('#ab-staff-details-container'),
                schedule_container = $('#ab-staff-schedule-container'),
                holidays_container = $('#ab-staff-holidays-container'),
                $schedule_form,
                services_form,
                tabs = $('.ab-list-link', $edit_form);

            // Opens services tab
            $('#ab-staff-services-tab').bind('click', function () {
                activateTab.call($(this));
                service_container.show();

                // Loads services form
                if (!service_container.children().length) {
                    $.post(ajaxurl, { action: 'ab_staff_services', id: staff_id }, function (response) {
                        service_container.html(response);
                        services_form = $('form', service_container);
                        
                        var auto_tick_checkboxes = function() {
                            // Handle 'select category' checkbox.
                            $('.ab-services-category .ab-category-checkbox').each(function() {
                                $(this).prop(
                                    'checked',
                                    $('.ab-category-services .ab-service-checkbox.ab-category-' + $(this).data('category-id') + ':not(:checked)').length == 0
                                );
                            });
                            // Handle 'select all services' checkbox.
                            $('#ab-all-services').prop(
                                'checked',
                                $('.ab-service-checkbox:not(:checked)').length == 0
                            );
                        };

                        // Select all services related to chosen category
                        $('.ab-category-checkbox', services_form).bind('click', function () {
                            $('.ab-category-services .ab-category-' + $(this).data('category-id')).prop('checked', $(this).is(':checked')).change();
                            auto_tick_checkboxes();
                        });

                        // Check and uncheck all services
                        $('#ab-all-services').bind('click', function () {
                            $('.ab-service-checkbox', services_form).prop('checked', $(this).is(':checked')).change();
                            $('.ab-category-checkbox').prop('checked', $(this).is(':checked'));
                        });

                        // Select service
                        $('.ab-service-checkbox', services_form).bind('click', function () {
                            var $this  = $(this);
                            var $price = $this.closest('li').find('.ab-price');
                            $this.is(':checked') ? $price.removeAttr('disabled') : $price.attr('disabled', true);
                            auto_tick_checkboxes();
                        });

                        $('.ab-service-checkbox',services_form).bind('change', function(){
                            var $input_fields = $('.ab-price[name="price['+$(this).val()+']"]').add('.ab-price[name="capacity['+$(this).val()+']"]');
                            $(this).is(':checked') ? $input_fields.removeAttr('disabled') : $input_fields.attr('disabled', true);
                        });

                        // Saves services
                        $('#ab-staff-services-update').bind('click', function () {
                            $('.spinner', services_form).fadeIn('slow');
                            $.post(ajaxurl, services_form.serialize(), function (response) {
                                $('.spinner', services_form).fadeOut('slow');
                            });
                        });

                        // After reset auto tick group checkboxes.
                        $('.ab-reset-form').on('click', function() {
                            setTimeout(auto_tick_checkboxes, 0);
                        });

                        auto_tick_checkboxes();
                    });
                }
            });

            // Opens schedule tab
            $('#ab-staff-schedule-tab').bind('click', function () {
                activateTab.call($(this));
                schedule_container.show();

                // Loads schedule list
                if (!schedule_container.children().length) {
                    $.post(ajaxurl, { action: 'ab_staff_schedule', id: staff_id }, function (response) {
                        // fill in the container
                        schedule_container.html(response);
                        $schedule_form = $('form', schedule_container);

                        // Saves initial values
                        $('.working-start', schedule_container).each(function () {
                            $(this).data('default_value', $(this).val());
                        });

                        $('.working-end', schedule_container).each(function () {
                            $(this).data('default_value', $(this).val());
                        });

                        // Resets initial values
                        $('#ab-schedule-reset').bind('click', function () {
                            $('.working-start', schedule_container).each(function () {
                                $(this).val($(this).data('default_value'));
                                $(this).trigger('change');
                            });

                            $('.working-end', schedule_container).each(function () {
                                $(this).val($(this).data('default_value'));
                            });

                            // reset breaks
                            $.ajax({
                                url     : ajaxurl,
                                type    : 'POST',
                                data    : { action : 'ab_reset_breaks', breaks : $.parseJSON($(this).attr('default-breaks'))},
                                success : function(response) {
                                    var days = $.parseJSON(response);
                                    for (var k in days) {
                                        var $content = $(days[k]);
                                        $("[data-id=" + k +"] .breaks", schedule_container).html($content);

                                        $content.find('.break-interval').bind('click', function(){
                                            markBreakIntervalActive.call($(this));
                                        });

                                        $content.find('.break-interval-wrapper .delete-break').bind('click', function(){
                                            deleteStaffScheduleBreakInterval.call($(this));
                                        });
                                        schedule_container.find('.ab-popup-wrapper').ab_popup();
                                    }
                                }
                            });
                        });

                        $('#ab-staff-schedule-update', schedule_container).bind('click', function () {
                            $('.spinner', $schedule_form).fadeIn('slow');
                            var data = {};
                            $('select.working-start, select.working-end, input:hidden', $schedule_form).each(function() {
                                data[this.name] = this.value;
                            });
                            $.post(ajaxurl, $.param(data), function (response) {
                                $('.spinner', $schedule_form).fadeOut('slow');
                                schedule_container.html('');
                                $('#ab-staff-schedule-tab').trigger('click');
                            });
                        });

                        // init "add break" functionality
                        schedule_container.find('.ab-popup-wrapper').ab_popup();
                        schedule_container.find('.ab-popup-trigger:not(.break-interval)').bind('click', function() {
                            var $row = $(this).parents('.staff-schedule-item-row').first(),
                                $working_start = $row.find('.working-start').val(),
                                $working_end = $row.find('.working-end').val();

                            // if the day is the working one - handle "add break" selected values
                            if ($working_start.length && $working_end.length) {
                                var $working_start_time  = $working_start.split(':'),
                                    $working_start_hours = parseInt($working_start_time[0], 10),
                                    $break_end           = $row.find('.add-break .break-end'),
                                    $break_end_hours     = $working_start_hours + 2;

                                if ($break_end_hours < 10) {
                                    $break_end_hours = '0' + $break_end_hours;
                                }
                                var $break_end_hours_str  = $break_end_hours + ':' + $working_start_time[1] + ':' + $working_start_time[2],
                                    $break_end_option     = $break_end.find('option[value="' + $break_end_hours_str + '"]'),
                                    $break_start          = $row.find('.add-break .break-start');

                                if ($break_end_option.length) {
                                    var $break_start_hours = $working_start_hours + 1;
                                    if ($break_start_hours < 10) {
                                        $break_start_hours = '0' + $break_start_hours;
                                    }
                                    var $break_start_hours_str = $break_start_hours + ':' + $working_start_time[1] + ':' + $working_start_time[2];

                                    $break_start.val($break_start_hours_str);
                                    $break_end.val($break_end_hours_str);
                                } else {
                                    // set defaults
                                    $break_start.val($break_start.data('default_value'));
                                    $break_end.val($break_end.data('default_value'));
                                }
                                $break_start.trigger('change');
                            }
                        });

                        // when the working day is disabled (working start time is set to "OFF")
                        // hide all the elements inside the row
                        schedule_container.find('.working-start').bind('change', function(){
                            var $this = $(this),
                                $row  = $this.parents('.staff-schedule-item-row');

                            if (!$this.val()) {
                                $row.find('.hide-on-non-working-day').hide();
                            } else {
                                $row.find('.hide-on-non-working-day').show();
                            }
                        });

                        $('.working-start', schedule_container).on('change', function () {
                            var $row = $(this).parents('.staff-schedule-item-row').first(),
                                $end_select = $('.working-end', $row),
                                start_time = $(this).val();

                            $('span > option', $end_select).each(function () {
                                if ( start_time < $(this).val()) {
                                    $(this).unwrap();
                                }
                            });

                            // Hides end time options with value less than in the start time
                            $('option', $end_select).each(function () {
                                if ($(this).val() <= start_time) {
                                    $(this).wrap("<span>").parent().hide();
                                }
                            });
                        }).trigger('change');

                        schedule_container.find('.ab-popup-wrapper .break-interval').bind('click', function(){
                            markBreakIntervalActive.call($(this));
                        });

                        schedule_container.find('.break-interval-wrapper .delete-break').bind('click', function(){
                            deleteStaffScheduleBreakInterval.call($(this));
                        });

                        schedule_container.find('.break-start').bind('change', function(){
                            var start = $(this);
                            var end   = start.parent().find('.break-end');
                            checkStaffScheduleBreakTimeRange(start,end);
                        }).trigger('change');

                        schedule_container.delegate('.ab-save-break', 'click', function() {
                            var $table                  = $(this).closest('table'),
                                $row                    = $table.parents('.staff-schedule-item-row').first(),
                                $break_list_label       = $row.find('.breaks-list-label span'),
                                $break_interval_wrapper = $table.parents('.break-interval-wrapper').first(),
                                $error                  = $table.parents('.ab-popup-wrapper').find('.error'),
                                $data                   = {
                                    action                 : 'ab_staff_schedule_handle_break',
                                    working_start          : $row.find('.working-start > option:selected').val(),
                                    working_end            : $row.find('.working-end > option:selected').val(),
                                    start_time             : $table.find('.break-start > option:selected').val(),
                                    end_time               : $table.find('.break-end > option:selected').val(),
                                    staff_schedule_item_id : $row.data('staff_schedule_item_id')
                                };

                            if ($break_interval_wrapper.data('break_id')) {
                                $data['break_id'] = $break_interval_wrapper.data('break_id');
                            }

                            $.post(
                                ajaxurl,
                                $data,
                                function (response) {
                                    if (response['success']) {
                                        if (response['item_content']) {
                                            var $new_break_interval_item = $(response['item_content']);
                                            $new_break_interval_item
                                              .hide()
                                              .appendTo($row.find('.breaks-list-content'))
                                              .fadeIn('slow');
                                            $new_break_interval_item.find('.break-interval').bind('click', function(){
                                                markBreakIntervalActive.call($(this));
                                            });
                                            $new_break_interval_item.find('.delete-break').bind('click', function(){
                                                deleteStaffScheduleBreakInterval.call($(this));
                                            });
                                            if ($break_list_label.is(':hidden')) {
                                                $break_list_label.fadeIn('slow');
                                            }
                                            schedule_container.find('.ab-popup-wrapper').ab_popup();
                                        } else if (response['new_interval']) {
                                            $break_interval_wrapper
                                              .find('.break-interval.active')
                                              .text(response['new_interval'])
                                              .removeClass('active');
                                        }
                                        $row.find('.ab-popup-wrapper').ab_popup('close');
                                    } else {
                                        $error.text(response['error_msg']);
                                        $error.slideDown();
                                        var t = setTimeout(function() {
                                            $error.hide();
                                            clearTimeout(t);
                                        }, 3000);
                                    }
                                },
                                'json'
                            );

                            return false;
                        });
                    });
                }
            });

            // Opens details tab
            $('#ab-staff-details-tab').bind('click', function () {
                activateTab.call($(this));
                details_container.show();
            });

            // Opens "Days off" tab
            $('#ab-staff-holidays-tab').bind('click', function () {
                activateTab.call($(this));
                holidays_container.show();

                if (!holidays_container.children().length) {
                    holidays_container.load(ajaxurl, { action: 'ab_staff_holidays', id: staff_id });
                }
            });

            function markBreakIntervalActive() {
                $(this).parents('.breaks-list-content').find('.break-interval').removeClass('.active');
                $(this).addClass('active');
            }

            function checkStaffScheduleBreakTimeRange( start, end )
            {
                var start_time = start.val();

                $('span > option', end).each(function () {
                    if (start_time < $(this).val()) {
                        $(this).unwrap();
                    }
                });

                // Hides end time options with value less than in the start time
                $('option', end).each(function () {
                    if ($(this).val() <= start_time) {
                        $(this).wrap("<span>").parent().hide();
                    }
                });
            }

            function deleteStaffScheduleBreakInterval() {
                var $break_interval_wrapper = $(this).parent(),
                    $row = $break_interval_wrapper.parents('.staff-schedule-item-row').first();
                if (confirm('Are you sure?')) {
                    $.post(ajaxurl, { action: 'ab_delete_staff_schedule_break', id: $break_interval_wrapper.data('break_id') }, function (response) {
                        $break_interval_wrapper.fadeOut(700, function() {
                            $(this).remove();
                            if (!$row.find('.break-interval-wrapper').length) {
                                $row.find('.breaks-list-label span').hide();
                            }
                        });
                    });
                }
            }

            function activateTab() {
//                $('.ab-active', tabs).removeClass('ab-active');
//                $(this).addClass('ab-active');
                $('.ab-staff-tab-content').not(':hidden').hide();
            }

            $('#' + active_tab_id).click();
        });
    }

    function helpInit() {
        // Popovers initialization.
        $('.ab-popover-ext').popover({
            trigger : 'hover',
            content: function() {
                return $('#' + $(this).data('ext_id')).html()
            },
            html: true
        });
    }

    $wp_user_select.bind('change', function () {
        if ($(this).val()) {
            $name_input.val($(this).find(':selected').text());
        }
    });

    $staff_member.click(function () {
        loadEditForm.call(this);
    });
    $staff_member.filter('[data-active="true"]').click();

    helpInit();

    var $staff = $('ul#ab-staff-list');
    $staff.sortable({
        axis   : 'y',
        handle : '.ab-handle',
        update : function( event, ui ) {
            var data = [];
            $staff.children('li').each(function() {
                var $this = $(this);
                var position = $this.data('staff-id');
                //alert($this.data('staff-id'));
                data.push(position);
            });
            $.ajax({
                type : 'POST',
                url  : ajaxurl,
                data : { action: 'ab_update_staff_position', position: data }
            });
        }
    });

});
