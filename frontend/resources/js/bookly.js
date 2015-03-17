(function($) {
    window.bookly = function(options) {
        var container   = $('#ab-booking-form-' + options.form_id);
        var today       = new Date();
        var Options     = $.extend(options, {
            skip_first_step : (
                options.attributes.hide_categories &&
                options.attributes.category_id &&
                options.attributes.hide_services &&
                options.attributes.service_id &&
                options.attributes.hide_staff_members &&
                options.attributes.staff_member_id &&
                options.attributes.hide_date_and_time
            ),
            skip_available  : options.attributes.hide_date_and_time,
            skip_service    : options.attributes.hide_categories
                && options.attributes.category_id
                && options.attributes.hide_services
                && options.attributes.service_id
                && options.attributes.hide_staff_members
                && options.attributes.staff_member_id,
            date_from_object    : {}
        });
        var BookingData = {
            form_id     : null,
            action      : 'ab_session_save',
            service_id  : null,
            staff_ids   : [],
            category_id : null,
            date_from   : null,
            time_from   : null,
            time_to     : null,
            days        : []
        };

        // initialize
        if (Options.is_finished) {
            fifthStep();
        } else {
            firstStep();
        }

        //
        function firstStep() {

            if (Options.is_cancelled) {
                fourthStep();

            } else if (Options.is_finished) {
                fifthStep();

            } else {
                $.ajax({
                    url         : Options.ajaxurl,
                    data        : { action: 'ab_render_service', form_id: Options.form_id, time_zone_offset: today.getTimezoneOffset() },
                    dataType    : 'json',
                    xhrFields   : { withCredentials: true },
                    success     : function (response) {
                        if (response.status == 'success') {
                            container.html(response.html);

                            var $select_category  = $('.ab-select-category', container),
                                $select_service   = $('.ab-select-service', container),
                                $select_staff     = $('.ab-select-employee', container),
                                $date_from        = $('.ab-date-from', container),
                                $week_day         = $('.ab-week-day', container),
                                $select_time_from = $('.ab-select-time-from', container),
                                $select_time_to   = $('.ab-select-time-to', container),
                                $service_error    = $('.ab-select-service-error', container),
                                $next_step        = $('.ab-next-step', container),
                                $mobile_next_step = $('.ab-mobile-next-step', container),
                                $mobile_prev_step = $('.ab-mobile-prev-step', container),
                                categories        = response.categories,
                                services          = response.services,
                                staff             = response.staff
                            ;

                            // Overwrite attributes if necessary.
                            if (response.attributes) {
                                Options.attributes.category_id = null;
                                Options.attributes.service_id = response.attributes.service_id;
                                Options.attributes.staff_member_id = response.attributes.staff_member_id;
                            }

                            $date_from.pickadate({
                                min             : Options.date_min || true,
                                clear           : false,
                                today           : BooklyL10n.today,
                                weekdaysShort   : BooklyL10n.days,
                                monthsFull      : BooklyL10n.months,
                                labelMonthNext  : BooklyL10n.nextMonth,
                                labelMonthPrev  : BooklyL10n.prevMonth,
                                firstDay        : Options.start_of_week,
                                onSet           : function(timestamp) {
                                    var date = new Date(timestamp);
                                    // Checks appropriate day of the week
                                    $('.ab-week-day[value="' + (date.getDay() + 1) + '"]:not(:checked)', container).attr('checked', true).trigger('change');
                                    BookingData.date_from = this.get('select', 'yyyy-m-dd');
                                    Options.date_from_object = this.get('select');
                                },
                                onRender        : function() {
                                    BookingData.date_from = this.get('select', 'yyyy-m-dd');
                                    Options.date_from_object = this.get('select');
                                },
                                onStart         : function() {
                                    if (Options.date_from_object) {
                                        this.set('select', Options.date_from_object);
                                    }
                                }
                            });

                            // fill the selects
                            setSelect($select_category, categories);
                            setSelect($select_service, services);
                            setSelect($select_staff, staff);

                            // Category select change
                            $select_category.on('change', function() {
                                $.extend(BookingData, {
                                    service_id  : null,
                                    staff_ids   : [],
                                    category_id : $(this).val()
                                });

                                // filter the services and staff
                                // if service or staff is selected, leave it selected
                                if ($(this).val()) {
                                    setSelect($select_service, categories[$(this).val()].services);
                                    setSelect($select_staff, categories[$(this).val()].staff, true);
                                // show all services and staff
                                // if service or staff is selected, reset it
                                } else {
                                    setSelect($select_service, services);
                                    setSelect($select_staff, staff);
                                }
                            });

                            // Service select change
                            $select_service.on('change', function() {
                                BookingData.service_id = $(this).val();

                                // select the category
                                // filter the staffs by service and categories
                                // show staff with price
                                // if staff selected, leave it selected
                                // if staff not selected, select all (for ajax request)
                                if ($(this).val()) {
                                    BookingData.category_id = services[$(this).val()].category_id;
                                    $select_category.val(BookingData.category_id);

                                    var staffs = {};
                                    if (BookingData.category_id) {
                                        $.map(services[$(this).val()].staff, function(st) {
                                            if (staff[st.id].categories.hasOwnProperty(BookingData.category_id)) {
                                                staffs[st.id] = st;
                                            }
                                        });
                                    } else {
                                        staffs = services[$(this).val()].staff;
                                    }
                                    setSelect($select_staff, staffs, true);

                                    var staff_ids = [];
                                    $.map(services[$(this).val()].staff, function(st) { staff_ids.push(st.id); });
                                    if (!$select_staff.val() || BookingData.staff_ids.length != 1 || $.inArray(BookingData.staff_ids[0], staff_ids) == -1 ) {
                                        BookingData.staff_ids = staff_ids;
                                    }
                                // filter staff by category
                                } else {
                                    if (BookingData.category_id) {
                                        setSelect($select_staff, categories[BookingData.category_id].staff, true);
                                    } else {
                                        setSelect($select_staff, staff, true);
                                    }
                                }
                            });

                            // Staff select change
                            $select_staff.on('change', function() {
                                if (!this.value) {
                                    BookingData.staff_ids = [];
                                    if (BookingData.service_id) {
                                        $.map(services[BookingData.service_id].staff, function (st) {
                                            BookingData.staff_ids.push(st.id);
                                        });
                                    }
                                }else{
                                    BookingData.staff_ids = [$(this).val()];
                                }

                                // select the category
                                // filter services by staff and category
                                // if service selected, leave it
                                if ($(this).val()) {
                                    var services_a = {};
                                    if (BookingData.category_id) {
                                        $.map(staff[$(this).val()].services, function(st) {
                                            if (services[st.id].category_id == BookingData.category_id) {
                                                services_a[st.id] = st;
                                            }
                                        });
                                    } else {
                                        services_a = staff[$(this).val()].services;
                                    }
                                    setSelect($select_service, services_a, true);

                                // filter services by category
                                } else {
                                    if (BookingData.category_id) {
                                        setSelect($select_service, categories[BookingData.category_id].services, true);
                                    } else {
                                        setSelect($select_service, services, true);
                                    }
                                }
                            });

                            // Init
                            $.extend(BookingData, {
                                service_id : $select_service.val(),
                                time_from  : $select_time_from.val(),
                                time_to    : $select_time_to.val(),
                                days       : [],
                                form_id    : Options.form_id,
                                staff_ids  : []
                            });

                            // Category
                            if (Options.attributes.category_id) {
                                $select_category.val(Options.attributes.category_id).trigger('change');
                            }
                            // Services
                            if (Options.attributes.service_id) {
                                $select_service.val(Options.attributes.service_id).trigger('change');
                            }
                            // Employee
                            if (Options.attributes.staff_member_id) {
                                $select_staff.val(Options.attributes.staff_member_id).trigger('change');
                            }

                            hideByAttributes();

                            $('.ab-week-day:checked', container).each(function () {
                                BookingData.days.push($(this).val());
                            });

                            // clear hash
                            if (document.location.hash) {
                                document.location.href = document.location.href.split('#')[0];
                            }

                            var firstStepValidator = function(button_type) {
                                var valid           = true,
                                    $select_wrap    = $('.ab-select-service').parents('.ab-select-wrap'),
                                    $time_wrap_from = $('.ab-time-from').parents('.ab-select-wrap'),
                                    $time_wrap_to   = $('.ab-time-to').parents('.ab-select-wrap');

                                $service_error.hide();

                                // service validation
                                if (!$select_service.val()) {
                                    $select_wrap.not("[class*='ab-service-error']").addClass("ab-service-error");
                                    $service_error.show();
                                    valid = false;
                                }

                                // date validation
                                $date_from.css('borderColor', $date_from.val() ? '' : 'red');
                                if (!$date_from.val()) {
                                    valid = false;
                                }

                                // time validation
                                if (button_type !== 'mobile' && $select_time_from.val() == $select_time_to.val()) {
                                    $time_wrap_from.not("[class*='ab-service-error']").addClass("ab-service-error");
                                    $time_wrap_to.not("[class*='ab-service-error']").addClass("ab-service-error");
                                    $('.ab-select-time-error').show();
                                    valid = false;
                                }

                                // week days
                                if (!$('.ab-week-day:checked').length) {
                                    valid = false;
                                }

                                return valid;
                            };

                            // "Next" click
                            $next_step.on('click', function (e) {
                                e.preventDefault();

                                if (firstStepValidator('simple')) {
                                    if (!BookingData.service_id) {
                                        BookingData.service_id = $select_service.val();
                                    }

                                    var ladda = Ladda.create(this);
                                    ladda.start();

                                    $.ajax({
                                        url  : Options.ajaxurl,
                                        data : BookingData,
                                        dataType : 'json',
                                        xhrFields : { withCredentials: true },
                                        success : function (response) {
                                            secondStep();
                                        }
                                    });
                                }
                            });

                            //
                            $mobile_next_step.on('click', function () {
                                if (firstStepValidator('mobile')) {

                                    if (Options.skip_available) {
                                        var ladda = Ladda.create(this);
                                        ladda.start();
                                        $('.ab-mobile-step_2 .ab-next-step', container).trigger('click');
                                    } else {
                                        $(this).parents('.ab-mobile-step_1', container).hide();
                                        $('.ab-mobile-step_2', container).show();
                                        if (Options.skip_service) {
                                            $('.ab-mobile-prev-step', container).attr("style", "display: none !important");
                                        }
                                    }
                                }

                                return false;
                            });

                            //
                            $mobile_prev_step.on('click', function () {
                                $('.ab-mobile-step_1', container).show();
                                $('.ab-mobile-step_2', container).hide();

                                if ($select_service.val()) {
                                    $('.ab-select-service').parents('.ab-select-wrap').removeClass('ab-service-error');
                                }
                                return false;
                            });

                            // change the week days
                            $week_day.on('change', function () {
                                var self    = $(this),
                                    value   = $(this).val();

                                if (self.is(':checked')) {
                                    self.parent().not("[class*='active']").addClass('active');
                                    BookingData.days.push(value);
                                } else {
                                    self.parent().removeClass('active');
                                    BookingData.days.splice($.inArray(value, BookingData.days), 1);
                                }
                            });

                            // time from
                            $select_time_from.on('change', function () {
                                var start_time       = $(this).val(),
                                    end_time         = $select_time_to.val(),
                                    $last_time_entry = $('option:last', $select_time_from);

                                $select_time_to.empty();

                                // case when we click on the not last time entry
                                if ($select_time_from[0].selectedIndex < $last_time_entry.index()) {
                                    // clone and append all next "time_from" time entries to "time_to" list
                                    $('option', this).each(function () {
                                        if ($(this).val() > start_time) {
                                            $select_time_to.append($(this).clone());
                                        }
                                    });
                                // case when we click on the last time entry
                                } else {
                                    $select_time_to.append($last_time_entry.clone()).val($last_time_entry.val());
                                }

                                var first_value =  $('option:first', $select_time_to).val();
                                $select_time_to.val(end_time >= first_value ? end_time : first_value);

                                $.extend(BookingData, {
                                    time_from : start_time,
                                    time_to   : $select_time_to.val()
                                });
                            });

                            //
                            $select_time_to.on('change', function () {
                                BookingData.time_to = $(this).val();
                            });

                            // #11681: if time_to wasn't selected by default
                            if ( ! $select_time_to.find('option:selected').length ) {
                                $select_time_to.find('option:first').attr('selected', 'selected');
                            }

                            if (Options.skip_service) {
                                $mobile_next_step.trigger('click');
                            }

                            if (Options.skip_first_step) {
                                $next_step.trigger('click');
                            }
                        }
                    } // ajax success
                }); // ajax
            }
        }

        //
        function secondStep(time_is_busy) {

            $.ajax({
                url         : Options.ajaxurl,
                data :      { action: 'ab_render_time', form_id: Options.form_id },
                dataType    : 'json',
                xhrFields   : { withCredentials: true },
                success     : function (response) {

                    // No time is available. The user selected completely non-working days.
                    if (response.status === 'error') {
                        container.html(response.html);
                        $('.ab-to-first-step', container).on('click', function(e) {
                            e.preventDefault();
                            var ladda = Ladda.create(this);
                            ladda.start();
                            firstStep();
                        });

                    // There are available time slots.
                    } else if (response.status == 'success') {
                        container.html(response.html);

                        if (time_is_busy) {
                            container.prepend(time_is_busy);
                        }

                        var $next_button    = $('.ab-time-next', container),
                            $prev_button    = $('.ab-time-prev', container),
                            $back_button    = $('.ab-to-first-step', container),
                            $list           = $('.ab-second-step', container),
                            $columnizer_wrap = $('.ab-columnizer-wrap', $list),
                            $columnizer     = $('.ab-columnizer', $columnizer_wrap),
                            $column,
                            $screen,
                            $current_screen,
                            $button,
                            screen_index    = 0,
                            $screens,
                            item_height     = 35,
                            column_width    = 127,
                            columns         = 0,
                            $current_booking_form = $('#ab-booking-form-' + options.form_id),
                            screen_width    = $current_booking_form.width(),
                            window_height   = $(window).height(),
                            columns_per_screen = parseInt(screen_width / column_width, 10),
                            has_more_slots  = response.has_more_slots || false
                        ;

                        if (Options.skip_first_step) {
                            $back_button.hide();
                        }

                        if (window_height < 4 * item_height) {
                            window_height = 4 * item_height;
                        }
                        else if (window_height > 8 * item_height) {
                            window_height = 10 * item_height;
                        }

                        var items_per_column = parseInt(window_height / item_height, 10);
                        $columnizer_wrap.css({ height: (items_per_column * item_height + 25) });


                        function createColumns() {
                            var $buttons =  $('> button', $columnizer);
                            var max_length = $buttons.length > items_per_column && has_more_slots ? items_per_column : 0;

                            while ($buttons.length > max_length) {
                                $column = $('<div class="ab-column" />');

                                var items_in_column = items_per_column;
                                if (columns % columns_per_screen == 0 && !$buttons.eq(0).hasClass('ab-available-day')) {
                                    // If this is the first column of a screen and the first slot in this column is not day
                                    // then put 1 slot less in this column because createScreens adds 1 more
                                    // slot to such columns.
                                    -- items_in_column;
                                }

                                for (var i = 0; i < items_in_column; ++ i) {
                                    if (i + 1 == items_in_column && $buttons.eq(0).hasClass('ab-available-day')) {
                                        // Skip the last slot if it is day.
                                        break;
                                    }
                                    $button = $($buttons.splice(0, 1));
                                    if (i == 0) {
                                        $button.addClass('ab-first-child');
                                    } else if (i + 1 == items_in_column) {
                                        $button.addClass('ab-last-child');
                                    }
                                    $column.append($button);
                                }
                                $columnizer.append($column);
                                ++ columns;
                            }
                        }


                        function createOneColumnsDay() {
                            var $buttons        = $('> button', $columnizer);
                            var max_height      = 0;
                            var column_height   = 0;

                            while ($buttons.length > 0) {

                                // create column
                                if ($buttons.eq(0).hasClass('ab-available-day')) {
                                    column_height = 1;
                                    $column = $('<div class="ab-column" />');
                                    $button = $($buttons.splice(0, 1));
                                    $button.addClass('ab-first-child');
                                    $column.append($button);

                                    // add slots in column
                                } else {
                                    column_height++;
                                    $button = $($buttons.splice(0, 1));
                                    // if is last in column
                                    if (!$buttons.length || $buttons.eq(0).hasClass('ab-available-day')) {

                                        $button.addClass('ab-last-child');
                                        $column.append($button);

                                        $columnizer.append($column);
                                        columns++;

                                    } else {
                                        $column.append($button);
                                    }
                                }
                                // calculate max height of columns
                                if (column_height > max_height) {
                                    max_height = column_height;
                                }
                            }
                            $columnizer_wrap.css({ height: (max_height * (item_height + 2.5)) });
                        }

                        function createScreens() {
                            var $columns = $('> .ab-column', $columnizer);

                            if (container.width() < 2 * column_width) {
                                screen_width = 2 * column_width;
                            }

                            if ($columns.length < columns_per_screen) {
                                columns_per_screen = $columns.length;
                            }

                            while ($columns.length && $columns.length >= (has_more_slots ? columns_per_screen : 0)) {
                                $screen = $('<div class="ab-time-screen"/>');
                                for (var i = 0; i < columns_per_screen; i++) {
                                    $column = $($columns.splice(0, 1));
                                    if (i == 0) {
                                        $column.addClass('ab-first-column');
                                        var $first_button_in_first_column = $column.filter('.ab-first-column')
                                            .find('.ab-first-child');
                                        // in first column first button is time
                                        if (!$first_button_in_first_column.hasClass('ab-available-day')) {
                                            var curr_date = $first_button_in_first_column.data('date'),
                                                $curr_date = $('button.ab-available-day[value="' + curr_date + '"]:last');
                                            // copy dateslot to first column
                                            $column.prepend($curr_date.clone());
                                        }
                                    }
                                    $screen.append($column);
                                }
                                $columnizer.append($screen);
                            }
                            $screens = $('.ab-time-screen', $columnizer);
                        }

                        function onTimeSelectionHandler(e, el) {
                            e.preventDefault();
                            var data = {
                                    action: 'ab_session_save',
                                    appointment_datetime: el.val(),
                                    staff_ids: [el.data('staff_id')],
                                    form_id: options.form_id
                                },
                                ladda = Ladda.create(el[0]);

                            ladda.start();
                            $.ajax({
                                type : 'POST',
                                url  : options.ajaxurl,
                                data : data,
                                dataType : 'json',
                                xhrFields : { withCredentials: true },
                                success : function (response) {
                                    thirdStep();
                                }
                            });
                        }

                        $next_button.on('click', function (e) {
                            e.preventDefault();
                            var last_date;
                            $prev_button.show();

                            if ($screens.eq(screen_index + 1).length) {
                                $columnizer.animate(
                                    { left: '-=' + $current_screen.width() },
                                    { duration: 800, complete: function () {
                                        if (has_more_slots || screen_index + 1 != $screens.length) {
                                            $next_button.show();
                                        }
                                        $prev_button.show();
                                        $back_button.show();
                                    } }
                                );
                                $current_screen = $screens.eq(++screen_index);

                                if (screen_index + 1 == $screens.length && !has_more_slots) {
                                    $next_button.hide();
                                }

                            // do ajax request when have the more slots
                            } else if (has_more_slots) {
                                $button     = $('> button:last', $columnizer);
                                last_date   = $button.length ? $button.val() : $('.ab-column:last > button:last', $columnizer).val();

                                // Render Next Time
                                var data = {
                                        action: 'ab_render_next_time',
                                        form_id: options.form_id,
                                        start_date: last_date
                                    },
                                    ladda = Ladda.create(document.querySelector('.ab-time-next'));

                                ladda.start();
                                $.ajax({
                                    type : 'POST',
                                    url  : options.ajaxurl,
                                    data : data,
                                    dataType : 'json',
                                    xhrFields : { withCredentials: true },
                                    success : function (response) {
                                        if (response.status == 'error') { // no available time
                                            $next_button.hide();
                                        }
                                        else if (response.status == 'success') { // if there are available time
                                            has_more_slots = response.has_more_slots;
                                            $columnizer.append(response.html);
                                            if (Options.day_one_column == 1) {
                                                createOneColumnsDay();
                                            } else {
                                                createColumns();
                                            }
                                            createScreens();
                                            $next_button.trigger('click');
                                            $('button.ab-available-hour').off('click').on('click', function (e) {
                                                e.preventDefault();
                                                onTimeSelectionHandler(e, $(this));
                                            });
                                        }
                                        ladda.stop();
                                    }
                                });
                            }
                        });

                        $prev_button.on('click', function () {
                            $current_screen = $screens.eq(--screen_index);
                            $columnizer.animate({ left: '+=' + $current_screen.width() },
                                { duration: 800,  complete: function () {
                                    if (screen_index) {
                                        $prev_button.show();
                                    }
                                    $next_button.show();
                                    $back_button.show();
                                }});
                            if (screen_index === 0) {
                                $prev_button.hide();
                            }
                        });

                        $('button.ab-available-hour').off('click').on('click', function (e) {
                            e.preventDefault();
                            onTimeSelectionHandler(e, $(this));
                        });

                        $back_button.on('click', function (e) {
                            e.preventDefault();
                            var ladda = Ladda.create(this);
                            ladda.start();
                            firstStep();
                        });

                        if (Options.day_one_column == 1) {
                            createOneColumnsDay();
                        } else {
                            createColumns();
                        }
                        createScreens();
                        $current_screen = $screens.eq(0);

                        if (!has_more_slots && $screens.length == 1) {
                            $next_button.off('click');
                            $next_button.hide();
                        }

                        // fixing styles
                        $list.css({
                            'width': function() {
                                return parseInt($current_booking_form.width() / column_width, 10) * column_width;
                            },
                            'max-width': '2850px'
                        });

                        var hammertime = $list.hammer({ swipe_velocity: 0.1 });

                        hammertime.on('swipeleft', function() {
                            $next_button.trigger('click');
                        });

                        hammertime.on('swiperight', function() {
                            if ($prev_button.is(':visible')) {
                                $prev_button.trigger('click');
                            }
                        });

                    // The session doesn't contain data
                    } else {
                        firstStep();
                    }
                }
            });
        }

        //
        function thirdStep() {
            $.ajax({
                url         : Options.ajaxurl,
                data        : { action: 'ab_render_details', form_id: Options.form_id },
                dataType    : 'json',
                xhrFields   : { withCredentials: true },
                success     : function (response) {
                    if (response.status == 'success') {
                        container.html(response.html);

                        // Init
                        var $button_next    = $('.ab-to-fourth-step', container),
                            $back_button    = $('.ab-to-second-step', container),
                            $phone_field    = $('.ab-user-phone', container),
                            $email_field    = $('.ab-user-email', container),
                            $name_field     = $('.ab-full-name', container),
                            $phone_error    = $('.ab-user-phone-error', container),
                            $email_error    = $('.ab-user-email-error', container),
                            $name_error     = $('.ab-full-name-error', container),
                            $errors         = $('.ab-user-phone-error, .ab-user-email-error, .ab-full-name-error, div.ab-custom-field-error', container),
                            $fields         = $('.ab-user-phone, .ab-user-email, .ab-full-name, .ab-custom-field', container)
                        ;

                        $button_next.on('click', function(e) {
                            e.preventDefault();
                            var custom_fields_data = [],
                                checkbox_values
                            ;

                            $.each(Options.custom_fields, function(i, field) {
                                switch (field.type) {
                                    case 'text-field':
                                        custom_fields_data.push({
                                            id      : field.id,
                                            value   : $('input[name="ab-custom-field-' + field.id + '"]', container).val()
                                        });
                                        break;
                                    case 'textarea':
                                        custom_fields_data.push({
                                            id      : field.id,
                                            value   : $('textarea[name="ab-custom-field-' + field.id + '"]', container).val()
                                        });
                                        break;
                                    case 'checkboxes':
                                        if ($('input[name="ab-custom-field-' + field.id + '"][type=checkbox]:checked', container).length) {
                                            checkbox_values = [];
                                            $('input[name="ab-custom-field-' + field.id + '"][type=checkbox]:checked', container).each(function () {
                                                checkbox_values.push($(this).val());
                                            });
                                            custom_fields_data.push({
                                                id      : field.id,
                                                value   : checkbox_values
                                            });
                                        }
                                        break;
                                    case 'radio-buttons':
                                        if ($('input[name="ab-custom-field-' + field.id + '"][type=radio]:checked', container).length) {
                                            custom_fields_data.push({
                                                id      : field.id,
                                                value   : $('input[name="ab-custom-field-' + field.id + '"][type=radio]:checked', container).val()
                                            });
                                        }
                                        break;
                                    case 'drop-down':
                                        custom_fields_data.push({
                                            id      : field.id,
                                            value   : $('select[name="ab-custom-field-' + field.id + '"] > option:selected', container).val()
                                        });
                                        break;
                                }
                            });

                            var data = {
                                    action          : 'ab_session_save',
                                    form_id         : Options.form_id,
                                    name            : $name_field.val(),
                                    phone           : $phone_field.val(),
                                    email           : $email_field.val(),
                                    custom_fields   : JSON.stringify(custom_fields_data)
                                },
                                ladda = Ladda.create(this);

                            ladda.start();
                            $.ajax({
                                type        : 'POST',
                                url         : Options.ajaxurl,
                                data        : data,
                                dataType    : 'json',
                                xhrFields   : { withCredentials: true },
                                success     : function (response) {
                                    // Error messages
                                    $errors.empty();
                                    $fields.removeClass('ab-details-error');

                                    if (response.length == 0) {
                                        fourthStep();
                                    } else {
                                        ladda.stop();
                                        if (response.name) {
                                            $name_error.html(response.name);
                                            $name_field.addClass('ab-details-error');
                                        }
                                        if (response.phone) {
                                            $phone_error.html(response.phone);
                                            $phone_field.addClass('ab-details-error');
                                        }
                                        if (response.email) {
                                            $email_error.html(response.email);
                                            $email_field.addClass('ab-details-error');
                                        }
                                        if (response.custom_fields) {
                                            $.each(response.custom_fields, function(key, value) {
                                                $('.' + key + '-error', container).html(value);
                                                $('[name=' + key + ']', container).addClass('ab-details-error');
                                            });
                                        }
                                    }
                                }
                            });
                        });

                        $back_button.on('click', function (e) {
                            e.preventDefault();
                            var ladda = Ladda.create(this);
                            ladda.start();
                            secondStep();
                        });
                    }
                }
            });
        }

        //
        function fourthStep() {
            $.ajax({
                url         : Options.ajaxurl,
                data        : { action: 'ab_render_payment', form_id: Options.form_id },
                xhrFields   : { withCredentials: true },
                success     : function (response) {

                    // The session doesn't contain data or payment is disabled in Admin Settings
                    if (response.status == 'no-data') {
                        save();

                    } else {
                        container.html(response.html);

                        if (Options.is_cancelled) {
                            $('html, body')
                                .animate({
                                    scrollTop: $('#ab-booking-form-' + Options.form_id).offset().top - 65
                            }, 1000);

                            Options.is_cancelled = false;
                        }

                        var $local_pay              = $('.ab-local-payment', container),
                            $paypal_pay             = $('.ab-paypal-payment', container),
                            $authorizenet_pay       = $('.ab-authorizenet-payment', container),
                            $stripe_pay             = $('.ab-stripe-payment', container),
                            $local_pay_button       = $('.ab-local-pay-button', container),
                            $coupon_pay_button      = $('.ab-coupon-payment-button', container),
                            $paypal_pay_button      = $('.ab-paypal-payment-button', container),
                            $card_payment_button    = $('.ab-card-payment-button', container),
                            $back_button            = $('.ab-to-third-step', container),
                            $apply_coupon_button    = $('#apply-coupon', container),
                            $coupon_input           = $('input.ab-user-coupon', container),
                            $coupon_error           = $('.ab-coupon-error', container),
                            $coupon_info_text       = $('#ab-info-text-coupon', container),
                            $ab_payment_nav         = $('#ab-payment-nav', container),
                            $buttons                = $('.ab-paypal-payment-button,.ab-card-payment-button,form.ab-authorizenet,form.ab-stripe,.ab-local-pay-button', container)
                        ;

                        $local_pay.on('click', function () {
                            $buttons.hide();
                            $local_pay_button.show();
                        });

                        $paypal_pay.on('click', function () {
                            $buttons.hide();
                            $paypal_pay_button.show();
                        });

                        $authorizenet_pay.on('click', function () {
                            $buttons.hide();
                            $card_payment_button.show();
                            $('form.ab-authorizenet', container).show();
                        });

                        $stripe_pay.on('click', function () {
                            $buttons.hide();
                            $card_payment_button.show();
                            $('form.ab-stripe', container).show();
                        });

                        $apply_coupon_button.on('click', function(e) {
                            var ladda = Ladda.create(this);

                            ladda.start();
                            $coupon_error.text('');
                            $coupon_input.removeClass('ab-details-error');

                            var data = {
                                action  : 'ab_apply_coupon',
                                form_id : Options.form_id,
                                coupon  : $coupon_input.val()
                            };

                            $.ajax({
                                type        : 'POST',
                                url         : Options.ajaxurl,
                                data        : data,
                                dataType    : 'json',
                                xhrFields   : { withCredentials: true },
                                success     : function (response) {
                                    if (response.status == 'success') {
                                        $coupon_info_text.html(response.text);
                                        $coupon_input.replaceWith(data.coupon);
                                        $apply_coupon_button.replaceWith('✓');
                                        if (response.discount == 100) {
                                            $ab_payment_nav.hide();
                                            $buttons.hide();
                                            $coupon_pay_button.show('fast',function(){
                                                $('.ab-coupon-free').attr('checked','checked').val(data.coupon);
                                            });
                                        }
                                    }
                                    else if (response.status == 'error'){
                                        $coupon_error.html(response.error);
                                        $coupon_input.addClass('ab-details-error');
                                        $coupon_info_text.html(response.text);
                                    }
                                    ladda.stop();
                                },
                                error: function() {
                                    ladda.stop();
                                }
                            });
                        });

                        if ($coupon_input.val()) {
                            $apply_coupon_button.click();
                        }

                        $('.ab-final-step', container).on('click', function (e) {
                            var ladda = Ladda.create(this);

                            if ($('.ab-local-payment', container).is(':checked') || $(this).hasClass('ab-coupon-payment')) { // handle only if was selected local payment !
                                e.preventDefault();
                                ladda.start();
                                save();

                            } else if ($('.ab-authorizenet-payment', container).is(':checked') || $('.ab-stripe-payment', container).is(':checked')) { // handle only if was selected AuthorizeNet payment !
                                var authorize   = $('.ab-authorizenet-payment', container).is(':checked');
                                var card_action = authorize ? 'ab_authorize_net_aim' : 'ab_stripe';
                                var card_form   = authorize ? 'ab-authorizenet' : 'ab-stripe';

                                e.preventDefault();
                                ladda.start();

                                var data = {
                                    action          : card_action,
                                    ab_card_number  : $('.' + card_form + ' input[name="ab_card_number"]', container).val(),
                                    ab_card_code    : $('.' + card_form + ' input[name="ab_card_code"]', container).val(),
                                    ab_card_month   : $('.' + card_form + ' select[name="ab_card_month"]', container).val(),
                                    ab_card_year    : $('.' + card_form + ' select[name="ab_card_year"]', container).val(),
                                    form_id         : Options.form_id
                                };

                                $.ajax({
                                    type        : 'POST',
                                    url         : Options.ajaxurl,
                                    data        : data,
                                    xhrFields   : { withCredentials: true },
                                    success     : function (response) {
                                        var _response;
                                        try {
                                            _response = JSON.parse(response);
                                        } catch (e) {}
                                        if (typeof _response === 'object') {
                                            var $response = $.parseJSON(response);

                                            if ($response.error){
                                                ladda.stop();
                                                $('.' + card_form + ' .ab-card-error').text($response.error);
                                            } else {
                                                Options.is_available = !!$response.state;
                                                fifthStep();
                                            }
                                        } else {
                                            ladda.stop();
                                        }
                                    }
                                });
                            } else if ($('.ab-paypal-payment', container).is(':checked')) {
                                ladda.start();
                                $(this).closest('form').submit();
                            }
                        });

                        $back_button.on('click', function (e) {
                            e.preventDefault();
                            var ladda = Ladda.create(this);
                            ladda.start();

                            thirdStep();
                        });
                    }
                }
            });
        }

        //
        function fifthStep() {
            $.ajax({
                url         : Options.ajaxurl,
                data        : { action : 'ab_render_complete', form_id : Options.form_id },
                xhrFields   : { withCredentials: true },
                success     : function (response) {
                    if (response.length != 0) {
                        var $response = $.parseJSON(response);

                        if (Options.is_available || Options.is_finished) {
                            if (Options.final_step_url) {
                                document.location.href = Options.final_step_url;
                            } else {
                                $response.step
                                    ? container.html($response.step + $response.state.success)
                                    : container.html($response.state.success)
                                ;

                                if (Options.is_finished) {
                                    $('html, body')
                                        .animate({
                                            scrollTop: $('#ab-booking-form-' + Options.form_id).offset().top - 65
                                        }, 1000);
                                }
                            }

                            Options.is_finished = false;
                        } else {
                            secondStep($response.state.error);
                        }
                    }
                }
            });
        }

        // =========== helpers ===================

        function hideByAttributes() {
            if (Options.skip_first_step) {
                $('.ab-first-step', container).hide();
            }
            if (Options.attributes.hide_categories && Options.attributes.category_id) {
                $('.ab-category', container).hide();
            }
            if (Options.attributes.hide_services && Options.attributes.service_id) {
                $('.ab-service', container).hide();
            }
            if (Options.attributes.hide_staff_members && Options.attributes.staff_member_id) {
                $('.ab-employee', container).hide();
            }
            if (Options.attributes.hide_date_and_time) {
                $('.ab-available-date', container).parent().hide();
            }
        }

        // insert data into select
        function setSelect($select, data, leave_selected) {
            var selected = $select.val();
            var reset    = true;
            // reset select
            $('option:not([value=""])', $select).remove();
            // and fill the new data
            var docFragment = document.createDocumentFragment();

            function valuesToArray(obj) {
                return Object.keys(obj).map(function (key) { return obj[key]; });
            }

            function compare(a, b) {
                if (parseInt(a.position) < parseInt(b.position))
                    return -1;
                if (parseInt(a.position) > parseInt(b.position))
                    return 1;
                return 0;
            }

            // sort select by position
            data = valuesToArray(data).sort(compare);

            $.each(data, function(id, object) {
                id = object.id;

                if (selected === id && leave_selected) {
                    reset = false;
                }
                var option = document.createElement('option');
                option.value = id;
                option.text = object.name;
                docFragment.appendChild(option);
            });
            $select.append(docFragment);
            // set default value of select
            $select.val(reset ? '' : selected);
        }

        //
        function save() {
            $.ajax({
                type        : 'POST',
                url         : Options.ajaxurl,
                xhrFields   : { withCredentials: true },
                data        : { action  : 'ab_save_appointment', form_id : Options.form_id }
            }).done(function(response) {
                var $response = $.parseJSON(response);
                Options.is_available = !!$response.state;
                fifthStep();
            });
        }
    }
})(jQuery);
