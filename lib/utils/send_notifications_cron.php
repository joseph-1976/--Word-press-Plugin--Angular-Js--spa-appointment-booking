<?php
define('SHORTINIT', true);

require_once( __DIR__ . '/../../../../../wp-load.php' );
require_once( __DIR__ . '/../../../../../' . WPINC . '/formatting.php' );
require_once( __DIR__ . '/../../../../../' . WPINC . '/general-template.php' );
require_once( __DIR__ . '/../../../../../' . WPINC . '/pluggable.php' );
require_once( __DIR__ . '/../../../../../' . WPINC . '/link-template.php' );
require_once( __DIR__ . '/AB_CommonUtils.php' );
require_once( __DIR__ . '/../AB_NotificationReplacement.php' );
require_once( __DIR__ . '/../AB_Entity.php' );
require_once( __DIR__ . '/../entities/AB_CustomerAppointment.php' );

/**
 * Class Notifications
 */
class Notifications {
    private $mysql_now; // format: YYYY-MM-DD HH:MM:SS

    /**
     * @var array
     */
    private static $notifications_types = array(
        'event_next_day'   => 'SELECT * FROM ab_notifications WHERE slug = "event_next_day" AND active = 1',
        'evening_after'    => 'SELECT * FROM ab_notifications WHERE slug = "evening_after" AND active = 1',
        'evening_next_day' => 'SELECT * FROM ab_notifications WHERE slug = "evening_next_day" AND active = 1'
    );

    /**
     * @var array
     */
    private static $appointments_types = array(
        'event_next_day'           =>
            'SELECT a.*, c.*, s.*, st.full_name AS staff_name, st.email AS staff_email, st.phone AS staff_phone, st.avatar_url AS staff_photo, ca.customer_id as customer_id, ss.price AS sprice
            FROM ab_customer_appointment ca
            LEFT JOIN ab_appointment a ON a.id = ca.appointment_id
            LEFT JOIN ab_customer c ON c.id = ca.customer_id
            LEFT JOIN ab_service s ON s.id = a.service_id
            LEFT JOIN ab_staff st ON st.id = a.staff_id
            LEFT JOIN ab_staff_service ss ON ss.staff_id = a.staff_id AND ss.service_id = a.service_id
            WHERE DATE(DATE_ADD("{{NOW}}", INTERVAL 1 DAY)) = DATE(a.start_date)
            AND NOT EXISTS (SELECT id FROM ab_email_notification aen WHERE DATE(aen.created) = DATE("{{NOW}}") AND aen.type = "agenda_next_day" AND aen.staff_id = a.staff_id)',
        'evening_after'            =>
            'SELECT a.*, c.*, s.*, st.full_name AS staff_name, st.email AS staff_email, st.phone AS staff_phone, st.avatar_url AS staff_photo, cat.name AS category_name, ca.customer_id as customer_id, ss.price AS sprice, ca.id as ca_id
            FROM ab_customer_appointment ca
            LEFT JOIN ab_appointment a ON a.id = ca.appointment_id
            LEFT JOIN ab_customer c ON c.id = ca.customer_id
            LEFT JOIN ab_service s ON s.id = a.service_id
            LEFT JOIN ab_staff st ON st.id = a.staff_id
            LEFT JOIN ab_category cat ON cat.id = s.category_id
            LEFT JOIN ab_staff_service ss ON ss.staff_id = a.staff_id AND ss.service_id = a.service_id
            WHERE DATE("{{NOW}}") = DATE(a.start_date)
            AND NOT EXISTS (SELECT id FROM ab_email_notification aen WHERE DATE(aen.created) = DATE("{{NOW}}") AND aen.type = "reminder_evening_after" AND aen.customer_id = ca.customer_id)',
        'evening_next_day'     =>
            'SELECT a.*, c.*, s.*, st.full_name AS staff_name, st.email AS staff_email, st.phone AS staff_phone, st.avatar_url AS staff_photo, cat.name AS category_name, ca.customer_id as customer_id, ss.price AS sprice, ca.id as ca_id
            FROM ab_customer_appointment ca
            LEFT JOIN ab_appointment a ON a.id = ca.appointment_id
            LEFT JOIN ab_customer c ON c.id = ca.customer_id
            LEFT JOIN ab_service s ON s.id = a.service_id
            LEFT JOIN ab_staff st ON st.id = a.staff_id
            LEFT JOIN ab_category cat ON cat.id = s.category_id
            LEFT JOIN ab_staff_service ss ON ss.staff_id = a.staff_id AND ss.service_id = a.service_id
            WHERE DATE(DATE_ADD("{{NOW}}", INTERVAL 1 DAY)) = DATE(start_date)
            AND NOT EXISTS (SELECT id FROM ab_email_notification aen WHERE DATE(aen.created) = DATE("{{NOW}}") AND aen.type = "reminder_evening_next_day" AND aen.customer_id = ca.customer_id)'
    );

