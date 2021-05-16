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
    }
  };
  $(document).ready(function() {
    // Message toaster init.
    $('.toast').toast('show');
    // Tooltips init.
    $('[data-toggle="tooltip"]').tooltip()
    // Add bootstrap classes to tables, as attributes are completely stripped on submit.
    $('main table').addClass('table table-bordered table-striped table-hover ')
  });
})(jQuery, Drupal);
