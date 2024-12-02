define([
    'jquery'
], function ($) {
    'use strict';

    return function (SwatchRenderer) {
        return $.widget('mage.SwatchRenderer', SwatchRenderer, {
            /**
             * Override _UpdatePrice method to add subscription frequency label
             */
            _UpdatePrice: function () {
                // Call the original logic from swatch-renderer.js
                this._super();

                var $widget = this,
                    $product = $widget.element.parents($widget.options.selectorProduct),
                    $productPrice = $product.find(this.options.selectorProductPrice),
                    selectedProductId = $widget.getProduct(), // Custom logic
                    frequency = $widget.options.jsonConfig.subscriptionFrequency[selectedProductId] || ""; // Custom logic

                // Remove existing frequency labels to avoid duplication
                $productPrice.find('.subscription-frequency-label').remove();
                $productPrice.find('.subscription-frequency-label-lower-price').hide();

                // Add frequency label after the price-wrapper span
                if (frequency) {
                    $productPrice.find('.price-container').after(
                        '<span class="subscription-frequency-label">' + frequency + '</span>'
                    );
                }else if(frequency === '' && selectedProductId === undefined) {
                    $productPrice.find('.price-label').show();
                    $productPrice.find('.subscription-frequency-label-lower-price').show()
                    return;
                }

                // hide label
                $productPrice.find('.price-label').hide();
            }
        });
    };
});
