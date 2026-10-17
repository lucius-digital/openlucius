/**
 * Remove file modal behavior.
 *
 * @file
 *
 * Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
 */

(function ($, Drupal, once) {

    /**
     * Remove file modal behavior.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.olAttachedFiles = {
        attach(context) {
            // Attach remove file modal event handler once per element.
            $(once('olAttachedFiles-removeFile', '#removeFile', context)).on(
                'show.bs.modal', function (event) {
                    const button = $(event.relatedTarget);
                    const olFid = button.data('ol_fid');
                    const fileName = button.data('name');
                    const fileType = button.data('file_type');
                    // Update the modal's content via jQuery.
                    const modal = $(this);
                    if (olFid != null) {
                        modal.find('#remove-file-id').val(olFid);
                        modal.find('.file_name').text(fileName);
                        modal.find('#file-type').val(fileType);
                    }
                }
            );
        }
    };

})(jQuery, Drupal, once);