    /**
     * @param array $notifications
     * @param $type
     */
    public function processNotifications( $notifications, $type ) {
        /** @var $wpdb wpdb */
        global $wpdb;

        $date = new DateTime();
        switch ( $type ) {
            case 'event_next_day':
                if ($date->format( 'H' ) >= 18) {
                    $rows = $wpdb->get_results(str_replace('{{NOW}}', $this->mysql_now, self::$appointments_types[ 'event_next_day' ]));

                    if ( $rows ) {
                        $staff_schedules = array();
                        $staff_emails = array();
                        foreach ( $rows as $row ) {
                            $staff_schedules[$row->staff_id][] = $row;
                            $staff_emails[$row->staff_id] = $row->staff_email;
                        }

                        foreach ( $staff_schedules as $staff_id => $collection ) {
                            $schedule = '<table>';
                            foreach ( $collection as $object ) {
                                $startDate = new DateTime($object->start_date);
                                $endDate = new DateTime($object->end_date);
                                $schedule .= '<tr>';
                                $schedule .= sprintf( '<td>%s<td>',
                                    ($startDate->format( 'H:i' ) . '-' . $endDate->format( 'H:i' ) ) );
                                $schedule .= sprintf( '<td>%s<td>', $object->title );
                                $schedule .= sprintf( '<td>%s<td>', $object->name );
                                $schedule .= '</tr>';
                            }
                            $schedule .= '</table>';

                            $replacement = new AB_NotificationReplacement();
                            $replacement->set('next_day_agenda', $schedule);
                            $replacement->set('appointment_datetime', $row->start_date);
                            $message = $replacement->replace($notifications->message);
                            $subject = $replacement->replace($notifications->subject);

                            // send mail & create emailNotification
                            if ( wp_mail( $staff_emails[$staff_id], $subject, wpautop( $message ), AB_CommonUtils::getEmailHeaderFrom() ) ) {
                                foreach ( $collection as $object ) {
                                    $this->processEmailNotifications(
                                        $object->customer_id,
                                        $object->staff_id,
                                        'agenda_next_day',
                                        $date->format( 'Y-m-d H:i:s' )
                                    );
                                }
                            }
                        }
                    }
                }
                break;
            case 'evening_after':
                if ($date->format( 'H' ) >= 21) {
                    $rows = $wpdb->get_results(str_replace('{{NOW}}', $this->mysql_now, self::$appointments_types[ 'evening_after' ]));

                    if ( $rows ) {
                        foreach ( $rows as $row ) {
                            $customer_appointment = new AB_CustomerAppointment();
                            $customer_appointment->load($row->ca_id);
                            $custom_fields = '';
                            foreach ($customer_appointment->getCustomFields() as $custom_field) {
                                $custom_fields .= sprintf(
                                    "%s: %s\n",
                                    $custom_field[ 'label' ], $custom_field[ 'value' ]
                                );
                            }

                            $replacement = new AB_NotificationReplacement();
                            $replacement->set('client_name', $row->name);
                            $replacement->set('appointment_datetime', $row->start_date);
                            $replacement->set('service_name', $row->title);
                            $replacement->set('service_price', $row->sprice );
                            $replacement->set('category_name', $row->category_name);
                            $replacement->set('staff_name', $row->staff_name);
                            $replacement->set('staff_email', $row->staff_email);
                            $replacement->set('staff_phone', $row->staff_phone);
                            $replacement->set('staff_photo', $row->staff_photo);
                            $replacement->set('custom_fields', $custom_fields );
                            $message = $replacement->replace($notifications->message);
                            $subject = $replacement->replace($notifications->subject);

                            // send mail & create emailNotification
                            if ( wp_mail( $row->email, $subject, wpautop( $message ), AB_CommonUtils::getEmailHeaderFrom() ) ) {
                                $this->processEmailNotifications(
                                    $row->customer_id ? $row->customer_id : 0,
                                    $row->staff_id    ? $row->staff_id    : 0,
                                    'reminder_evening_after',
                                    $date->format( 'Y-m-d H:i:s' )
                                );
                            }
                        }
                    }
                }
                break;
            case 'evening_next_day':
                if ($date->format( 'H' ) >= 18) {
                    $rows = $wpdb->get_results(str_replace('{{NOW}}', $this->mysql_now, self::$appointments_types[ 'evening_next_day' ]));

                    if ( $rows ) {
                        foreach ( $rows as $row ) {
                            $customer_appointment = new AB_CustomerAppointment();
                            $customer_appointment->load($row->ca_id);
                            $custom_fields = '';
                            foreach ($customer_appointment->getCustomFields() as $custom_field) {
                                $custom_fields .= sprintf(
                                    "%s: %s\n",
                                    $custom_field[ 'label' ], $custom_field[ 'value' ]
                                );
                            }

                            $replacement = new AB_NotificationReplacement();
                            $replacement->set('client_name', $row->name);
                            $replacement->set('appointment_datetime', $row->start_date);
                            $replacement->set('service_name', $row->title);
                            $replacement->set('service_price', $row->sprice );
                            $replacement->set('category_name', $row->category_name);
                            $replacement->set('staff_name', $row->staff_name);
                            $replacement->set('staff_email', $row->staff_email);
                            $replacement->set('staff_phone', $row->staff_phone);
                            $replacement->set('staff_photo', $row->staff_photo);
                            $replacement->set('custom_fields', $custom_fields );
                            $message = $replacement->replace($notifications->message);
                            $subject = $replacement->replace($notifications->subject);

                            // send mail & create emailNotification
                            if ( wp_mail( $row->email, $subject, wpautop( $message ), AB_CommonUtils::getEmailHeaderFrom() ) ) {
                                $this->processEmailNotifications(
                                    $row->customer_id ? $row->customer_id : 0,
                                    $row->staff_id    ? $row->staff_id    : 0,
                                    'reminder_evening_next_day',
                                    $date->format( 'Y-m-d H:i:s' )
                                );
                            }
                        }
                    }
                }
                break;
        }
    }

    /**
     * @param int $customer_id
     * @param int $staff_id
     * @param string $type
     * @param string $date
     * @return array|bool|int
     */
    public function processEmailNotifications( $customer_id, $staff_id, $type, $date ) {
        /** @var $wpdb wpdb */
        global $wpdb;

        return $wpdb->insert(
            'ab_email_notification',
            array(
                'customer_id' => $customer_id,
                'staff_id'    => $staff_id,
                'type'        => $type,
                'created'     => $date
            ),
            array(
                '%d', '%d', '%s', '%s'
            )
        );
    }

    /**
     * Run each notification-row
     */
    public function run() {
        /** @var $wpdb wpdb */
        global $wpdb;

        foreach ( self::$notifications_types as $type => $query ) {
            $notifications = $wpdb->get_row( $query );

            if ( $notifications ) {
                $this->processNotifications( $notifications, $type );
            }
        }
    }

    /**
     * Constructor
     */
    public function __construct() {
        date_default_timezone_set( AB_CommonUtils::getTimezoneString() );

        wp_load_translations_early();

        $now = new DateTime();
        $this->mysql_now = $now->format('Y-m-d H:i:s');
        // run each notification
        $this->run();
    }

}

$notifications = new Notifications();