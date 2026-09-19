(function ($, Drupal, drupalSettings) {

  /*
   * Weblinks
   * This should be made generic, since it now also exists on board_modals.js
   */

  // Delete weblink btn listener.
  $('#linked-items-wrapper').on('click','.delete-weblink-btn', function(e){
    e.preventDefault();
    const button = $(this)
    const ol_weblink_id = button.data('ol_weblink_id')
    $( "#ol-weblink-id").val(ol_weblink_id);
    $( "#edit-delete-weblink-btn" ).trigger( "click" );
  });
  // Add weblink btn.
  $("#show-weblink-form").click(function(e) {
    e.preventDefault();
    $( "#weblink-form-wrapper" ).show("fade");
  });
  // Hide weblink form (cancel btn)
  $("#edit-hide-weblink-form").click(function(e) {
    e.preventDefault();
    $( "#weblink-form-wrapper").hide("fade");
  });

  // Edit text doc listener
  $('.card-body').on('click','#edit-text-doc', function(e){
    e.preventDefault();
    // const button = $(this)
    const body_height = $('#text-doc-body').height()
    $("#text-doc-edit-form .note-editable").height(body_height);
    $("#text-doc-edit-form").removeClass( "hidden" );
    $(".body-wrapper").addClass( "hidden" );
    // Enable navigation prompt
    window.onbeforeunload = function() {
      return true;
    };
  });
  // Cancel Edit text doc listener.
  $('.modal-footer').on('click','#edit-cancel', function(e){
    e.preventDefault();
    // const button = $(this)
    $("#text-doc-edit-form").addClass( "hidden" );
    $(".body-wrapper").removeClass( "hidden" );
    // Remove navigation prompt
    window.onbeforeunload = null;
  });
  // Save Edit text doc listener.
  $('.modal-footer').on('click','#edit-submit', function(e){
    // Remove navigation prompt.
    window.onbeforeunload = null;
  });

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
