(function ($, Drupal, drupalSettings) {

  // Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
  $('#showStory').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const story_image_url = button.data('story_image_url') // Extract info from data-* attributes
    const story_body = button.data('story_body') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    // if (story_image_url != null) {
      modal.find('#story_image_url').attr("src", story_image_url)
      modal.find('.story_body').text(story_body)
    // }
  })
  $('#removeStory').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    const story_id = button.data('story_id') // Extract info from data-* attributes
    // Update the modal's content via jquery.
    const modal = $(this)
    if (story_id != null) {
      modal.find('#remove-story-id').val(story_id)
    }
  })
})(jQuery, Drupal, drupalSettings);
