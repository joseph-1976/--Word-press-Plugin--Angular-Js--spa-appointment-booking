<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( !function_exists( 'wp_handle_upload' ) ) require_once(ABSPATH . 'wp-admin/includes/file.php');

class AB_StaffMemberEditForm extends AB_StaffMemberForm {

    private $errors = array();

    public function configure() {
        $this->setFields( array(
            'wp_user_id',
            'full_name',
            'email',
            'phone',
            'avatar',
            'google_calendar_id',
            'position',
        ) );
    }

    /**
     * @param array $post
     * @param array $files
     */
    public function bind( array $post, array $files = array() ) {
        if ( isset( $post[ 'wp_user_id' ] ) && ! $post[ 'wp_user_id' ] ) {
            $post[ 'wp_user_id' ] = null;
        }

        parent::bind( $post );

        if ( isset ( $files[ 'avatar' ] ) && $files[ 'avatar' ][ 'tmp_name' ] ) {

            if ( in_array( $files[ 'avatar' ][ 'type' ], array( "image/gif", "image/jpeg", "image/png" ) ) ) {
                $movefile = wp_handle_upload( $files[ 'avatar' ], array( 'test_form' => false ) );
                if ( $movefile ) {
                    $imageResize = new AB_ImageResize( $movefile[ 'file' ] );
                    $imageResize->resizeImage( 80, 80 );
                    $imageResize->saveImage( $movefile[ 'file' ], 80 );

                    $this->data[ 'avatar_path' ] = $movefile[ 'file' ];
                    $this->data[ 'avatar_url' ]  = $movefile[ 'url' ];
                }
            }
        }
    }

    /**
     * @return bool|object
     */
    public function save(){
        //verify google calendar
        if (array_key_exists('google_calendar_id', $this->data) && !empty($this->data['google_calendar_id'])){
            $google = new AB_Google();
            $google->loadByStaffId($this->data['id']);
            if (!$google->validateCalendar($this->data['google_calendar_id'])){
                $this->errors['google_calendar'] = implode('<br>', $google->getErrors());
                return false;
            }
        }

        return parent::save();
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
