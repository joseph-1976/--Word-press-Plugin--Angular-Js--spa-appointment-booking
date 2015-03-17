<?php $color = get_option( 'ab_appearance_color' ) ?>
<?php $checkbox_img = plugins_url( 'frontend/resources/images/checkbox.png', AB_PATH . '/main.php' ) ?>
<style type="text/css">
    /* Service */
    .ab-formGroup .ab-label-error {color: <?php echo $color ?>!important;}
    label.ab-category-title {color: <?php echo $color ?>!important;}
    .ab-next-step, .ab-mobile-next-step, .ab-mobile-prev-step, li.ab-step-tabs.active div,
    .picker__frame, .ab-first-step .ab-week-days li label {background: <?php echo $color ?>!important;}
    li.ab-step-tabs.active a {color: <?php echo $color ?>!important;}
    div.ab-select-service-error {color: <?php echo $color ?>!important;}
    div.ab-select-time-error {color: <?php echo $color ?>!important;}
    .ab-select-wrap.ab-service-error .select-list {border: 2px solid <?php echo $color ?>!important;}
    .picker__header {border-bottom: 1px solid <?php echo $color ?>!important;}
    .picker__nav--next, .pickadate__nav--prev {color: <?php echo $color ?>!important;}
    .picker__nav--next:before {border-left:  6px solid <?php echo $color ?>!important;}
    .picker__nav--prev:before {border-right: 6px solid <?php echo $color ?>!important;}
    .picker__day:hover {color: <?php echo $color ?>!important;}
    .picker__day--selected:hover {color: <?php echo $color ?>!important;}
    .picker__day--selected,
    .picker__day--highlighted {color: <?php echo $color ?>!important;}
    .picker__button--clear {color: <?php echo $color ?>!important;}
    .picker__button--today {color: <?php echo $color ?>!important;}
    .ab-first-step .ab-week-days li label.active {background: <?php echo $color ?> url(<?php echo $checkbox_img ?>) 0 0 no-repeat!important;}
    /* Time */
    .ab-columnizer .ab-available-day {
        background: <?php echo $color ?>!important;
        border: 1px solid <?php echo $color ?>!important;
    }
    .ab-columnizer .ab-available-hour:hover {
        border: 2px solid <?php echo $color ?>!important;
        color: <?php echo $color ?>!important;
    }
    .ab-columnizer .ab-available-hour:hover .ab-hour-icon {
        background: none;
        border: 2px solid <?php echo $color ?>!important;
        color: <?php echo $color ?>!important;
    }
    .ab-columnizer .ab-available-hour:hover .ab-hour-icon span {background: <?php echo $color ?>!important;}
    .ab-time-next {background: <?php echo $color ?>!important;}
    .ab-time-prev {background: <?php echo $color ?>!important;}
    .ab-to-first-step {background: <?php echo $color ?>!important;}
    /* Details */
    label.ab-formLabel {color: <?php echo $color ?>!important;}
    a.ab-to-second-step {background: <?php echo $color ?>!important;}
    a.ab-to-fourth-step {background: <?php echo $color ?>!important;}
    div.ab-error {color: <?php echo $color ?>!important;}
    input.ab-details-error,
    textarea.ab-details-error {border: 2px solid <?php echo $color ?>!important;}
    .ab-to-second-step, .ab-to-fourth-step {background: <?php echo $color ?>!important;}
    /* Payment */
    .btn-apply-coupon {background: <?php echo $color ?>!important;}
    .ab-to-third-step {background: <?php echo $color ?>!important;}
    .ab-final-step {background: <?php echo $color ?>!important;}
</style>