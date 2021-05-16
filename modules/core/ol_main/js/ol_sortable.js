(function ($, Drupal, drupalSettings) {

  $(document).ready(function() {
    const el = document.getElementById('sortable-tab-settings');
    const sortable_list = Sortable.create(el, {
      // Element dragging ended.
      onSort: function (/**Event*/evt) {
        const orderedItems = sortable_list.toArray();
        $.ajax({
          url: Drupal.url('update_home_tabs_positions'),
          type: 'POST',
          data: {
            'orderedItems': orderedItems,
          },
          dataType: 'json',
          success: function (result) {
            $('#sorted-message').text('Saved')
          }
        });
      },
    });
  });

})(jQuery, Drupal, drupalSettings);
