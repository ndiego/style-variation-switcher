(function($) {
    'use strict';

    const $baseStyles = $('#global-styles-inline-css');
    const $variationStyles = $('<style>', {
        id: 'variation-styles-preview'
    }).insertAfter('#global-styles-inline-css');

    // Store the original stylesheet content
    const originalStyles = $baseStyles.html() || '';
    
    $(document).ready(function() {
        // Ensure default is active on page load
        $('.color-swatch.default-swatch').addClass('active');
        
        $('.color-swatch').on('click', function() {
            const $this = $(this);
            const variation = $this.data('value');
            
            // Update active state
            $('.color-swatch').removeClass('active');
            $this.addClass('active');
            
            if (!variation) {
                // Restore default styles
                $variationStyles.html('');
                return;
            }

            if (styleVariationSwitcher.variations[variation]) {
                // Apply variation styles
                $variationStyles.html(styleVariationSwitcher.variations[variation]);
            }
        });
    });
})(jQuery); 