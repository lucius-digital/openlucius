(function ($, Drupal, drupalSettings) {
  // Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
  $('#addGroupModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const type = button.data('type') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (type != null) {
      modal.find('#group-type-id').val(type)
      modal.find('#edit-submit').val(Drupal.t('Add '+type))
      modal.find('.group-type-label').text(type)
    }
  })

})(jQuery, Drupal, drupalSettings);
