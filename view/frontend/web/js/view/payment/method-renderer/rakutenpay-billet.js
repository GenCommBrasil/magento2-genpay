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
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate'
    ],
    function (
        _,
        $,
        ko,
        Component,
        custom,
        additionalValidators
        ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Rakuten_RakutenPay/payment/billet',
                taxNumber: '',
                $fingerprint: null,
                fingerprintSelector: '#rakutenpay_billet_fingerprint'
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
                return window.checkoutConfig.payment.rakutenpay_billet.instruction;
            },

            /** Returns payable to info */
            getDue: function() {
                return window.checkoutConfig.payment.rakutenpay_billet.due;
            },

            getCode: function() {
                return window.checkoutConfig.payment.rakutenpay_billet.code;
            },

            /** Return Title */
            getTitle: function() {
                return window.checkoutConfig.payment.rakutenpay_billet.title;
            },

            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);