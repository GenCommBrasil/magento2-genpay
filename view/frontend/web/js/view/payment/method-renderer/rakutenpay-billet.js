/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'underscore',
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Rakuten_RakutenPay/js/model/custom',
        'Rakuten_RakutenPay/js/model/billet',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function (
        _,
        $,
        ko,
        Component,
        custom,
        billet,
        additionalValidators
        ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Rakuten_RakutenPay/payment/billet',
                taxNumber: ''
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
                //Set credit card number to credit card data object
                this.taxNumber.subscribe(function (value) {

                    if (value === '' || value === null) {
                        return false;
                    }

                    generateFingerprint();
                    return true;
                    // result = cardNumberValidator(value);
                });
            },

             /** Returns send check to info */
            getInstruction: function() {
                return window.checkoutConfig.payment.moipboleto.instruction;
            },

            /** Returns payable to info */
            getDue: function() {
                return "TESTE DOS TESTES";
            },

            getCode: function() {
                return 'rakutenpay_billet';
            },
        });
    }
);