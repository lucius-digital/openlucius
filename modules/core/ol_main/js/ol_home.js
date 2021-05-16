(function ($, Drupal, drupalSettings) {

  $(document).ready(function() {

  });

  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    const tab_id = $(e.target).data('content')
    // e.target // newly activated tab
    // e.relatedTarget // previous active tab
    if(tab_id === 'nav-posts') {
      let url = Drupal.url('ol_home/get_posts')
      Drupal.ajax({url: url}).execute();
    }
    if(tab_id === 'nav-activity') {
      let url = Drupal.url('ol_home/get_stream')
      Drupal.ajax({url: url}).execute();
    }
  })
  $('a[data-toggle="tab"]').on('hide.bs.tab', function (e) {
    // e.target // previous active tab
    // e.relatedTarget // newly activated tab
    const tab_id = $(e.target).data('content')
    const selector = '#'+tab_id
    $(selector +' .stream-block-wrapper').empty()
    $(selector +' .stream-block-wrapper').text('Loading...')
    $('#pager-container').empty()
    // e.target // newly activated tab
    // e.relatedTarget // previous active tab
  })

})(jQuery, Drupal, drupalSettings);
