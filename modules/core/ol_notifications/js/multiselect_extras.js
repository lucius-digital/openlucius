(function ($, Drupal, drupalSettings) {

  $(document).ready(function() {
    // Initiate rich multi-select for notifications.
    $('#edit-notifications').multiselect({
      includeSelectAllOption: true,
      enableFiltering: true,
      buttonWidth: '150px',
    });
  });


})(jQuery, Drupal, drupalSettings);
