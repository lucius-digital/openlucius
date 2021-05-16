(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.testStuff = {
    attach: function (context) {
      // Init mentions autocomplete.
      console.log(drupalSettings.users_in_group);
      $('.mention-suggest').suggest('@', {
        data: drupalSettings.users_in_group,
        dropdownClass: 'chat-mentions',
        filter: {
          limit: 3,
        },
        map: function (user) {
          return {
            value: user.name,
            text: '<strong>' + user.name + '</strong> <small>' + user.mail + '</small>'
          }
        }
      })
    }
  };
})(jQuery, Drupal, drupalSettings);
