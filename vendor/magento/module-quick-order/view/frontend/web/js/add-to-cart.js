/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('mage.quickOrderAddToCart', {
        /**
         * Initialization of widget.
         *
         * @private
         */
        _init: function () {
            this.element.on('submit', function () {
                $('body').trigger('processStart');
            });
        }
    });

    return $.mage.addToCart;
});
