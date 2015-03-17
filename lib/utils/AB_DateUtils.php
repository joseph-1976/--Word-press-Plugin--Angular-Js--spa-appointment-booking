<?php

    class AB_DateUtils {
        private static $week_days = array(
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        );

        /**
         * Get week day by day number (0 = Sunday, 1 = Monday...)
         *
         * @param $number
         *
         * @return string
         */
        public static function getWeekDayByNumber( $number ) {
            return isset( self::$week_days[ $number ] ) ? self::$week_days[ $number ] : '';
        }
    }