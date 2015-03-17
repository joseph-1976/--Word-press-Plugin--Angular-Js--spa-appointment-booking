<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="ab-title"><?php _e( 'Export appointments', 'ab' ); ?></div>
<div class=ab-nav-payment>
    <form class="form-inline" action="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ab_export_to_csv" method="post" style="margin: 0">
        <div id=reportrange class="pull-left ab-reportrange" style="margin-bottom: 10px">
            <i class="icon-calendar icon-large"></i>
            <span data-date="<?php echo date( 'F j, Y', strtotime( '-30 days' ) ) ?> - <?php echo date( 'F j, Y' ) ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( '-30 day' ) ) ?> - <?php echo date_i18n( get_option( 'date_format' ) ) ?></span> <b style="margin-top: 8px;" class=caret></b>
        </div>
        <input type="hidden" id="date_start" name="date_start" value="<?php echo date( 'F j, Y', strtotime( '-30 days' ) ) ?>"/>
        <input type="hidden" id="date_end" name="date_end" value="<?php echo date( 'F j, Y' ) ?>"/>
        <span class="help-inline"><?php _e( 'Delimiter' , 'ab' ) ?></span>
        <select name="delimiter" style="width: 125px;height: 30px">
            <option value=","><?php _e( 'Comma (,)', 'ab' ) ?></option>
            <option value=";"><?php _e( 'Semicolon (;)', 'ab' ) ?></option>
        </select>
        <button type="submit" class="btn btn-info"><?php _e('Export to CSV','ab') ?></button>
    </form>
</div>
<script type="text/javascript">
    jQuery(function($) {
        var data          = {},
            $report_range = $('#reportrange span'),
            picker_ranges = {},
            l10nRanges    = {
                response: function(start, end) {
                    return $.post(ajaxurl, {action: 'ab_l10n_ranges', start: start, end: end});
                },
                l10n: function(start, end) {
                    this.response(start, end).done(function(response) {
                        var ranges = JSON.parse(response);
                        $report_range.data('date', start + ' - ' + end);
                        $report_range.html(ranges.start + ' - ' + ranges.end);
                    });
                }
            };

        picker_ranges[BooklyL10n.today]      = ['today', 'today'];
        picker_ranges[BooklyL10n.yesterday]  = ['yesterday', 'yesterday'];
        picker_ranges[BooklyL10n.last_7]     = [Date.today().add({ days: -6 }), 'today'];
        picker_ranges[BooklyL10n.last_30]    = [Date.today().add({ days: -30 }), 'today'];
        picker_ranges[BooklyL10n.this_month] = [Date.today().moveToFirstDayOfMonth(), Date.today().moveToLastDayOfMonth()];
        picker_ranges[BooklyL10n.last_month] = [Date.today().moveToFirstDayOfMonth().add({ months: -1 }), Date.today().moveToFirstDayOfMonth().add({ days: -1 })];

        $('#reportrange').daterangepicker(
            {
                startDate: Date.today().add({ days: -30 }), // by default selected is "Last 30 days"
                ranges: picker_ranges
            },
            function(start, end) {
                var format = 'MMMM d, yyyy';
                l10nRanges.l10n(start.toString(format), end.toString(format));
                $('#date_start').val(start.toString(format));
                $('#date_end').val(end.toString(format));
            }
        );
    });
</script>