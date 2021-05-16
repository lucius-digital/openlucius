(function ($, Drupal, drupalSettings) {

  $(document).ready(function() {
    // Facilitate placeholder override.
    let placeholder = ''
    if (drupalSettings.placeholder_override){
      placeholder = drupalSettings.placeholder_override
    } else{
      placeholder = Drupal.t('Write away... (@.. to notify). You can also drag images here.')
    }
    $('.summernote').summernote({
      placeholder: '<i class="text-muted">'+placeholder+'</i>',
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'underline','italic','strikethrough', 'clear']],
        ['para', ['ul', 'ol']],
        ['table', ['table']],
        ['view', ['fullscreen','codeview']],
      ],
      styleTags: ['p','blockquote','pre','h3','h4','h5'],
      tooltip:false,
      height:150,
      hint: {
        mentions: drupalSettings.group_users,
        match: /\B@(\w*)$/,
        search: function (keyword, callback) {
          callback($.grep(this.mentions, function (item) {
            return item.indexOf(keyword) == 0;
          }));
        },
        content: function (item) {
          return '@' + item;
        },
      },
      callbacks: {
        onImageUpload: function(files) {
          let target = '.summernote'
          for(var i = 0; i < files.length; i++) {
            var file_obj = files[i];
            uploadFile(file_obj, target);
          }
        },
      },
    });
    // Add table classed in editor.
     $('.note-editing-area table').addClass('table table-sm table-bordered table-striped table-hover')
    // No Focus for now: on comment forms this causes screen to scroll.
/*
    setTimeout(function() {
      $('.summernote').summernote('focus')
    }, 250);
*/

    // Upload files.
    function uploadFile(file, target) {
      data = new FormData();
      data.append("file", file);
      $.ajax({
        data: data,
        type: "POST",
        url: Drupal.url('group/'+drupalSettings.group_uuid+'/upload_inline_images'),
        contentType: false,
        processData: false,
        success: function(response) {
          $(target).summernote("insertImage", response.url, function ($image) {
            $image.css('width', $image.width() / 3);
            $image.attr('data-fid', response.fid);
          });
        }
      });
    }
  });

})(jQuery, Drupal, drupalSettings);
