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

/** Generate Fingerprint */
function generateFingerprint() {
    var rpay = new RPay();
    var fingerprintFields = document.querySelectorAll(".rakutenFingerprint");
    rpay.fingerprint(function(error, fingerprint) {
        if (error) {
            console.log("Erro ao gerar fingerprint", error);
            return;
        }
        for (var i = 0; i < fingerprintFields.length; i++) {
            fingerprintFields[i].value = fingerprint;
        }
    });
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
    var creditCardNumber = document.getElementById("rakutenpay_credit_card_creditCardNumber");
    creditCardNumber.value = cc.value;
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