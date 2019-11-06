/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'GenComm_GenPay/js/model/custom',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate'
    ],
    function (
        $,
        Component,
        quote,
        fullScreenLoader,
        setPaymentInformationAction,
        placeOrder,
        custom,
        additionalValidators,
        translate
        ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'GenComm_GenPay/payment/billet',
                taxNumber: '',
                $fingerprint: null,
                fingerprintSelector: '#genpay_billet_fingerprint'
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'taxNumber'
                    ]);

                return this;
            },

            initialize: function () {
                this._super();

                var self = this;
                this.taxNumber.subscribe(function (value) {
                    self.$fingerprint = $(self.fingerprintSelector);

                    if (value === '' || value === null) {
                        return false;
                    }

                    if (self.$fingerprint.val() === '') {
                        generateFingerprint();
                    }

                    return true;
                });
            },

            /** Returns send check to info */
            getInstruction: function() {
                return window.checkoutConfig.payment.genpay_billet.instruction;
            },

            /** Returns payable to info */
            getDue: function() {
                return window.checkoutConfig.payment.genpay_billet.due;
            },

            getCode: function() {
                return window.checkoutConfig.payment.genpay_billet.code;
            },

            /** Return Title */
            getTitle: function() {
                return window.checkoutConfig.payment.genpay_billet.title;
            },

            /**
             * @override
             */
            placeOrder: function () {
                var self = this;
                var paymentData = quote.paymentMethod();
                var messageContainer = this.messageContainer;
                fullScreenLoader.startLoader();
                this.isPlaceOrderActionAllowed(false);
                if (! self.validate()) {
                    fullScreenLoader.stopLoader();
                    this.isPlaceOrderActionAllowed(true);
                    return;
                }

                $.when(setPaymentInformationAction(this.messageContainer, {
                    'method': self.getCode(),
                    'additional_data': {
                        'billet_document': jQuery('#'+this.getCode()+'_tax_number').val(),
                        'fingerprint': jQuery('#'+this.getCode()+'_fingerprint').val()
                    }
                })).done(function () {
                    delete paymentData['title'];
                    $.when(placeOrder(paymentData, messageContainer)).done(function () {
                        $.mage.redirect(window.checkoutConfig.payment.genpay_billet.url);
                    });
                }).fail(function () {
                    self.isPlaceOrderActionAllowed(true);
                }).always(function(){
                    fullScreenLoader.stopLoader();
                });
            },

            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);