/**
 ************************************************************************
 * Copyright [2019] [RakutenConnector]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 ************************************************************************
 */
/*
 * This file have all the rakutenpay direct payment common functions, like
 * form input masks and  validations and calls to the rakutenpay js api
 */

/**
 * Validate document (cpf or cnpj) according with it's length
 * @param {type} self
 * @returns {Boolean}
 */
function validateDocument(self) {
    var value = unmask(self.value);
    if (value.length === 11) {
        return validateCpf(self);
    } else if (value.length === 14) {
        return validateCnpj(self);
    } else {
        return false;
    }
}

/**
 * Remove special characters, spaces
 * @param {type} el
 * @returns {unresolved}
 */
function unmask(el) {
    return el.replace(/[/ -. ]+/g, '').trim();
}

/**
 * Validate CPF
 * @param {object} self
 * @returns {Boolean}
 */
function validateCpf(self) {
    var cpf = unmask(self.value);
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
 * @param {object} self
 * @returns {Boolean}
 */
function validateCnpj(self) {
    var cnpj = unmask(self.value);
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

/**
 * Add mask for document (cpf or cnpj)
 * Important: Called on keyup event
 * @param {this} document
 * @returns {bool}
 */
function documentMask(event, document) {
    if (document.value.length < 14 ||
        (document.value.length == 14 && (event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 46))) {
        MaskCPF(event, document);
    } else {
        MaskCNPJ(event, document);
    }
}

/*
 * Mask functions below adapted from
 * http://www.fabiobmed.com.br/excelente-codigo-para-mascara-e-validacao-de-cnpj-cpf-cep-data-e-telefone/
 */

/**
 * Add CNPJ mask to input
 * @param {type} cnpj
 * @returns {Boolean}
 */
function MaskCNPJ(event, cnpj) {
    if (maskInteger(event, cnpj) == false) {
        event.returnValue = false;
    }
    return formatField(cnpj, '00.000.000/0000-00', event);
}

/**
 * Add CPF mask to input
 * @param {type} cnpj
 * @returns {Boolean}
 */
function MaskCPF(event, cpf) {
    maskInteger(event, cpf);
    return formatField(cpf, '000.000.000-00', event);
}

/**
 * Add credit card mask to input
 * @param {type} cnpj
 * @returns {Boolean}
 */
function creditCardMask(event, cc) {
    var creditCardNum = document.getElementById("creditCardNum");
    creditCardNum.value = cc.value;
    maskInteger(event, cc);
    return formatField(cc, '0000 0000 0000 0000', event);
}

/**
 * Add not number mask to input
 * @param {event} e
 * @returns {Boolean}
 */
function notNumberMask(e) {
    e = e || window.event;
    var keyCode = (typeof e.which == "undefined") ? e.keyCode : e.which;
    if (isIntegerKeyCode(keyCode)){
        e.preventDefault();
    }
}

function isIntegerKeyCode(keyCode){
    return (keyCode > 47 && keyCode < 58) || (keyCode > 95 && keyCode < 106);
}

function isIntegerAcceptedKeyCode(keyCode) {
    return isIntegerKeyCode(keyCode) || keyCode == 8 || keyCode == 9 || keyCode == 46 || keyCode == 37 || keyCode == 38 || keyCode == 39 || keyCode == 40;
}

function isCopyCommand(e) {
    var keyCode = (typeof e.which == "undefined") ? e.keyCode : e.which;
    var ctrlDown = e.ctrlKey || e.metaKey;

    if (ctrlDown && (keyCode == 65 || keyCode == 67 || keyCode == 86 || keyCode == 88)){
        return true;
    }

    return false;
}

/**
 * Validate and prevent key typed event if it is not an integer([48,57] || [96, 105]),
 * backspace(8), tab(9), or del(46)
 * @returns {Boolean}
 */
function maskInteger(e) {
    e = e || window.event;
    var keyCode = (typeof e.which == "undefined") ? e.keyCode : e.which;
    if (!isIntegerAcceptedKeyCode(keyCode) && !isCopyCommand(e)){
        e.preventDefault();
    }
}

/**
 * Format fields, according with the mask pattern
 * @param {type} field
 * @param {type} mask
 * @param {type} event
 * @returns {Boolean}
 */
function formatField(field, mask, event) {
    var maskBoolean;

    var digits = event.keyCode;
    exp = /\-|\.|\/|\(|\)| /g
    onlyNumbersField = field.value.toString().replace(exp, "");

    var fieldPosition = 0;
    var updatedFieldValue = "";
    var maskSize = onlyNumbersField.length;

    if (digits != 8) { // backspace
        for (i = 0; i <= maskSize; i++) {
            maskBoolean = ((mask.charAt(i) == "-") || (mask.charAt(i) == ".")
                || (mask.charAt(i) == "/"))
            maskBoolean = maskBoolean || ((mask.charAt(i) == "(")
                || (mask.charAt(i) == ")") || (mask.charAt(i) == " "))
            if (maskBoolean) {
                updatedFieldValue += mask.charAt(i);
                maskSize++;
            } else {
                updatedFieldValue += onlyNumbersField.charAt(fieldPosition);
                fieldPosition++;
            }
        }
        field.value = updatedFieldValue;
        return true;
    } else {
        return true;
    }
}

/**
 * Add credit card code mask to input
 * @param {type} cnpj
 * @returns {Boolean}
 */
function creditCardCodeMask(event, code) {
    if (maskInteger(event) == false) {
        event.returnValue = false;
    }
    return true;
}