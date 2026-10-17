/**
 * Comment append behavior for AJAX and Socket.IO triggered comments.
 *
 * @file
 *
 * Provides Drupal.behaviors for initializing comment containers and
 * appending comments via AJAX or Socket.IO events.
 */

(function ($, Drupal, once) {

    /**
     * Append a comment to the comment container.
     *
     * @param {string} name
     *   The comment author's name.
     * @param {string} message
     *   The comment message HTML.
     * @param {string} userpicture
     *   URL of the user's profile picture.
     * @param {string} created
     *   The created time string.
     * @param {string} entityId
     *   The entity ID for the comment container.
     */
    function appendComment(name, message, userpicture, created, entityId)
    {
        const commentContainer = $('#comment-container-' + entityId);
        const messageElement = document.createElement('div');
        messageElement.className = 'media text-muted pt-3';
        if (userpicture == null) {
            userpicture = '/themes/lus/images/white-pixel.jpg';
        }
        if (created == null) {
            const today = new Date();
            const time = today.getHours() + ':' + today.getMinutes();
            created = time;
        }
        messageElement.innerHTML =
        '      <img class="rounded-circle mr-2" src="' +
        userpicture +
        '             " alt="" height="25">\n' +
        '    <p class="media-body pb-3 mb-0 lh-125 border-bottom border-gray">\n' +
        '      <strong class="d-block text-gray-dark">\n' +
        name +
        '        <span class="badge badge-pill badge-light text-muted">\n' +
        created +
        '        </span></strong>\n      <span class="body-text img-fluid">\n' +
        message +
        '      </span></p>\n';
        commentContainer.append(messageElement);
    }

    /**
     * jQuery plugin: Triggered after AJAX submit.
     *
     * @param {string} comment
     *   The comment text.
     * @param {string} userPicture
     *   URL of the user's profile picture.
     * @param {string} created
     *   The created time string.
     * @param {string} entityId
     *   The entity ID for the comment container.
     */
    $.fn.post_comment = function (comment, userPicture, created, entityId) {
        appendComment('You', comment, userPicture, created, entityId);
    };

    /**
     * Comment behaviors.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.olComments = {
        attach(context) {
            // Mark comment containers for future AJAX appends.
            once('olComments', '[data-entity-id]', context).each(
                function () {
                    // Ensure comment containers are initialized.
                    const entityId = $(this).data('entity-id');
                    if (entityId) {
                        $(this).attr('id', 'comment-container-' + entityId);
                    }
                }
            );
        }
    };

})(jQuery, Drupal, once);
