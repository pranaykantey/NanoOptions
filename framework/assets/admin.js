// NanoOptions Admin JavaScript
// Production-optimized, minify-ready

jQuery(document).ready(function($) {
    // Tab switching
    if ($('.nav-tab-wrapper').length) {
        $('.nav-tab-wrapper a').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).attr('href').split('tab=')[1];
            $('.tab-panel').hide();
            $('#tab-' + tab).show();
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
        });
    }

    // Conditional fields visibility
    function updateConditionals() {
        $('[data-condition]').each(function() {
            var $field = $(this);
            var condition = $field.data('condition');
            if (typeof condition === 'string') {
                try {
                    condition = JSON.parse(condition);
                } catch(e) {
                    return; // Invalid JSON
                }
            }
            if (condition && condition.field && condition.value !== undefined) {
                var $controller = $('#' + condition.field);
                var controllerValue = $controller.val();
                // For checkboxes, check if checked
                if ($controller.is(':checkbox')) {
                    controllerValue = $controller.is(':checked') ? '1' : '0';
                }
                // Show/hide based on condition
                if (controllerValue == condition.value) {
                    $field.show();
                } else {
                    $field.hide();
                }
            }
        });
    }
    // Run on load
    updateConditionals();
    // Run when controllers change
    $('[data-condition]').each(function() {
        var condition = $(this).data('condition');
        if (typeof condition === 'string') {
            try {
                condition = JSON.parse(condition);
            } catch(e) {
                return;
            }
        }
        if (condition && condition.field) {
            var $controller = $('#' + condition.field);
            $controller.on('change keyup', function(){
                updateConditionals();
            });
        }
    });

    // Media uploader - only if WordPress media API is available
    if ($('.np-media-upload-button').length && typeof wp !== 'undefined' && wp.media) {
        $(document).on('click', '.np-media-upload-button', function(e){
            e.preventDefault();
            var button = $(this);
            var custom_uploader = wp.media({
                title: 'Choose Image',
                button: {
                    text: 'Choose Image'
                },
                multiple: false
            }).on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                button.prev('.np-media-url').val(attachment.url);
                button.prev('.np-media-preview').attr('src', attachment.url).show();
            }).open();
        });
        $(document).on('click', '.np-media-remove-button', function(e){
            e.preventDefault();
            var button = $(this);
            button.prevAll('.np-media-url').val('');
            button.prevAll('.np-media-preview').attr('src', '').hide();
        });
    }

    // Color picker - only if WordPress color picker is available
    if ($('.np-color-picker').length && typeof $.wpColorPicker === 'function') {
        $('.np-color-picker').wpColorPicker();
    }
});