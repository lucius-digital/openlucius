(function ($, Drupal, drupalSettings) {
  // Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
  $('#removeFile').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const ol_fid = button.data('ol_fid') // Extract info from data-* attributes
    const file_name = button.data('name') // Extract info from data-* attributes
    const file_type = button.data('file_type') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (ol_fid != null) {
      modal.find('#remove-file-id').val(ol_fid)
      modal.find('.file_name').text(file_name)
      modal.find('#file-type').val(file_type)
    }
  })
})(jQuery, Drupal, drupalSettings);
