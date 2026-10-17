/**
 * Color settings behavior for applying custom colors to UI elements.
 *
 * @file
 */

(function ($, Drupal, drupalSettings) {

    /**
     * Apply color settings to UI elements.
     *
     * Colors are applied to the entire document, not just the behavior context,
     * because color settings are global (e.g., body background, nav color).
     */
    function applyColorSettings()
    {
        // Get color settings from drupalSettings.
        const colorSettings = drupalSettings.color_settings;
        if (!colorSettings) {
            return;
        }

        // Apply nav color to badge and icon elements.
        $('.badge-light')
            .attr('style', 'color:' + colorSettings.nav + ' !important');
        $('.badge.bg-light')
            .attr('style', 'color:' + colorSettings.nav + ' !important');
        $('.modal-dialog .lni-calendar')
            .attr('style', 'color:' + colorSettings.nav + ' !important');
        $('.modal-dialog .lni-user')
            .attr('style', 'color:' + colorSettings.nav + ' !important');
        $('#main-nav')
            .attr('style', 'background-color:' + colorSettings.nav + ' !important');
        $('body')
            .attr('style', 'background-color:' + colorSettings.global_background + ' !important');

        // Apply nav color to active nav items.
        $('main .nav .active').css(
            {
                'background-color': colorSettings.nav + '20',
                'border-left': colorSettings.nav + ' 3px solid',
                'color': colorSettings.nav
            }
        );
        $('main .shadow-sm')
            .css('border-left', '4px solid ' + colorSettings.nav + '50');
        $('main .container.chat-wrapper')
            .css('border-left', '4px solid ' + colorSettings.nav);
        $('main .comment-wrapper.add-comment')
            .css('border-left', '4px solid ' + colorSettings.nav);
        $('.nav-underline .active')
            .css('border-bottom', '2px solid ' + colorSettings.nav);

        // Apply nav color to success buttons.
        $('.btn-success').css(
            {
                'color': colorSettings.nav,
                'border-color': colorSettings.nav + 37
            }
        );

        // Apply nav color to add story button.
        $('.btn-add-story').css(
            {
                'color': colorSettings.nav
            }
        );

        // Hover effect for success buttons.
        $('.btn-success').hover(
            function () {
                // Mouse enter.
                $(this).css('background-color', colorSettings.nav + '20');
                $(this).css('border-color', colorSettings.nav + 40);
            },
            function () {
                // Mouse leave.
                $(this).css('background-color', '');
                $(this).css('border-color', colorSettings.nav + 20);
            }
        );

        // Apply nav color to primary buttons.
        $('.btn-primary')
            .attr('style', 'color:' + colorSettings.nav + ' !important');

        // Apply nav color to liked buttons.
        $('.liked .button').css(
            {
                'color': colorSettings.nav
            }
        );

        // Apply nav color to folder links.
        $('.folder-left a').css(
            {
                'color': colorSettings.nav
            }
        );

        // Apply nav color to active list group items.
        $('.list-group-item.active').css(
            {
                'background-color': colorSettings.nav + '10',
                'border-left': colorSettings.nav + ' 3px solid',
                'color': colorSettings.nav
            }
        );

        // Hover effect for primary buttons.
        $('.btn-primary').hover(
            function () {
                // Mouse enter.
                $(this).css('background-color', colorSettings.nav + '20');
                $(this).css('border-color', colorSettings.nav + 40);
                $(this).css('color', colorSettings.nav + ' !important');
            },
            function () {
                // Mouse leave.
                $(this).css('background-color', '');
                $(this).css('border-color', '#dee5e4');
            }
        );
    }

    /**
     * Color settings behavior.
     *
     * Waits for window load to ensure CSS is fully loaded before applying colors.
     * Uses requestAnimationFrame to apply colors after the browser has painted.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.olColorSettings = {
        attach() {
            // Defer color application until after window load event.
            // This ensures all CSS is loaded and applied before our custom colors.
            if (document.readyState === 'complete') {
                // If window already loaded, apply after a short delay to let CSS settle.
                setTimeout(applyColorSettings, 0);
            }
            else {
                // Wait for window load event to ensure CSS is fully loaded.
                $(window).one(
                    'load', function () {
                        // Apply after a short delay to let CSS settle.
                        setTimeout(applyColorSettings, 0);
                    }
                );
            }
        }
    };

})(jQuery, Drupal, drupalSettings);
