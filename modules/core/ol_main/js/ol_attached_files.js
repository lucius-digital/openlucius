(function ($, Drupal, drupalSettings) {
  // Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
  $('#removeFile').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const file_id = button.data('file_id') // Extract info from data-* attributes
    const file_name = button.data('name') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (file_id != null) {
      modal.find('#remove-file-id').val(file_id)
      modal.find('.file_name').text(file_name)
    }
  })
})(jQuery, Drupal, drupalSettings);