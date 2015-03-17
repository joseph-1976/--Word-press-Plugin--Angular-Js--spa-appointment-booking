<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    /**
     * @var AB_Category[] $collection
     * @var AB_StaffServicesForm $form
     * @var int $staff_id
     */
    $collection = $form->getCollection();
    $selected = $form->getSelected();
    $uncategorized_services = $form->getUncategorizedServices();

    $currentDateTime  = new DateTime( 'now' );
    $durationDateTime = new DateTime( 'now' );
    $current_time     = time();
?>
<div id="ab-staff-services">
    <?php if ( $collection || $uncategorized_services ) : ?>
        <form>
            <ul>
                <li>
                    <input id="ab-all-services" type="checkbox" />
                    <span><?php _e( 'All services', 'ab' ) ?></span>
                    <?php if ( !empty ( $uncategorized_services ) ) : ?>
                        <div class="ab-title-service">
                            <div><?php _e( 'Price', 'ab' ) ?></div>
                            <div><?php _e( 'Capacity', 'ab' ) ?></div>
                        </div>
                    <?php endif ?>
                </li>
                <?php if ( !empty ( $uncategorized_services ) ) : ?>
                    <li>
                        <ul>
                            <li class="ab-category-services">
                                <ul>
                                    <?php foreach ( $uncategorized_services as $service ) : ?>
                                        <li>
                                            <div class="left ab-list-title">
                                                <input class="ab-service-checkbox" <?php if ( array_key_exists( $service->get( 'id' ), $selected ) ) echo 'checked=checked' ?> type="checkbox" value="<?php echo $service->get( 'id' ) ?>" name="service[<?php echo $service->get( 'id' ) ?>]"/>
                                                <?php echo esc_html($service->get('title')) ?>
                                            </div>
                                            <div class="right">
                                                <input class="ab-price" type="text" <?php if ( !array_key_exists( $service->get( 'id' ), $selected ) ) echo 'disabled=disabled' ?> name="price[<?php echo $service->get( 'id' ) ?>]" value="<?php echo array_key_exists( $service->get( 'id' ), $selected ) ? $selected[ $service->get('id') ]['price'] : $service->get( 'price' )?>">
                                                <input class="ab-price" type="number" min=1 <?php if ( !array_key_exists( $service->get( 'id' ), $selected )) echo 'disabled=disabled' ?> name="capacity[<?php echo $service->get( 'id' ) ?>]" value="<?php echo array_key_exists( $service->get( 'id' ), $selected ) ? $selected[$service->get('id')]['capacity'] : $service->get('capacity')?>">
                                            </div>
                                            <div style="border-bottom: 1px dotted black; overflow: hidden; padding-top: 15px;"></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if ( !empty ( $collection ) ) : ?>
                    <li>
                        <ul>
                            <?php foreach ( $collection as $category ) : ?>
                                <li class="ab-services-category">
                                    <input class="ab-category-checkbox ab-category-<?php echo $category->get( 'id' ) ?>" data-category-id="<?php echo $category->get( 'id' ) ?>" type="checkbox" value="" />
                                    <span><?php echo esc_html($category->get('name')) ?></span>
                                    <div class="ab-title-service">
                                        <div><?php _e( 'Price', 'ab' ) ?></div>
                                        <div><?php _e( 'Capacity', 'ab' ) ?></div>
                                    </div>
                                </li>
                                <li class="ab-category-services">
                                    <ul>
                                        <?php foreach ( $category->getServices() as $service ) : ?>
                                            <li>
                                                <div class="left ab-list-title">
                                                   <input class="ab-service-checkbox ab-category-<?php echo $category->get( 'id' ) ?>" data-category-id="<?php echo $category->get( 'id' ) ?>" <?php if ( array_key_exists( $service->get( 'id' ), $selected )) echo 'checked=checked' ?> type="checkbox" value="<?php echo $service->get( 'id' ) ?>" name="service[<?php echo $service->get( 'id' ) ?>]"/>
                                                   <?php echo esc_html($service->get('title')) ?>
                                                </div>
                                                <div class="right">
                                                    <input class="ab-price" type="text" <?php if ( !array_key_exists( $service->get( 'id' ), $selected )) echo 'disabled=disabled' ?> name="price[<?php echo $service->get( 'id' ) ?>]" value="<?php echo array_key_exists( $service->get( 'id' ), $selected ) ? $selected[$service->get('id')]['price'] : $service->get('price')?>">
                                                    <input class="ab-price" type="number" min=1 <?php if ( !array_key_exists( $service->get( 'id' ), $selected )) echo 'disabled=disabled' ?> name="capacity[<?php echo $service->get( 'id' ) ?>]" value="<?php echo array_key_exists( $service->get( 'id' ), $selected ) ? $selected[$service->get('id')]['capacity'] : $service->get('capacity')?>">
                                                </div>
                                                <div style="border-bottom: 1px dotted black; overflow: hidden; padding-top: 15px;"></div>
                                            </li>
                                        <?php endforeach ?>
                                    </ul>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <input type="hidden" name="action" value="ab_staff_services_update"/>
            <input type="hidden" name="staff_id" value="<?php echo $staff_id ?>"/>
            <span class="spinner"></span>
            <a class="btn btn-info ab-update-button" href="javascript:void(0)" id="ab-staff-services-update"><?php _e('Update', 'ab') ?></a>
            <button class="ab-reset-form" type="reset"><?php _e( 'Reset', 'ab' ) ?></button>
        </form>
    <?php endif; ?>
</div>

