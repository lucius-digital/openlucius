/**
 * Sortable tab settings drag-and-drop behavior.
 *
 * @file
 */

(function ($, Drupal, once) {

    /**
     * Sortable tab settings behavior.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.olSortable = {
        attach(context) {
            // Initialize sortable list once per element.
            $(once('olSortable', '#sortable-tab-settings', context)).each(
                function () {
                    const el = this;
                    const sortableList = Sortable.create(
                        el, {
                            // Element dragging ended.
                            onSort: function (/* Event */evt) {
                                const orderedItems = sortableList.toArray();
                                $.ajax(
                                    {
                                        url: Drupal.url('update_home_tabs_positions'),
                                        type: 'POST',
                                        data: {
                                            orderedItems: orderedItems
                                        },
                                        dataType: 'json',
                                        success: function (result) {
                                            $('#sorted-message').text('Saved');
                                        }
                                    }
                                );
                            }
                        }
                    );
                }
            );
        }
    };

})(jQuery, Drupal, once);
