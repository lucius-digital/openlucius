(function ($, Drupal, once) {

  Drupal.behaviors.folderModals = {
    attach: function (context) {

      $(once('add-edit-folder-modal', '#addEditFoldereModal', context)).on(
        'show.bs.modal',
        function (event) {
          const button = $(event.relatedTarget);
          const name = button.data('name');
          const folderId = button.data('folder_id');
          const modal = $(this);

          if (name != null) {
            modal.find('#edit-name').val(name);
            modal.find('.modal-title').text('Edit ' + name);
            modal.find('#edit-folder-id').val(folderId);
          }
          else {
            modal.find('#edit-name').val('');
            modal.find('.modal-title').text('Add Folder');
            modal.find('#edit-folder-id').val('');
          }
        }
      );

      $(once('remove-file-from-folder-modal', '#removeFileFromFolder', context)).on(
        'show.bs.modal',
        function (event) {
          const button = $(event.relatedTarget);
          const fileId = button.data('file_id');
          const fileName = button.data('file_name');
          const folderName = button.data('folder_name');
          const modal = $(this);

          if (fileId != null) {
            modal.find('#edit-file-id').val(fileId);
            modal.find('.file_name').text(fileName);
            modal.find('.folder_name').text(folderName);
          }
        }
      );

      $(once('remove-file-modal', '#removeFile', context)).on(
        'show.bs.modal',
        function (event) {
          const button = $(event.relatedTarget);
          const olFid = button.data('ol_fid');
          const fileName = button.data('name');
          const fileType = button.data('file_type');
          const modal = $(this);

          if (olFid != null) {
            modal.find('#remove-file-id').val(olFid);
            modal.find('#file-type').val(fileType);
            modal.find('.file_name').text(fileName);
          }
        }
      );

      $(once('put-file-in-folder-modal', '#putFileInFolderModal', context)).on(
        'show.bs.modal',
        function (event) {
          const button = $(event.relatedTarget);
          const fileId = button.data('file_id');
          const folderId = button.data('folder_id');
          const modal = $(this);

          if (fileId != null) {
            modal.find('#edit-fid').val(fileId);
            modal.find('#edit-folder-id--2').val(folderId);
          }
        }
      );

      $(once('remove-folder-modal', '#removeFolder', context)).on(
        'show.bs.modal',
        function (event) {
          const button = $(event.relatedTarget);
          const folderName = button.data('folder_name');
          const folderId = button.data('folder_id');
          const modal = $(this);

          if (folderId != null) {
            modal.find('#remove-folder-id').val(folderId);
            modal.find('.folder_name').text(folderName);
          }
        }
      );

    }
  };

})(jQuery, Drupal, once);

