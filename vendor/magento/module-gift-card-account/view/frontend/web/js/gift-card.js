/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery/ui',
    'mage/validation'
], function ($) {
    'use strict';

    $.widget('mage.giftCard', {
        /**
         * @private
         */
        _create: function () {
            $(this.options.checkStatus).on('click', $.proxy(function () {
                var giftCardStatusId, giftCardSpinnerId, messages;

                if (this.element.validation().valid()) {
                    giftCardStatusId = this.options.giftCardStatusId;
                    giftCardSpinnerId = $(this.options.giftCardSpinnerId);
                    messages = this.options.messages;
                    $.ajax({
                        url: this.options.giftCardStatusUrl,
                        type: 'post',
                        cache: false,
                        data: {
                            'giftcard_code': $(this.options.giftCardCodeSelector).val()
                        },

                        /**
                         * Before send.
                         */
                        beforeSend: function () {
                            giftCardSpinnerId.show();
                        },

                        /**
                         * @param {*} response
                         */
                        success: function (response) {
                            $(messages).hide();
                            $(giftCardStatusId).html(response);
                        },

                        /**
                         * Complete.
                         */
                        complete: function () {
                            giftCardSpinnerId.hide();
                        }
                    });
                }
            }, this));
        }
    });

    return $.mage.giftCard;
});
