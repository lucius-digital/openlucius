(function ($, Drupal, drupalSettings) {

  /**
   * Add message html, triggered by ajax and socket.io
   */
  function appendComment(name, message, userpicture, created, entity_id) {
    const comment_container = $('#comment-container-'+entity_id)
    const messageElement = document.createElement('div')
    messageElement.className = "d-flex justify-content-between align-items-center"
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
      '  <div class="d-flex justify-content-between align-items-center">\n' +
      '    <div class="mr-2">\n' +
      '      <img class="rounded-circle" src="' +
      userpicture +
      '             " alt="" height="25">\n' +
      '    </div>\n' +
      '    <div class="ml-2 small-comment-body">\n' +
      '      <div class="m-0">\n' +
      '        <span href="#" class="username">' +
      name +
      '             </span>\n' +
      '        <span class="body-text">\n' +
      message +
      '             </span>\n' +
      '        <span class="badge badge-light created"><i class="fas fa-clock"></i> ' +
      created +
      '            </span>\n' +
      '      </div>\n' +
      '    </div>\n' +
      '  </div>'
    comment_container.append(messageElement)
  }

  /**
   * Triggered after ajax submit
   */
  $.fn.post_comment = function(commemt, userPicture, created, entity_id ) {
    appendComment('You', commemt, userPicture, created, entity_id)
  }

})(jQuery, Drupal, drupalSettings);
