define([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate',
    'Rakuten_RakutenPay/js/model/custom'
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
    }
});

/**
 * Validate document (cpf or cnpj) according with it's length
 * @param value
 * @returns {Boolean}
 */
function validateDocument(value) {
    var value = unmask(value.toString());
    if (value.length === 11) {
        return validateCpf(value);
    } else if (value.length === 14) {
        return validateCnpj(value);
    } else {
        return false;
    }
}

/**
 * Validate CPF
 * @param value
 * @returns {Boolean}
 */
function validateCpf(value) {
    var cpf = unmask(value);
    var numbers, digits, sum, i, result, equal_digits;
    equal_digits = 1;
    if (cpf.length < 11) {
        return false;
    }
    for (i = 0; i < cpf.length - 1; i++)
        if (cpf.charAt(i) != cpf.charAt(i + 1)) {
            equal_digits = 0;
            break;
        }
    if (!equal_digits) {
        numbers = cpf.substring(0, 9);
        digits = cpf.substring(9);
        sum = 0;
        for (i = 10; i > 1; i--) {
            sum += numbers.charAt(10 - i) * i;
        }
        result = sum % 11 < 2 ? 0 : 11 - sum % 11;
        if (result != digits.charAt(0)) {
            return false;
        }
        numbers = cpf.substring(0, 10);
        sum = 0;
        for (i = 11; i > 1; i--) {
            sum += numbers.charAt(11 - i) * i;
        }
        result = sum % 11 < 2 ? 0 : 11 - sum % 11;
        if (result != digits.charAt(1)) {
            return false;
        }
        return true;
    } else {
        return false;
    }
}

/**
 * Validates CNPJ
 * @param value
 * @returns {Boolean}
 */
function validateCnpj(value) {
    var cnpj = unmask(value);
    var numbersVal;
    var digits;
    var sum;
    var i;
    var result;
    var pos;
    var size;
    var equal_digits;
    equal_digits = 1;
    if (cnpj.length < 14 && cnpj.length < 15) {
        return false;
    }
    for (i = 0; i < cnpj.length - 1; i++) {
        if (cnpj.charAt(i) != cnpj.charAt(i + 1)) {
            equal_digits = 0;
            break;
        }
    }
    if (!equal_digits) {
        size = cnpj.length - 2;
        numbersVal = cnpj.substring(0, size);
        digits = cnpj.substring(size);
        sum = 0;
        pos = size - 7;
        for (i = size; i >= 1; i--) {
            sum += numbersVal.charAt(size - i) * pos--;
            if (pos < 2) {
                pos = 9;
            }
        }
        result = sum % 11 < 2 ? 0 : 11 - sum % 11;
        if (result != digits.charAt(0)) {
            return false;
        }
        size = size + 1;
        numbersVal = cnpj.substring(0, size);
        sum = 0;
        pos = size - 7;
        for (i = size; i >= 1; i--) {
            sum += numbersVal.charAt(size - i) * pos--;
            if (pos < 2) {
                pos = 9;
            }
        }
        result = sum % 11 < 2 ? 0 : 11 - sum % 11;
        if (result != digits.charAt(1)) {
            return false;
        }
        return true;
    } else {
        return false;
    }
}

function isFingerprint() {
    var fingerprint = document.getElementById("rakutenpay_billet_fingerprint");
    if (fingerprint.value !== "") {
        return true;
    }

    return false;
}

