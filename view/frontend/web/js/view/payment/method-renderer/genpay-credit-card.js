/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/checkout-data',
        'GenComm_GenPay/js/model/custom',
        'GenComm_GenPay/js/model/credit-card',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate'
    ],
    function (

        $,
        ko,
        Component,
        quote,
        fullScreenLoader,
        setPaymentInformationAction,
        placeOrder,
        checkoutData,
        custom,
        creditCard,
        additionalValidators,
        translate) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'GenComm_GenPay/payment/credit-card',
                creditCardNumber: '',
                creditCardCode: '',
                creditCardExpirationMonth: '',
                creditCardExpirationYear: '',
                creditCardHolder: '',
                creditCardDocument: '',
                creditCardInstallments: '',
                $fingerprint: null,
                $installments: null,
                fingerprintSelector: '#genpay_credit_card_fingerprint',
                creditCardTokenSelector: '#genpay_credit_card_creditCardToken',
                creditCardNumberSelector: '#genpay_credit_card_creditCardNumber',
                creditCardExpirationMonthSelector: '#genpay_credit_card_creditCardExpirationMonth',
                creditCardExpirationYearSelector: '#genpay_credit_card_creditCardExpirationYear',
                creditCardInstallmentSelector: '#genpay_credit_card_creditCardInstallment',
                creditCardInstallmentValueSelector: '#genpay_credit_card_creditCardInstallmentValue',
                creditCardInterestPercentSelector: '#genpay_credit_card_creditCardInterestPercent',
                creditCardInterestAmountSelector: '#genpay_credit_card_creditCardInterestAmount',
                creditCardInstallmentTotalValueSelector: '#genpay_credit_card_creditCardInstallmentTotalValue',
            },

            getCode: function() {
                return 'genpay_credit_card';
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'creditCardNumber',
                        'creditCardCode',
                        'creditCardExpirationMonth',
                        'creditCardExpirationYear',
                        'creditCardHolder',
                        'creditCardDocument',
                        'creditCardInstallments'
                    ]);

                return this;
            },

            initialize: function () {
                this._super();

                var self = this;
                this.creditCardNumber.subscribe(function (value) {
                    self.$fingerprint = $(self.fingerprintSelector);

                    if (self.$fingerprint.val() === '') {
                        generateFingerprint();
                    }

                    if (value.length === 19) {
                        updateCreditCardToken(value, $(self.creditCardExpirationMonthSelector).val(), $(self.creditCardExpirationYearSelector).val());
                    }
                });

                this.creditCardCode.subscribe(function (value) {
                    if (self.$installments === null) {
                        self.getInstallments();
                    }
                });

                this.creditCardExpirationMonth.subscribe(function (value) {
                    if (value !== '') {
                        updateCreditCardToken($(self.creditCardNumberSelector).val(), value, $(self.creditCardExpirationYearSelector).val());
                    }
                });

                this.creditCardExpirationYear.subscribe(function (value) {
                    if (value !== '') {
                        updateCreditCardToken($(self.creditCardNumberSelector).val(), $(self.creditCardExpirationMonthSelector).val(), value);
                    }
                });

                this.creditCardExpirationYear.subscribe(function (value) {
                    if (value !== '') {
                        updateCreditCardToken($(self.creditCardNumberSelector).val(), $(self.creditCardExpirationMonthSelector).val(), value);
                    }
                });

                this.creditCardInstallments.subscribe(function (value) {
                    if (value !== '' || value !== null) {
                        $(self.creditCardInstallmentSelector).val(value);
                        $(self.creditCardInstallmentValueSelector).val(self.$installments[value].amount);
                        $(self.creditCardInterestPercentSelector).val(self.$installments[value].interest_percent);
                        $(self.creditCardInterestAmountSelector).val(self.$installments[value].interest_amount);
                        $(self.creditCardInstallmentTotalValueSelector).val(self.$installments[value].total_amount);
                    }
                });
            },

            getYearValues: function () {
                return _.map(window.checkoutConfig.payment.genpay_credit_card.year_values, function (value, key) {
                    return {
                        'value': key,
                        'year': value
                    };
                });
            },

            getInstallments: function() {
                var self = this;
                var baseGrandTotal = quote.totals().base_grand_total;
                var url = window.checkoutConfig.payment.genpay_credit_card.installment_url;
                var data = {'baseGrandTotal' : baseGrandTotal};
                $.ajax({
                    url: url,
                    type: "POST",
                    data: data,
                    cache: false,
                    async: false,
                    success: function(response) {
                        if (response.error) {
                            console.log("Error for create Installments. Error: " + response.error.msg);
                            return;
                        }
                        var select = $('#' + self.getCode() + '_installments');
                        $.each(response, function (key, value) {
                            select.append($("<option />").val(value.quantity).text(value.text));
                        });
                        self.$installments = response;
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log(textStatus, errorThrown);
                    }
                });
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
                        'fingerprint': jQuery('#' + this.getCode() + '_fingerprint').val(),
                        'credit_card_token': jQuery('#' + this.getCode() + '_creditCardToken').val(),
                        'credit_card_code': jQuery('#' + this.getCode() + '_creditCardCode').val(),
                        'credit_card_holder': jQuery('#' + this.getCode() + '_creditCardHolder').val(),
                        'credit_card_document': jQuery('#' + this.getCode() + '_creditCardDocument').val(),
                        'credit_card_brand': jQuery('#' + this.getCode() + '_creditCardBrand').val(),
                        'credit_card_installment': jQuery('#' + this.getCode() + '_creditCardInstallment').val(),
                        'credit_card_installment_value': jQuery('#' + this.getCode() + '_creditCardInstallmentValue').val(),
                        'creditCard_interest_percent': jQuery('#' + this.getCode() + '_creditCardInterestPercent').val(),
                        'credit_card_interest_amount': jQuery('#' + this.getCode() + '_creditCardInterestAmount').val(),
                        'credit_card_installment_total_value': jQuery('#' + this.getCode() + '_creditCardInstallmentTotalValue').val()
                    }
                })).done(function () {
                    delete paymentData['title'];
                    $.when(placeOrder(paymentData, messageContainer)).done(function () {
                        $.mage.redirect(window.checkoutConfig.payment.genpay_credit_card.url);
                    });
                }).fail(function () {
                    self.isPlaceOrderActionAllowed(true);
                }).always(function(){
                    fullScreenLoader.stopLoader();
                });
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');

                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);
