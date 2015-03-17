<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class AB_ServiceController
 */
class AB_ServiceController extends AB_Controller {

    /**
     * Index page.
     */
    public function index() {
        $this->enqueueStyles( array(
            'wp' => array(
                'wp-color-picker',
            ),
            'backend' => array(
                'css/ab_style.css',
                'bootstrap/css/bootstrap.min.css',
            ),
            'module' => array(
                'css/service.css',
            )
        ) );

        $this->enqueueScripts( array(
            'wp' => array(
                'wp-color-picker',
            ),
            'backend' => array(
                'js/ab_popup.js' => array( 'jquery' ),
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
//                'js/jquery.ajaxQueue.js' => array( 'jquery' ),
            ),
            'module' => array(
                'js/service.js' => array( 'jquery-ui-sortable', 'jquery' ),
            )
        ) );

        wp_localize_script( 'ab-service.js', 'BooklyL10n', array(
            'are_you_sure' => __( 'Are you sure?', 'ab' ),
            'please_select_at_least_one_service' => __( 'Please select at least one service.', 'ab'),
        ) );

        $this->staff_collection    = $this->getStaffCollection();
        $this->category_collection = $this->getCategoryCollection();
        $this->service_collection  = $this->getServiceCollection();
        $this->render( 'index' );
    }

    /**
     *
     */
    public function executeCategoryServices() {
        $this->setDataForServiceList();
        $this->render( 'list' );
        exit;
    }

    /**
     *
     */
    public function executeCategoryForm() {
        $this->form = new AB_CategoryForm();

        if ( !empty ( $_POST ) ) {
            $this->form->bind( $this->getPostParameters() );
            if ( $category = $this->form->save() ) {
                echo "<li class='ab-category-item' data-id='{$category->id}'>
                    <span class='ab-handle'><i class='ab-inner-handle icon-move'></i></span>
                    <span class='left displayed-value'>{$category->name}</span>
                    <a href='#' class='left ab-hidden ab-edit'></a>
                    <input class=value type=text name=name value='{$category->name}' style='display: none' />
                    <a href='#' class='left ab-hidden ab-delete'></a></li>";
                exit;
            }
        }
        exit;
    }

    /**
     * Update category.
     */
    public function executeUpdateCategory() {
        $form = new AB_CategoryForm();
        $form->bind( $this->getPostParameters() );
        $form->save();
    }

    /**
     * Update category position.
     */
    public function executeUpdateCategoryPosition() {
        $category_sorts = $this->getParameter( 'position' );
        foreach ( $category_sorts as $position => $category_id ) {
            $category_sort = new AB_Category();
            $category_sort->load($category_id);
            $category_sort->set( 'position', $position );
            $category_sort->save();
        }
    }

    /**
     * Update services position.
     */
    public function executeUpdateServicesPosition() {
        $services_sorts = $this->getParameter( 'position' );
        foreach ( $services_sorts as $position => $service_ids ) {
            $services_sort = new AB_Service();
            $services_sort->load($service_ids);
            $services_sort->set( 'position', $position );
            $services_sort->save();
        }
    }

    /**
     * Delete category.
     */
    public function executeDeleteCategory() {
        $category = new AB_Category();
        $category->set( 'id', $this->getParameter( 'id', 0 ) );
        $category->delete();
    }


    public function executeAddService() {
        $form = new AB_ServiceForm();
        $form->bind( $this->getPostParameters() );
        $service = $form->save();
        $this->setDataForServiceList( $service->get( 'category_id' ) );
        $this->render( 'list' );
        exit;
    }

    public function executeRemoveServices() {
        $service_ids = $this->getParameter( 'service_ids', array() );
        if ( is_array( $service_ids ) && !empty ( $service_ids ) ) {
            $this->getWpdb()->query('DELETE FROM ab_service WHERE id IN (' . implode(', ', $service_ids) . ')');
        }
    }

    public function executeAssignStaff() {
        $service_id = $this->getParameter( 'service_id', 0 );
        $staff_ids  = $this->getParameter( 'staff_ids', array() );

        if ( $service_id ) {
            $this->getWpdb()->delete('ab_staff_service', array( 'service_id' => $service_id ), array( '%d' ) );
            $service = new AB_Service();
            if ( ! empty( $staff_ids ) && $service->load( $service_id ) ) {
                foreach ( $staff_ids as $staff_id ) {
                    $staff_service = new AB_StaffService();
                    $staff_service->set( 'staff_id',   $staff_id );
                    $staff_service->set( 'service_id', $service_id );
                    $staff_service->set( 'price',      $service->get( 'price' ) );
                    $staff_service->save();
                }
            }
            echo count( $staff_ids );
            exit;
        }
    }

    public function executeUpdateServiceValue() {
        /** @var $wpdb  wpdb */
        global $wpdb;

        $form = new AB_ServiceForm();
        $form->bind( $this->getPostParameters() );
        $form->save();

        if ( $this->getParameter( 'update_staff', false ) ) {
            if ( $this->getParameter( 'price' ) ) {
                $wpdb->update('ab_staff_service', array('price' => $this->getParameter( 'price' )), array('service_id' => $this->getParameter( 'id' )));
            }
            if ( $this->getParameter( 'capacity' ) ) {
                $wpdb->update('ab_staff_service', array('capacity' => $this->getParameter( 'capacity' )), array('service_id' => $this->getParameter( 'id' )));
            }
        }

    }

    /**
     * @param int $category_id
     */
    private function setDataForServiceList( $category_id = 0 ) {
        if (!$category_id) {
            $category_id = $this->getParameter( 'category_id', 0 );
        }

        $this->service_collection  = $this->getServiceCollection($category_id);
        $this->staff_collection    = $this->getStaffCollection();
        $this->category_collection = $this->getCategoryCollection();
    }

    /**
     * @return mixed
     */
    private function getCategoryCollection() {
        return $this->getWpdb()->get_results( "SELECT * FROM ab_category ORDER BY position" );
    }

    /**
     * @return mixed
     */
    private function getStaffCollection() {
        return $this->getWpdb()->get_results( "SELECT * FROM ab_staff" );
    }

    /**
     * @param int $id
     * @return mixed
     */
    private function getServiceCollection( $id = 0 ) {

        $query = $this->getWpdb()->prepare(
            'SELECT
               `s`.*,
               COUNT(`st`.`id`) AS `total_staff`,
               GROUP_CONCAT(DISTINCT `st`.`id`) AS `staff_ids`
             FROM `ab_service` `s`
               LEFT JOIN `ab_staff_service` `sts` ON `sts`.`service_id` = `s`.`id`
               LEFT JOIN `ab_staff` `st` ON `st`.`id` = `sts`.`staff_id`
             WHERE `s`.`category_id` = %d OR !%d
             GROUP BY `s`.`id` ORDER BY ISNULL(`s`.`position`), `s`.`position`',
            $id,
            $id
        );

        return $this->getWpdb()->get_results($query);
    }

    // Protected methods.

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     */
    protected function registerWpActions( $prefix = '' ) {
        parent::registerWpActions( 'wp_ajax_ab_' );
    }

}
