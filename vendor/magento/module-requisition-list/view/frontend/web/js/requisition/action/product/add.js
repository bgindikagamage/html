/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_RequisitionList/js/requisition/action/abstract',
    'underscore',
    'jquery'
], function (RequisitionComponent, _, $) {
    'use strict';

    return RequisitionComponent.extend({
        /**
         * Perform new list action
         *
         * @returns {Promise}
         */
        performNewListAction: function () {
            if (!this._isActionValid({})) {
                return $.Deferred().reject().promise();
            }

            return this._super();
        },

        /**
         * Get action data
         *
         * @returns {Object}
         * @protected
         */
        _getActionData: function (list) {
            return _.extend(this._super(list), {
                'product_data': JSON.stringify(this._getProductData())
            });
        },

        /**
         * Get product data
         *
         * @returns {Object}
         * @protected
         */
        _getProductData: function () {
            return {
                sku: this.sku
            };
        }
    });
});
