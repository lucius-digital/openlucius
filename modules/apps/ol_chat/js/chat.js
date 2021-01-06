(function ($, Drupal, drupalSettings) {

  $(document).ready(function() {
    getChatItems()
    connectUser()
    // File upload, hide Drupal default stuff.
    $("#edit-files .js-hide").addClass("hidden");
    $("#add-chat-file-form .js-hide").addClass("hidden");
    $("#add-chat-file-form label").addClass("hidden");
    // Init mentions autocomplete.
    $('#edit-message').suggest('@', {
      data: drupalSettings.group_users,
      dropdownClass:'chat-mentions',
      filter: {
        limit: 3,
      },
      map: function(user) {
        return {
          value: user.name,
          text: '<strong>'+user.name+'</strong> <small>'+user.mail+'</small>'
        }
      }
    })
  });

  // Modals
  $('#editChatItemModal').on('show.bs.modal', function (event) {
    // Get chat id from button.
    const chat_id = $(event.relatedTarget).data('chat_id')
    // Get chat body.
    const chat_body = $('#chat-body-id_'+chat_id +' .body-text').text();
    const trimmed_body = $.trim(chat_body);
    // Set values in form.
    $("#edit-chat-body").val(trimmed_body);
    $('#edit-chat-id').val(chat_id)
  })

  $('#deleteFileModal').on('show.bs.modal', function (event) {
    // Get chat id from button.
    const chat_id = $(event.relatedTarget).data('chat_id')
    // Get chat body.
    const chat_body = $('#files-body-id_'+chat_id).html();
    const trimmed_body = $.trim(chat_body);
    // Set values in form.
    $("#modal-remove-message").html(chat_body);
    $('#file-remove-chat-id').val(chat_id)
  })

  // Initiate chat.
  if (drupalSettings.node_server) {
    var connectionOptions = {
      pingTimeout: 30000,
      reconnectionAttempts: 'Infinity',
    };
    // const would be better, but this in block right now and we need it outside block scope.
    var socket = io(drupalSettings.node_server, connectionOptions)
    var roomName = drupalSettings.group_uuid
  }
  const name = drupalSettings.username
  const userPicture = drupalSettings.user_picture
  const chatContainer = document.getElementById('chat-items-container')
  const chat_container = $('#chat-items-container')

  /**
   * Connect user and fill container with messages
   */
/*
  $(document).ready(function() {
    getChatItems()
    connectUser()
  });
*/

  /* These should be on all group pages, next level scheiB.
    /!**
     * Set timestamp when exiting, so channel will not be marked as unread unneeded.
     *!/
    $(window).on("beforeunload", function () {
      $.ajax({
        type: 'GET',
        async: false,
        url: '/set_group_visited_timestamp/'+drupalSettings.group_uuid,
      })
    })
    // Needed for Opera
    window.onbeforeunload = function() {
      $.get({
        cache: false,
        url: '/set_group_visited_timestamp/'+drupalSettings.group_uuid
      })
    };
  */

  /**
   * Get chat items and prepend them.
   */
  function getChatItems(){
    // Remove all children in container
    chat_container.empty();
    // Throw spinner image in container.
    chat_container.prepend('<div class="loader"></div>');
    // Get messages, ready made html via AJAX.
    $.get({
      url: '/group/'+drupalSettings.group_uuid +'/get-latest-chat-items/',
      cache: false
      }).then(function(rdata){
        chat_container.empty();
        chat_container.prepend(rdata);
      });
  }

  /**
   * Checks if messages where missed and refreshes where needed.
    */
  $(window).focus(function() {
    // Get timestamps, via ajax, and compare to see if refresh is needed so user always sees all messages.
    $.get({
      url: '/group/'+drupalSettings.group_uuid +'/get-last-message-timestamp',
      cache: false
    }).then(function(data){
      // Check created of last message in dbase of this channel.
      var last_message_timestamp_db = data
      $('#last_message_timestamp_db').text(last_message_timestamp_db);
      // Check created from last message on screen.
      var last_message_timestamp = $("#last_message_timestamp").text();
      // IF different: refresh page
      if (last_message_timestamp_db > last_message_timestamp){
        // First empty and show loader, so user sees system is busy reconnecting and getting messages.
        chat_container.empty();
        chat_container.prepend('<div class="loader"></div>');
        // This is needed, else user gets lost in limbo.
        reconnectUser()
        // Only after reconnecting, so everything is up and running again.
        getChatItems()
      }
      // Always do a reconnect on focus, because socket is auto deleted by server ~few minutes.
      else {
        // Todo | Only for desktop: check if connection is gone.
        // Mobile always needs a reconnect, to prevent user going in limbo.
        reconnectUser()
      }
    });
  });

  /**
   * Add message html, triggered by ajax and socket.io
   */
  function appendMessage(name, message, userpicture, created) {
    const messageElement = document.createElement('li')
    messageElement.className = "media"
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
      '            <img class="mr-3 rounded-circle" src="' +
      userpicture +
      '             " alt="" height="30">\n' +
      '            <div class="media-body">\n' +
      '              <h6 class="mt-0 mb-1">' +
      name +
      '             <span class="badge badge-light comment">\n' +
      created +
      '             </span>' +
      '             </h6>\n' +
      message +
      '            </div>\n'
    chatContainer.append(messageElement)
  }

  /**
   * Triggered after ajax submit
   */
  $.fn.post_message = function(room, message, userPicture, created, timestamp ) {
    appendMessage('You', message, userPicture, created)
    socket.emit('send-chat-message', roomName, message, userPicture, created, timestamp)
    $('#last_message_timestamp').text(timestamp);
  }
  /**
   * Connect user with socket.io server.
   */
  function connectUser() {
    if (drupalSettings.node_server) {
      socket.emit('new-user', roomName, name, userPicture)
    }
  }
  /**
   * Connect user with socket.io server.
   */
  function reconnectUser() {
    socket.close();
    socket.connect()
    socket.emit('new-user', roomName, name, userPicture)
  }
  /**
   * Emit messages to socket.io server.
   */
  if(drupalSettings.node_server) {
    socket.on('chat-message', data => {
      appendMessage(data.name, data.message, data.userpicture, data.created)
      $('#last_message_timestamp').text(data.timestamp);
    })
  }
  /**
   * Misc helpers
    */
  function isMobile(){
    var check = false;
    (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
    return check;
  };
  function isTablet(){
    const userAgent = navigator.userAgent.toLowerCase();
    const isTablet = /(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/.test(userAgent);
    return isTablet;
  }
  function isIOS() {
    if (/iPad|iPhone|iPod/.test(navigator.platform)) {
      return true;
    } else {
      return navigator.maxTouchPoints &&
        navigator.maxTouchPoints > 2 &&
        /MacIntel/.test(navigator.platform);
    }
  }

})(jQuery, Drupal, drupalSettings);
