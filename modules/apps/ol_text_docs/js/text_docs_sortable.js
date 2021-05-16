(function ($, Drupal, drupalSettings) {
  $(document).ready(()=>{

    // Trigger click on drop down button to uncollapse parents of active link.
    // Also: uncollapse 1st level children if parent is clicked.
    $("a.active.left-menu-link").parents('.button-holder').find('.drop-down-toggle:lt(1)').trigger('click');

    // Handle sortable in modal.
    var nestedSortables = [].slice.call(document.querySelectorAll('.nested-sortable'));

    // Loop through each nested sortable element.
    if(nestedSortables) {
      for (var i = 0; i < nestedSortables.length; i++) {
        new Sortable(nestedSortables[i], {
          dragoverBubble: true,
          group: 'nested',
          animation: 0,
          fallbackOnBody: true,
          swapThreshold: 0.7,
          invertSwap: true,
          draggable: ".nested-sortable ",
          onEnd: function ( /**Event*/evt) {
            $.ajax({
              url: Drupal.url('group/' + drupalSettings.group_uuid + '/save_text_docs_order'),
              type: 'POST',
              data: {
                'orderedItems': JSON.stringify(getOrder()),
              },
              dataType: 'json',
              success: function (result) {
                $('#sorted-message').text('Order saved successfully')
              }
            });
          }
        });
      }
      // When drag end, this function gets triggered.
      // Returns all data we need to save order to dbase.
      function getOrder() {
        let pages = [];
        $('.nested-sortable.item').each(function (i, e) {
          let el = $(e);
          pages.push(
            {
              parent_id: +el.parent().attr('id'),
              id: +el.attr('id'),
              weight: el.index(),
            }
          )
        });
        return pages
      }
    }
  });
})(jQuery, Drupal, drupalSettings);

