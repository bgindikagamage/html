/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable max-nested-callbacks */
define([
    'ko',
    'squire'
], function (ko, Squire) {
    'use strict';

    var injector = new Squire(),
        mocks = {
            'Magento_Checkout/js/model/quote': {
                shippingMethod: ko.observable(),
                shippingAddress: ko.observable(),
                isVirtual: jasmine.createSpy()
            },
            'Magento_Checkout/js/model/shipping-rate-service': {
                shippingAddress: ko.observable({
                    subscribe: jasmine.createSpy()
                })
            },
            'Magento_Checkout/js/model/shipping-rates-validator': {
                bindChangeHandlers: jasmine.createSpy()
            },
            'Magento_Checkout/js/model/step-navigator': {
                registerStep: jasmine.createSpy()
            }
        },
        method = {
            'method_code': 'flatrate',
            'carrier_code': 'flatrate'
        },
        shipping;

    describe('Magento_NegotiableQuote/js/view/shipping', function () {

        beforeEach(function (done) {
            window.checkoutConfig = {
                selectedShipping: 'flatrate_flatrate',
                isAddressSelected: true,
                isAddressInAddressBook: false,
                quoteShippingAddress: true,
                isNegotiableQuote: true
            };

            injector.mock(mocks);
            injector.require(['Magento_NegotiableQuote/js/view/shipping'], function (Constr) {
                shipping = Constr();
                done();
            });
        });

        describe('"initialize" method', function () {
            it('Check for defined', function () {
                expect(shipping.initialize).toBeDefined();
                expect(shipping.initialize).toEqual(jasmine.any(Function));
            });

            it('Check if flags have been changed for negotiable quote', function () {
                expect(shipping.isQuoteAddressDeleted).toBe(true);
                expect(shipping.isFormInline).toBe(false);
                expect(shipping.isQuoteAddressLocked).toBe(false);
                expect(shipping.hasQuoteShippingAddress).toBe(true);
            });

            it('Check if shippingMethod has been called with selected method', function () {
                spyOn(mocks['Magento_Checkout/js/model/quote'], 'shippingMethod');
                shipping.initialize();
                expect(mocks['Magento_Checkout/js/model/quote'].shippingMethod).toHaveBeenCalledWith(method);
            });
        });

        describe('"initObservable" method', function () {
            it('Check for defined', function () {
                expect(shipping.initObservable).toBeDefined();
                expect(shipping.initObservable).toEqual(jasmine.any(Function));
            });

            it('Check called "this.observe" method', function () {
                shipping.observe = jasmine.createSpy().and.callFake(function () {
                    return shipping;
                });
                shipping.initObservable();
                expect(shipping.observe).toHaveBeenCalled();
            });
        });

        describe('"initElement" method', function () {
            it('Check for defined', function () {
                expect(shipping.initElement).toBeDefined();
                expect(shipping.initElement).toEqual(jasmine.any(Function));
            });

            it('Check for return value and element that initiated.', function () {
                var element = jasmine.createSpyObj('element', ['initContainer']);

                expect(shipping.initElement(element)).toBeUndefined();
                expect(mocks['Magento_Checkout/js/model/shipping-rates-validator'].bindChangeHandlers)
                    .not.toHaveBeenCalled();
            });

            it('Check shipping rates validator call.', function () {
                var element = {
                    index: 'shipping-address-fieldset',
                    elems: ko.observable(),
                    initContainer: jasmine.createSpy()
                };

                spyOn(element.elems, 'subscribe');

                shipping.initElement(element);
                expect(mocks['Magento_Checkout/js/model/shipping-rates-validator'].bindChangeHandlers)
                    .toHaveBeenCalledWith(element.elems(), false);
            });
        });
    });
});
