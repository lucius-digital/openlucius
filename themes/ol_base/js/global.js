(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.globalOpenLuciusBehavior = {
    attach: function (context, settings) {
      // Responsive main navigation.
      $('[data-toggle="offcanvas"]').on('click', function () {
        $('.offcanvas-collapse').toggleClass('open')
      })
      // Group filter in main menu.
      $("#groupFilter").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        $(".dropdown-menu li").filter(function () {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
      });
      // Message toaster init.
      $('.toast').toast('show');
    }
  };
})(jQuery, Drupal);
