<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="ab-title"><?php _e('Services', 'ab') ?></div>
<div class="ab-wrapper-container">
    <div class="ab-left-bar">
        <div id="ab-categories-list">
          <div class="ab-category-item ab-active ab-main-category-item" data-id=""><?php _e('All Services','ab') ?></div>
          <ul id="ab-category-item-list" class="ab-category-item-list">
              <?php if (count($category_collection)): ?>
                  <?php foreach ($category_collection as $category):?>
                    <li class="ab-category-item" data-id="<?php echo $category->id ?>">
                      <span class="ab-handle">
                        <i class="ab-inner-handle icon-move"></i>
                      </span>
                      <span class="left displayed-value"><?php echo esc_html($category->name) ?></span>
                      <a href="#" class="left ab-hidden ab-edit"></a>
                      <input class="value ab-value" type="text" name="name" value="<?php echo esc_attr($category->name) ?>" style="display: none" />
                      <a href="#" class="left ab-hidden ab-delete"></a>
                    </li>
                  <?php endforeach ?>
              <?php endif ?>
          </ul>
        </div>
        <input type="hidden" id="color" />
        <div id="new_category_popup" class="ab-popup-wrapper">
          <input class="btn btn-info ab-popup-trigger" data- type="submit" value="<?php _e('New Category','ab') ?>" />
          <div class="ab-popup" style="display: none">
              <div class="ab-arrow"></div>
              <div class="ab-content">
                  <form method="post" id="new-category-form">
                    <table class="form-horizontal">
                      <tr>
                        <td>
                          <input class="ab-clear-text" style="width: 170px" type="text" name="name" />
                          <input type="hidden" name="action" value="ab_category_form" />
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <input type="submit" class="btn btn-info ab-popup-save ab-update-button" value="<?php _e('Save category','ab') ?>" />
                          <a class="ab-popup-close" href="#"><?php _e('Cancel','ab') ?></a>
                        </td>
                      </tr>
                    </table>
                    <a class="ab-popup-close ab-popup-close-icon" href="#"></a>
                  </form>
              </div>
          </div>
        </div>
    </div>
    <div class="ab-right-content" id="ab_services_wrapper">
        <h2 class="ab-category-title"><?php _e('All services','ab') ?></h2>
        <div class="no-result"<?php if (count($category_collection)) : ?> style="display: none"<?php endif; ?>><?php _e( 'No services found. Please add services.','ab' ) ?></div>
        <div class="list-wrapper">
            <div id="ab-services-list">
                <?php include 'list.php' ?>
            </div>
            <div class="list-actions">
                <a class="add-service btn btn-info" href="#"><?php _e('Add Service','ab') ?></a>
                <a class="delete btn btn-info" href="#"><?php _e('Delete','ab') ?></a>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div id="ab-staff-update" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel"><?php _e( 'Update service setting','ab' ) ?></h3>
        </div>
        <div class="modal-body" style="white-space: normal">
            <span class="help-block"><?php _e( 'You are about to change a service setting which is also configured separately for each staff member. Do you want to update it in staff settings too?','ab' ) ?></span>
            <label class="checkbox">
                <input id="ab-remember-my-choice" type="checkbox" /> <?php _e( 'Remember my choice','ab' ) ?>
            </label>
        </div>
        <div class="modal-footer">
            <button type="reset" class="btn ab-no" data-dismiss="modal" aria-hidden="true"><?php _e( 'No, just update here in services','ab' ) ?></button>
            <button type="submit" class="btn btn-primary ab-yes"><?php _e( 'Yes','ab' ) ?></button>
        </div>
    </div>
</div>