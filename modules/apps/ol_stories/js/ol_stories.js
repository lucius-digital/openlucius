(function ($, Drupal, once) {

  /**
   * @file
   * Story modal behaviors.
   *
   * Source: https://getbootstrap.com/docs/4.1/components/modal/#varying-modal-content
   */

  /**
   * Story modal behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.olStories = {
    attach(context) {
      // Attach showStory modal event handler once per element.
      $(once('olStories-showStory', '#showStory', context)).on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const storyImageUrl = button.data('story_image_url');
        const storyBody = button.data('story_body');
        // Update the modal's content via jQuery.
        const modal = $(this);
        modal.find('#story_image_url').attr('src', storyImageUrl);
        modal.find('.story_body').text(storyBody);
      });

      // Attach removeStory modal event handler once per element.
      $(once('olStories-removeStory', '#removeStory', context)).on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const storyId = button.data('story_id');
        // Update the modal's content via jQuery.
        const modal = $(this);
        if (storyId != null) {
          modal.find('#remove-story-id').val(storyId);
        }
      });
    }
  };

})(jQuery, Drupal, once);
