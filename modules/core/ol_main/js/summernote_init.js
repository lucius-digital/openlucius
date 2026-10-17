/**
 * @file
 * Initializes Summernote rich text editor with @mentions and image upload.
 */

(function ($, Drupal, once) {

  /**
   * Upload an image file via AJAX and insert it into the editor.
   *
   * @param {File} file
   *   The file object to upload.
   * @param {string} target
   *   The jQuery selector for the target Summernote instance.
   */
  function uploadFile(file, target) {
    const formData = new FormData();
    formData.append('file', file);
    $.ajax({
      data: formData,
      type: 'POST',
      url: Drupal.url('group/' + drupalSettings.group_uuid + '/upload_inline_images'),
      contentType: false,
      processData: false,
      success: function (response) {
        $(target).summernote('insertImage', response.url, function ($image) {
          $image.css('width', $image.width() / 3);
          $image.attr('data-fid', response.fid);
        });
      }
    });
  }

  /**
   * Summernote initialization behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.summernoteInit = {
    attach(context) {
      // Initialize Summernote on editor elements that haven't been processed yet.
      $(once('summernote-init', '.summernote', context)).each(function () {
        // Facilitate placeholder override.
        const placeholder = drupalSettings.placeholder_override
          ? drupalSettings.placeholder_override
          : Drupal.t('Write away... (@.. to notify).');

        $(this).summernote({
          placeholder: '<i class="text-muted">' + placeholder + '</i>',
          toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'italic', 'strikethrough', 'clear']],
            ['para', ['ul', 'ol']],
            ['table', ['table']],
            ['view', ['fullscreen', 'codeview']],
          ],
          styleTags: ['p', 'blockquote', 'pre', 'h3', 'h4', 'h5'],
          tooltip: false,
          height: 150,
          hint: {
            mentions: drupalSettings.group_users,
            match: /\B@(\w*)$/,
            search: function (keyword, callback) {
              callback($.grep(this.mentions, function (item) {
                return item.indexOf(keyword) === 0;
              }));
            },
            content: function (item) {
              return '@' + item;
            },
          },
          callbacks: {
            onImageUpload: function (files) {
              for (let i = 0; i < files.length; i++) {
                uploadFile(files[i], this);
              }
            },
          },
        });

        // Add table classes in editor.
        $('.note-editing-area table').addClass('table table-sm table-bordered table-striped table-hover');
      });
    }
  };

})(jQuery, Drupal, once);
