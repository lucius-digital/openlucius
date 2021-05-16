(function ($, Drupal, drupalSettings) {
  // Get color settings
  let color_settings = drupalSettings.color_settings
  if (color_settings) {
    $("#main-nav").attr("style", "background-color:" +color_settings.nav +" !important")
    $("body").attr("style", "background-color:" +color_settings.global_background +" !important")
    $("main .active").css("background-color", color_settings.nav)
    $("main .shadow-sm").css("border-left", "4px solid "+color_settings.nav)
    $("main .container.chat-wrapper").css("border-left", "4px solid "+color_settings.nav)
    $("main .comment-wrapper.add-comment").css("border-left", "4px solid "+color_settings.nav)
    $(".nav-underline .active").css("border-bottom", "2px solid "+color_settings.nav)
  }
})(jQuery, Drupal, drupalSettings);
