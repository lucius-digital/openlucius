(function ($, Drupal, drupalSettings) {
  // Get color settings
  let color_settings = drupalSettings.color_settings
  if (color_settings) {
    $(".badge-light").attr("style", "color:" +color_settings.nav +" !important")
    $(".badge.bg-light").attr("style", "color:" +color_settings.nav +" !important")
    $(".modal-dialog .lni-calendar").attr("style", "color:" +color_settings.nav +" !important")
    $(".modal-dialog .lni-user").attr("style", "color:" +color_settings.nav +" !important")
    $("#main-nav").attr("style", "background-color:" +color_settings.nav +" !important")
    $("body").attr("style", "background-color:" +color_settings.global_background +" !important")

    $("main .nav .active").css({
      "background-color": color_settings.nav + '20',
      "border-left": color_settings.nav + ' 3px solid',
      "color": color_settings.nav
    })
    $("main .shadow-sm").css("border-left", "4px solid "+color_settings.nav +'50')
    $("main .container.chat-wrapper").css("border-left", "4px solid "+color_settings.nav)
    $("main .comment-wrapper.add-comment").css("border-left", "4px solid "+color_settings.nav)
    $(".nav-underline .active").css("border-bottom", "2px solid "+color_settings.nav)
    $(".btn-success").css({
      "color": color_settings.nav,
      "border-color": color_settings.nav+37,
    })

    $(".btn-add-story").css({
      "color": color_settings.nav,
    })
    $('.btn-success').hover(
      function() {
        // Mouse enter
        $(this).css('background-color', color_settings.nav +'20');
        $(this).css('border-color', color_settings.nav+40);
      },
      function() {
        // Mouse leave.
        $(this).css('background-color', '');
        $(this).css('border-color', color_settings.nav+20);
      }
    )
    $(".btn-primary").attr("style", "color:" +color_settings.nav +" !important")
    // $(".btn-primary").css({
    //   "color": color_settings.nav,
    // })
    $(".liked .button").css({
      "color": color_settings.nav,
    })
    $(".folder-left a").css({
      "color": color_settings.nav,
    })
    $(".list-group-item.active").css({
      "background-color": color_settings.nav + '10',
      "border-left": color_settings.nav + ' 3px solid',
      "color": color_settings.nav
    })
    $('.btn-primary').hover(
      function() {
        // Mouse enter
        $(this).css('background-color', color_settings.nav +'20');
        $(this).css('border-color', color_settings.nav+40);
        $(this).css('color', color_settings.nav +' !important'); // doesn't work
      },
      function() {
        // Mouse leave
        $(this).css('background-color', '');
        $(this).css('border-color', '#dee5e4');
      }
    )


  }
})(jQuery, Drupal, drupalSettings);
