// NanoOptions Admin JavaScript – Vanilla, lightweight.
// (c) NanoOptions, MIT License

jQuery(document).ready(function($) {
    'use strict';

    /**
     * Tab switching – works with native WP nav-tabs.
     */
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var tab = href.split('tab=')[1];
        $('.tab-panel, .nanooptions-section').hide();
        $('#tab-' + tab + ', #section-' + tab).show(); // support both IDs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
    });

    /**
     * Conditional fields – show/hide rows based on other field values.
     */
    function updateConditionals() {
        $('[data-condition]').each(function() {
            var $row       = $(this);
            var condition  = $row.data('condition');

            // Parse if string
            if (typeof condition === 'string') {
                try { condition = JSON.parse(condition); } catch(e) { return; }
            }

            if (!condition || !condition.field) return;

            var $controller = $('#' + condition.field);
            if (!$controller.length) return;

            var ctrlVal;
            if ($controller.is(':checkbox')) {
                ctrlVal = $controller.is(':checked') ? '1' : '0';
            } else {
                ctrlVal = $controller.val();
            }

            var show = false;
            switch (condition.compare) {
                case '===': show = (ctrlVal === condition.value); break;
                case '!==': show = (ctrlVal !== condition.value); break;
                case '!=':  show = (ctrlVal != condition.value);  break;
                case '==':
                default:    show = (ctrlVal == condition.value);  break;
            }

            $row.toggle( show );
        });
    }

    // Initial evaluation
    updateConditionals();

    // Bind change events to controller fields
    $(document).on('change keyup', '[data-condition]', function() {
        updateConditionals();
    });

    /**
     * Media uploader.
     */
    if ($('.np-media-upload-button').length && typeof wp !== 'undefined' && wp.media) {
        $(document).on('click', '.np-media-upload-button', function(e) {
            e.preventDefault();
            var button = $(this);
            var frame = wp.media({
                title: button.data('frame-title') || 'Select Media',
                button: { text: button.data('frame-button') || 'Select' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                button.siblings('.np-media-url').val(attachment.url);
                var $preview = button.siblings('.np-media-preview');
                $preview.attr('src', attachment.url).show();
            });

            frame.open();
        });

        $(document).on('click', '.np-media-remove-button', function(e) {
            e.preventDefault();
            var button = $(this);
            button.siblings('.np-media-url').val('');
            button.siblings('.np-media-preview').attr('src', '').hide();
        });
    }

    /**
     * Color picker.
     */
    if ($('.np-color-picker').length && typeof $.fn.wpColorPicker !== 'undefined') {
        $('.np-color-picker').wpColorPicker();
    }
});
