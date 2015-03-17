;(function() {

angular.module('newCustomerDialog', []).directive('newCustomerDialog', function() {
  return {
    restrict : 'A',
    replace  : true,
    scope    : {
      callback  : '&newCustomerDialog',
      backdrop  : '@',
      btn_class : '@btnClass'
    },
    templateUrl : ajaxurl + '?action=ab_get_ng_new_customer_dialog_template&module=' + ((typeof BooklyL10n.module == 'undefined')? 'calendar' : BooklyL10n.module) ,
    // The linking function will add behavior to the template.
    link: function(scope, element, attrs) {
      // Init properties.
      var init = function() {
        // Form fields.
        scope.form = {
          name  : '',
          phone : '',
          email : ''
        };
        // Form errors.
        scope.errors = {
          name : {
            required : false
          }
        };
        // Loading indicator.
        scope.loading = false;
      };

      // Run init.
      init();

      // On 'Cancel' button click.
      scope.closeDialog = function() {
        // Close the dialog.
        element.children('#ab_new_customer_dialog').modal('hide');
        // Re-init all properties.
        init();
      };

      /**
       * Send form to server.
       */
      scope.processForm = function() {
        scope.errors  = {};
        scope.loading = true;
        jQuery.ajax({
          url  : ajaxurl,
          type : 'POST',
          data : jQuery.extend({ action : 'ab_save_customer' }, scope.form),
          dataType : 'json',
          success : function ( response ) {
            scope.$apply(function(scope) {
              if (response.status === 'ok') {
                  // save custom fields of new customer into parent scope
                  var result  = [];
                  var $fields = jQuery('.new-customer-custom-fields .ab-formField');
                  $fields.each(function() {
                      var $this = jQuery(this);
                      var value;
                      switch ($this.data('type')) {
                          case 'checkboxes':
                              value = [];
                              $this.find('.ab-custom-field:checked').each(function() {
                                  value.push(this.value);
                              });
                              break;
                          case 'radio-buttons':
                              value = $this.find('.ab-custom-field:checked').val();
                              break;
                          default:
                              value = $this.find('.ab-custom-field').val();
                              break;
                      }
                      result.push({ id: $this.data('id'), value: value });
                  });
                  response.customer.custom_fields = result;

                // Send new customer to the parent scope.
                scope.callback({customer : response.customer});
                // Close the dialog.
                scope.closeDialog();
              } else {
                // Set errors.
                jQuery.each(response.errors, function(field, errors) {
                  scope.errors[field] = {};
                  jQuery.each(errors, function(key, error) {
                    scope.errors[field][error] = true;
                  });
                });
              }
              scope.loading = false;
            });
          },
          error : function() {
            scope.$apply(function(scope) {
              scope.loading = false;
            });
          }
        });
      };
    }
  };
});

})();