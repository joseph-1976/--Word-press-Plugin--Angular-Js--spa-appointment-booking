jQuery(function ($) {
    var $form                = $('#business-hours'),
        $all_tabs            = $('#ab_settings_company, #ab_settings_payments, #ab_settings_hours, #ab_settings_holidays, #ab_settings_purchase_code, #ab_settings_google_calendar, #ab_settings_general'),
        $all_forms           = $('#company-form, #payments-form, #hours-form, #holidays-form, #purchase-code-form, #general-form, #google-calendar-form'),
        $final_step_url      = $('input[name=ab_settings_final_step_url]'),
        $final_step_url_mode = $('#ab_settings_final_step_url_mode');

    $('.select_start', $form).on('change', function () {
        var $row = $(this).parent(),
            $end_select = $('.select_end', $row),
            $start_select = $(this);

        if ($start_select.val()) {
            $end_select.show();
            $('span', $row).show();

            var start_time = $start_select.val();

            $('span > option', $end_select).each(function () {
                $(this).unwrap();
            });

            // Hides end time options with value less than in the start time
            $('option', $end_select).each(function () {
                if ($(this).val() <= start_time) {
                    $(this).wrap("<span>").parent().hide();
                }
            });

            if (start_time >= $end_select.val()) {
                $('option:visible:first', $end_select).attr('selected', true);
            }
        } else { // OFF
            $end_select.hide();
            $('span', $row).hide();
        }
    }).each(function () {
        var $row = $(this).parent(),
            $end_select = $('.select_end', $row);

        $(this).data('default_value', $(this).val());
        $end_select.data('default_value', $end_select.val());

        // Hides end select for "OFF" days
        if (!$(this).val()) {
            $end_select.hide();
            $('span', $row).hide();
        }
    }).trigger('change');

    // Reset
    $('#ab-hours-reset', $form).on('click', function () {
        $('.select_start', $form).each(function () {
            $(this).val($(this).data('default_value'));
            $(this).trigger('click');
        });

        $('.select_end', $form).each(function () {
            $(this).val($(this).data('default_value'));
        });

        $('.select_start', $form).trigger('change');
    });

    // Tabs Onclick Handlers
    $all_tabs.on('click', function() {
        $('.ab-active').removeClass('ab-active');
        $(this).addClass('ab-active');
    });
    $('#ab_settings_company').on('click', function() {
        $all_forms.addClass('hidden');
        $('#company-form').removeClass('hidden');
    });
    $('#ab_settings_payments').on('click', function() {
        $all_forms.addClass('hidden');
        $('#payments-form').removeClass('hidden');
    });
    $('#ab_settings_hours').on('click', function() {
        $all_forms.addClass('hidden');
        $('#hours-form').removeClass('hidden');
    });
    $('#ab_settings_holidays').on('click', function() {
        $all_forms.addClass('hidden');
        $('#holidays-form').removeClass('hidden');
    });
    $('#ab_settings_purchase_code').on('click', function() {
        $all_forms.addClass('hidden');
        $('#purchase-code-form').removeClass('hidden');
    });
    $('#ab_settings_general').on('click', function() {
        $all_forms.addClass('hidden');
        $('#general-form').removeClass('hidden');
    });
    $('#ab_settings_google_calendar').on('click', function() {
        $all_forms.addClass('hidden');
        $('#google-calendar-form').removeClass('hidden');
    });

    // Company Tab
    $('#ab-delete-logo').on('click', function() {
        $('#ab-show-logo').hide(300).append('<input type="hidden" id="ab-remove-logo" name="ab_remove_logo" value="1" />');
    });
    $('#ab_settings_company_logo').on('change', function() { $('#ab-remove-logo').remove(); });

    $('#ab-settings-company-reset').on('click', function() {
        $('#ab-remove-logo').remove();
        $('#ab-show-logo').show(300);
    });

    //Payment Tab
    $('#ab_paypal_type').change(function() {
        $('.paypal_ec').toggle(this.value != 'disabled');
    }).change();

    $('#ab_authorizenet_type').change(function() {
        $('.authorizenet').toggle(this.value != 'disabled');
    }).change();

    $('#ab_stripe').change(function() {
        $('.ab-stripe').toggle(this.value == 1);
    }).change();

    $("#ab-payments-reset").on("click", function(event){
        event.preventDefault();
        $(this).closest('form').get(0).reset();
        $('#ab_paypal_type, #ab_authorizenet_type').change();
    });

    $('.ab-popover').popover({
        trigger : 'hover'
    });

    $('.ab-popover-ext').popover({
        content: function() {
            return $('#' + $(this).data('ext_id')).html();
        },
        html: true
    });

    if ($final_step_url.val()) { $final_step_url_mode.val(1); }
    $final_step_url_mode.change(function(){
        if (this.value == 0){
            $final_step_url.hide().val('');
        }else{
            $final_step_url.show();
        }
    });
});

