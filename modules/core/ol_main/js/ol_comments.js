(function ($, Drupal, drupalSettings) {

  /**
   * Add message html, triggered by ajax and socket.io
   */
  function appendComment(name, message, userpicture, created, entity_id) {
    const comment_container = $('#comment-container-'+entity_id)
    const messageElement = document.createElement('div')
    messageElement.className = "media text-muted pt-3"
    //messageElement.innerText = message
    if (userpicture == null){
      userpicture = '/themes/lus/images/white-pixel.jpg'
    }
    if(created == null){
      var today = new Date();
      var time = today.getHours() + ":" + today.getMinutes();
      created = time;
    }
    messageElement.innerHTML =


      '      <img class="rounded-circle mr-2" src="' +
      userpicture +
      '             " alt="" height="25">\n' +
      '    <p class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">\n' +
      '      <strong class="d-block text-gray-dark">\n' +
      name +
      '        <span class="badge badge-pill badge-light text-muted">\n' +
      created +
      '        </span>\n' +
      '      </strong>\n' +
      '      <span class="body-text img-fluid">\n' +
      message +
      '      </span>\n' +
      '    </p>\n'
    comment_container.append(messageElement)
  }

  /**
   * Triggered after ajax submit
   */
  $.fn.post_comment = function(commemt, userPicture, created, entity_id ) {
    appendComment('You', commemt, userPicture, created, entity_id)
  }

})(jQuery, Drupal, drupalSettings);
