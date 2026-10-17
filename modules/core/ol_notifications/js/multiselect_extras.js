/**
 * @file
 * Initializes Bootstrap-Multiselect for notification settings.
 */

(function ($, Drupal, once) {

  /**
   * Multi-select initialization behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.multiselectExtras = {
    attach(context) {
      // Initialize rich multi-select for notifications.
      $(once('multiselect-extras', '#edit-notifications', context)).multiselect({
        includeSelectAllOption: true,
        enableFiltering: true,
        buttonWidth: '150px',
      });
    },
  };

})(jQuery, Drupal, once);
