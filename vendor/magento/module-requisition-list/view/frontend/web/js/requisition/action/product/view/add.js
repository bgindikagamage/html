/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_RequisitionList/js/requisition/action/product/add',
    'underscore',
    'jquery'
], function (ProductAddComponent, _, $) {
    'use strict';

    return ProductAddComponent.extend({
        defaults: {
            productFormSelector: '',
            configureModeHash: 'requisition_configure'
        },

        /**
         * Init component
         */
        initialize: function () {
            this._super();

            if (this.isConfigureMode()) {
                this._validateProductForm();
            }
        },

        /**
         * Validate product form
         *
         * @returns {Boolean}
         * @private
         */
        _validateProductForm: function () {
            if (!this._getProductForm().is(':visible')) {
                this._getProductForm().parent().show();
            }

            return this._getProductForm().valid();
        },

        /**
         * Is action valid
         *
         * @returns {Boolean}
         * @protected
         */
        _isActionValid: function () {
            return this._validateProductForm();
        },

        /**
         * Get product data
         *
         * @returns {Object}
         * @protected
         */
        _getProductData: function () {
            var productOptions = this._getProductOptions();

            return _.extend(this._super(), {
                qty: productOptions.qty,
                options: productOptions
            });
        },

        /**
         * Get product form
         *
         * @returns {*|jQuery|HTMLElement}
         * @protected
         */
        _getProductForm: function () {
            return $(this.productFormSelector);
        },

        /**
         * Get product options
         *
         * @returns string
         * @protected
         */
        _getProductOptions: function () {
            var productOptionsList = this._getProductForm().serialize();

            return productOptionsList;
        },

        /**
         * Is configure mode
         *
         * @returns {Boolean}
         */
        isConfigureMode: function () {
            var hash = window.location.hash.replace('#', '');

            return hash == this.configureModeHash; //eslint-disable-line eqeqeq
        }
    });
});
