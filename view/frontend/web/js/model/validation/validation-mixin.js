define([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate',
    'GenComm_GenPay/js/model/custom',
    'GenComm_GenPay/js/model/credit-card',
    'GenComm_GenPay/js/model/validation/payment-validation'
], function($) {
    'use strict';
    return function () {
        $.validator.addMethod(
            "validate-document",
            function (value, element) {

                return validateDocument(value);
            },
            $.mage.__("Digite um CPF/CNPJ válido.")
        );

        $.validator.addMethod(
            "validate-fingerprint",
            function (value, element) {

                if (isFingerprint() === false) {
                    generateFingerprint();

                    return false;
                }

                return true
            },
            $.mage.__("Ocorreu um problema ao validar o boleto. Por favor, tente novamente.")
        );

        $.validator.addMethod(
            "validate-credit-card-number",
            function (value, element) {

                if (value.indexOf('*') > -1) {

                    return true;
                }

                if (validateCreditCardNumber(value) === false) {

                    return false;
                }

                return true
            },
            $.mage.__("Insira um número de cartão válido.")
        );

        $.validator.addMethod(
            "validate-card-holder",
            function (value, element) {
                if (element.validity.tooShort || !element.validity.valid || removeLetters(unmask(element.value)) !== "") {
                    return false;
                }

                return true;
            },
            $.mage.__("Insira o nome conforme impresso no cartão.")
        );

        $.validator.addMethod(
            "validate-card-code",
            function (value, element) {
                var isValid = validateCardDate();

                if (!isValid) {
                    return false;
                }

                return true;
            },
            $.mage.__("Data de Expiração do cartão inválida.")
        );

        $.validator.addMethod(
            "validate-card-date",
            function (value, element) {
                if (element.validity.tooLong || element.validity.tooShort || !element.validity.valid) {
                    return false;
                }

                return true;
            },
            $.mage.__("Insira um código de segurança válido.")
        );

        $.validator.addMethod(
            "validate-credit-card-installments",
            function (value, element) {
                if (element.validity.valid && element.value !== "null" && element.value !== "") {
                    return true
                }

                return true;
            },
            $.mage.__("Escolha uma opção de parcelamento.")
        );

        $.validator.addMethod(
            "validate-genpay-form",
            function (value, element) {

                return $('#genpay_credit_card_fingerprint').val() !== "" &&
                    $('#genpay_credit_card_creditCardToken').val() !== "" &&
                    $('#genpay_credit_card_creditCardBrand').val() !== "" &&
                    $('#genpay_credit_card_creditCardInstallment').val() !== "" &&
                    $('#genpay_credit_card_creditCardInstallmentValue').val() !== "" &&
                    $('#genpay_credit_card_creditCardInterestPercent').val() !== "" &&
                    $('#genpay_credit_card_creditCardInterestAmount').val() !== "" &&
                    $('#genpay_credit_card_creditCardInstallmentTotalValue')
            },
            $.mage.__("Erro ao processar dados, verifique se todas as informações estão corretas e tente novamente.")
        );
    }
});