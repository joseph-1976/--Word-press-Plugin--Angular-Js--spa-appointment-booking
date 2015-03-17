jQuery(function($) {
    var $no_result = $('#ab_coupons_wrapper .no-result');

    // Promo codes list delegated events.
    $('#ab_coupons_wrapper')

        // On click on editable cell.
        .on('click', '.editable-cell div.displayed-value', function() {
            var $this = $(this);
            $this.hide().next('.value').show();
            // Fix FF accidental blur of input[type=number].
            setTimeout( function() { $this.next('.value').focus(); }, 100 );
        })

        // On blur of input in editable cell.
        .on('blur', '.editable-cell input.value', function() {
            var $this = $(this),
                field = $this.attr('name'),
                value = $this.attr('value'),
                id    = $this.parents('.coupon-row').attr('id');
            if (value) {
                var data = { action: 'ab_update_coupon_value', id: id };
                data[field] = value;

                $.post(ajaxurl,
                    data,
                    function(response) {
                        if (response.status == 'error'){
                            alert(response.text);
                        }else{
                            refreshList(response.text);
                        }
                    },
                    "json"
                );
            }
        })

        // On click on 'Add Coupon' button.
        .on('click', 'a.add-coupon', function(e) {
            e.preventDefault();
            $.post(ajaxurl, { action: 'ab_add_coupon' }, function(response) {
                refreshList(response);
            });
        })

        // On change in `select row` checkbox.
        .on('change', 'input.row-checker', function() {
            if ($(this).attr('checked')) {
                $(this).parents('.coupon-row').addClass('checked');
            } else {
                $(this).parents('.coupon-row').removeClass('checked');
            }
        })

        // On click on 'Delete' button.
        .on('click', 'a.delete', function(e){
            e.preventDefault();
            var $checked_rows = $('#coupons_list .coupon-row.checked');
            if (!$checked_rows.length) {
                alert(BooklyL10n.please_select_at_least_one_coupon);
                return false;
            }
            var data = { action: 'ab_remove_coupon' },
                row_ids = [];
            $checked_rows.each(function() {
                row_ids.push($(this).attr('id'));
            });

            data['coupon_ids[]'] = row_ids;
            $.post(ajaxurl, data, function() {
                $checked_rows.fadeOut(700, function() {
                    $(this).each(function() {
                        if ($(this).hasClass('last')) {
                            $(this).removeClass('last').prev().addClass('last');
                        }
                    });
                    $(this).remove();
                    $('#coupons_list .coupon-row').removeClass('even odd').each(function(index, value) {
                        if (index % 2) {
                            $(this).addClass('even');
                        } else {
                            $(this).addClass('odd');
                        }
                    });
                    if (!$('#coupons_list > tbody > tr').length) {
                        $('#coupons_list').hide();
                        $no_result.show();
                    }
                });
            });
        })
    ;

    function refreshList(response) {
        var $list = $('#ab-coupons-list');
        $list.html(response);
        doNotCloseDropDowns();

        if (response) {
            $no_result.hide();
        } else {
            $no_result.show();
        }
    }

    function doNotCloseDropDowns() {
        $('#ab-coupons-list .dropdown-menu').on('click', function(e) {
            e.stopPropagation();
        });
    }

/*    $.ajaxSetup({
        mode: 'abort',
        port: 'ab_coupons'
    });*/
});
