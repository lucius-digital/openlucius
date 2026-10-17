/**
 * Add group modal behavior.
 *
 * @file
 *
 * Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
 */

(function ($, Drupal, once) {

    /**
     * Add group modal behavior.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.olAddGroup = {
        attach(context) {
            // Attach add group modal event handler once per element.
            $(once('olAddGroup-modal', '#addGroupModal', context)).on(
                'show.bs.modal', function (event) {
                    const button = $(event.relatedTarget);
                    const type = button.data('type');
                    // Update the modal's content via jQuery.
                    const modal = $(this);
                    if (type != null) {
                        modal.find('#group-type-id').val(type);
                        modal.find('#edit-submit').val(Drupal.t('Add ') + type);
                        modal.find('.group-type-label').text(type);
                    }
                }
            );
        }
    };

})(jQuery, Drupal, once);
