(function ($, Drupal, drupalSettings) {
  'use strict'
  $(document).ready(function(){
    // Responsive main navigation.
    $('[data-toggle="offcanvas"]').on('click', function () {
      $('.offcanvas-collapse').toggleClass('open')
    })
    // Group filter in main menu.
    $("#myInput").on("keyup", function() {
      var value = $(this).val().toLowerCase();
      $(".dropdown-menu li").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
      });
    });

    // $('#files_table').DataTable();
  });

})(jQuery, Drupal, drupalSettings);
