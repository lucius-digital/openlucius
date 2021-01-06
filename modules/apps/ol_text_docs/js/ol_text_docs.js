(function ($, Drupal, drupalSettings) {

  // Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
  $('#addEditCategoryModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const name = button.data('name') // Extract info from data-* attributes
    const category_id = button.data('category_id') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (name != null) {
      modal.find('#edit-name').val(name)
      modal.find('.modal-title').text(Drupal.t('Edit ') +name)
      modal.find('#edit-category-id').val(category_id)
    } else {
      modal.find('#edit-name').val('')
      modal.find('.modal-title').text(Drupal.t('Add Category'))
      modal.find('#edit-category-id').val('')
    }
  })
  $('#removeTextDocFromCategory').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const textdoc_id = button.data('textdoc_id') // Extract info from data-* attributes
    const textdoc_name = button.data('textdoc_name') // Extract info from data-* attributes
    const category_name = button.data('category_name') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (textdoc_id != null) {
      modal.find('#edit-textdoc-id').val(textdoc_id)
      modal.find('.textdoc_name').text(textdoc_name)
      modal.find('.category_name').text(category_name)
    }
  })
  $('#removeTextdoc').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const ol_fid = button.data('textdoc_id') // Extract info from data-* attributes
    const textdoc_name = button.data('name') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (ol_fid != null) {
      modal.find('#remove-textdoc-id').val(ol_fid)
      modal.find('.textdoc_name').text(textdoc_name)
    }
  })
  $('#putTextdocInCategoryModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const textdoc_id = button.data('textdoc_id') // Extract info from data-* attributes
    const category_id = button.data('category_id') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (textdoc_id != null) {
      modal.find('#edit-textdoc-id').val(textdoc_id)
      modal.find('#edit-category-id--2').val(category_id)
    }
  })
  $('#removeCategory').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const category_name = button.data('category_name') // Extract info from data-* attributes
    const category_id = button.data('category_id') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (category_id != null) {
      modal.find('#remove-category-id').val(category_id)
      modal.find('.category_name').text(category_name)
    }
  })
})(jQuery, Drupal, drupalSettings);
