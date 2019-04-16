/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_SharedCatalog/js/tier-price/fields-with-currency'
], function (FieldWithCurrency) {
    'use strict';

    describe('Magento_SharedCatalog/js/tier-price/fields-with-currency', function () {
        var sourceParams, field;

        beforeEach(function () {
            sourceParams = {
                data: {
                    'base_currencies': [{
                        'website_id': 1
                    }]
                }
            };
            field = new FieldWithCurrency({
                dataScope: '',
                provider: 'provName',
                parent: {
                    'website_id': true
                },

                /** @inheritdoc */
                source: function () {
                    return sourceParams;
                }
            });
        });

        describe('"initObservable" method', function () {
            it('Check for defined', function () {
                expect(field.hasOwnProperty('initObservable')).toBeDefined();
            });

            it('Check method type', function () {
                var type = typeof field.initObservable;

                expect(type).toEqual('function');
            });

            it('Check if initObservable has been called', function () {
                spyOn(field, 'initObservable');
                field.initObservable();
                expect(field.initObservable).toHaveBeenCalled();
            });
        });

        describe('"setInitialValue" method', function () {
            it('Check for defined', function () {
                expect(field.hasOwnProperty('setInitialValue')).toBeDefined();
            });

            it('Check method type', function () {
                var type = typeof field.setInitialValue;

                expect(type).toEqual('function');
            });

            it('Check if setCurrencyCode has been called', function () {
                spyOn(field, 'setCurrencyCode');
                field.setInitialValue();
                expect(field.setCurrencyCode).toHaveBeenCalled();
            });
        });

        describe('"setCurrencyCode" method', function () {
            it('Check if currency do not set without argument', function () {
                spyOn(field, 'set');
                field.setCurrencyCode();
                expect(field.set).not.toHaveBeenCalled();
            });

            it('Check if currency set with correct argument', function () {
                spyOn(field, 'set');
                field.setCurrencyCode(1);
                expect(field.set).toHaveBeenCalled();
            });

            it('Check if currency do not set with incorrect argument', function () {
                spyOn(field, 'set');
                field.setCurrencyCode(2);
                expect(field.set).not.toHaveBeenCalled();
            });
        });
    });
});
