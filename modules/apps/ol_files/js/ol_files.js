(function ($, Drupal, drupalSettings) {

  // Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
  $('#addEditFoldereModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const name = button.data('name') // Extract info from data-* attributes
    const folder_id = button.data('folder_id') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (name != null) {
      modal.find('#edit-name').val(name)
      modal.find('.modal-title').text('Edit ' +name)
      modal.find('#edit-folder-id').val(folder_id)
    } else {
      modal.find('#edit-name').val('')
      modal.find('.modal-title').text('Add Folder')
      modal.find('#edit-folder-id').val('')
    }
  })
  $('#removeFileFromFolder').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const file_id = button.data('file_id') // Extract info from data-* attributes
    const file_name = button.data('file_name') // Extract info from data-* attributes
    const folder_name = button.data('folder_name') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (file_id != null) {
      modal.find('#edit-file-id').val(file_id)
      modal.find('.file_name').text(file_name)
      modal.find('.folder_name').text(folder_name)
    }
  })
  $('#removeFile').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const ol_fid = button.data('ol_fid') // Extract info from data-* attributes
    const file_name = button.data('name') // Extract info from data-* attributes
    const file_type = button.data('file_type') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (ol_fid != null) {
      modal.find('#remove-file-id').val(ol_fid)
      modal.find('#file-type').val(file_type)
      modal.find('.file_name').text(file_name)
    }
  })
  $('#putFileInFolderModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const file_id = button.data('file_id') // Extract info from data-* attributes
    const folder_id = button.data('folder_id') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (file_id != null) {
      modal.find('#edit-fid').val(file_id)
      modal.find('#edit-folder-id--2').val(folder_id)
    }
  })
  $('#removeFolder').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const folder_name = button.data('folder_name') // Extract info from data-* attributes
    const folder_id = button.data('folder_id') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (folder_id != null) {
      modal.find('#remove-folder-id').val(folder_id)
      modal.find('.folder_name').text(folder_name)
    }
  })
})(jQuery, Drupal, drupalSettings);
