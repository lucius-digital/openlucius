/**
 * @file
 * Text docs modal and form behaviors.
 *
 * Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
 */

(function ($, Drupal, once) {

  /**
   * Text docs modal and form behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.olTextDocs = {
    attach(context) {

      /*
       * Weblinks
       * This should be made generic, since it now also exists on board_modals.js
       */

      // Delete weblink button listener.
      $(once('olTextDocs-linkedItems', '#linked-items-wrapper', context)).on('click', '.delete-weblink-btn', function (e) {
        e.preventDefault();
        const button = $(this);
        const olWeblinkId = button.data('ol_weblink_id');
        $("#ol-weblink-id").val(olWeblinkId);
        $("#edit-delete-weblink-btn").trigger("click");
      });

      // Add weblink button.
      $(once('olTextDocs-showWeblink', '#show-weblink-form', context)).click(function (e) {
        e.preventDefault();
        $("#weblink-form-wrapper").show("fade");
      });

      // Hide weblink form (cancel button).
      $(once('olTextDocs-hideWeblink', '#edit-hide-weblink-form', context)).click(function (e) {
        e.preventDefault();
        $("#weblink-form-wrapper").hide("fade");
      });

      // Edit text doc listener.
      $(once('olTextDocs-editTextDoc', '.card-body', context)).on('click', '#edit-text-doc', function (e) {
        e.preventDefault();
        const bodyHeight = $('#text-doc-body').height();
        $("#text-doc-edit-form .note-editable").height(bodyHeight);
        $("#text-doc-edit-form").removeClass("hidden");
        $(".body-wrapper").addClass("hidden");
        // Enable navigation prompt.
        window.onbeforeunload = function () {
          return true;
        };
      });

      // Cancel edit text doc listener.
      $(once('olTextDocs-cancelEdit', '.modal-footer', context)).on('click', '#edit-cancel', function (e) {
        e.preventDefault();
        $("#text-doc-edit-form").addClass("hidden");
        $(".body-wrapper").removeClass("hidden");
        // Remove navigation prompt.
        window.onbeforeunload = null;
      });

      // Save edit text doc listener.
      $(once('olTextDocs-saveEdit', '.modal-footer', context)).on('click', '#edit-submit', function (e) {
        // Remove navigation prompt.
        window.onbeforeunload = null;
      });

      // Add/edit category modal.
      $(once('olTextDocs-addEditCategory', '#addEditCategoryModal', context)).on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const name = button.data('name');
        const categoryId = button.data('category_id');
        // Update the modal's content via jQuery.
        const modal = $(this);
        if (name != null) {
          modal.find('#edit-name').val(name);
          modal.find('.modal-title').text(Drupal.t('Edit ') + name);
          modal.find('#edit-category-id').val(categoryId);
        } else {
          modal.find('#edit-name').val('');
          modal.find('.modal-title').text(Drupal.t('Add Category'));
          modal.find('#edit-category-id').val('');
        }
      });

      // Remove text doc from category modal.
      $(once('olTextDocs-removeFromCategory', '#removeTextDocFromCategory', context)).on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const textdocId = button.data('textdoc_id');
        const textdocName = button.data('textdoc_name');
        const categoryName = button.data('category_name');
        // Update the modal's content via jQuery.
        const modal = $(this);
        if (textdocId != null) {
          modal.find('#edit-textdoc-id').val(textdocId);
          modal.find('.textdoc_name').text(textdocName);
          modal.find('.category_name').text(categoryName);
        }
      });

      // Remove text doc modal.
      $(once('olTextDocs-removeTextdoc', '#removeTextdoc', context)).on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const olFid = button.data('textdoc_id');
        const textdocName = button.data('name');
        // Update the modal's content via jQuery.
        const modal = $(this);
        if (olFid != null) {
          modal.find('#remove-textdoc-id').val(olFid);
          modal.find('.textdoc_name').text(textdocName);
        }
      });

      // Put text doc in category modal.
      $(once('olTextDocs-putInCategory', '#putTextdocInCategoryModal', context)).on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const textdocId = button.data('textdoc_id');
        const categoryId = button.data('category_id');
        // Update the modal's content via jQuery.
        const modal = $(this);
        if (textdocId != null) {
          modal.find('#edit-textdoc-id').val(textdocId);
          modal.find('#edit-category-id--2').val(categoryId);
        }
      });

      // Remove category modal.
      $(once('olTextDocs-removeCategory', '#removeCategory', context)).on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const categoryName = button.data('category_name');
        const categoryId = button.data('category_id');
        // Update the modal's content via jQuery.
        const modal = $(this);
        if (categoryId != null) {
          modal.find('#remove-category-id').val(categoryId);
          modal.find('.category_name').text(categoryName);
        }
      });

    }
  };

})(jQuery, Drupal, once);
