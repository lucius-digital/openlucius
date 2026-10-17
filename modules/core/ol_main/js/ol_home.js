/**
 * Home page tab switching and AJAX content loading behavior.
 *
 * @file
 */

(function ($, Drupal, once) {

    /**
     * Home page tab behaviors.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.olHome = {
        attach(context) {
            // Attach tab shown event handler once per tab link.
            $(once('olHome-tabShown', 'a[data-toggle="tab"]', context)).on(
                'shown.bs.tab', function (e) {
                    const tabId = $(e.target).data('content');
                    if (tabId === 'nav-posts') {
                        const url = Drupal.url('ol_home/get_posts');
                        Drupal.ajax({url: url}).execute();
                    }
                    if (tabId === 'nav-activity') {
                        const url = Drupal.url('ol_home/get_stream');
                        Drupal.ajax({url: url}).execute();
                    }
                }
            );

        // Attach tab hide event handler once per tab link.
        $(once('olHome-tabHide', 'a[data-toggle="tab"]', context)).on(
            'hide.bs.tab', function (e) {
                const tabId = $(e.target).data('content');
                const selector = '#' + tabId;
                $(selector + ' .stream-block-wrapper').empty();
                $(selector + ' .stream-block-wrapper').text('Loading...');
                $('#pager-container').empty();
            }
        );
        }
    };

})(jQuery, Drupal, once);
