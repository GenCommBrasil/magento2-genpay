/**
 ************************************************************************
 * Copyright [2018] [RakutenConnector]
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
function updateCreditCardToken(creditCardNumber, creditCardMonth, creditCardYear) {
    var rpay = new RPay();
    if (creditCardNumber.length === 19 && creditCardMonth !== "" && creditCardYear !== "") {

        var container = document.getElementById("rakutenpay-cc-method-div");
        while (container.hasChildNodes()) {
            container.removeChild(container.lastChild);
        }
        var rpay_method = document.createElement("input");
        rpay_method.type = "hidden";
        rpay_method.setAttribute("data-rkp", "method");
        rpay_method.value = "credit_card";
        container.appendChild(rpay_method);

        //Gets the form element
        var form = rpay_method.form;

        //Generates the token
        var creditCardTokenField = document.getElementById("rakutenpay_credit_card_creditCardToken");
        var creditCardBrandField = document.getElementById('rakutenpay_credit_card_creditCardBrand');

        var elements = {
            "form": form,
            "card-number": document.querySelector("#rakutenpay_credit_card_creditCardNumber"),
            "card-cvv": document.querySelector("#rakutenpay_credit_card_creditCardCode"),
            "expiration-month": document.querySelector('#rakutenpay_credit_card_creditCardExpirationMonth'),
            "expiration-year": document.querySelector('#rakutenpay_credit_card_creditCardExpirationYear')
        };

        rpay.tokenize(elements, function(error, data) {
            if (error) {
                console.log("Dados de cartão inválidos", error);
                return;
            }
            creditCardTokenField.value = data.cardToken;
            creditCardBrandField.value = rpay.cardBrand(elements["card-number"].value);
        });

        return true;
    }
}

function validateCreditCardNumber(value) {
    if (removeNumbers(unmask(value)) === "" && (value.length >= 14 && value.length <= 22)) {
        var rpay = new RPay();
        cardValidate = rpay.cardValidate(unmask(value));
        if (cardValidate.valid) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function validateCardDate() {
    var monthField = document.getElementById('rakutenpay_credit_card_creditCardExpirationMonth');
    var yearField = document.getElementById('rakutenpay_credit_card_creditCardExpirationYear');

    if (!monthField.validity.valid) {
        return false;
    }
    if (!yearField.validity.valid) {
        return false;
    }

    var month = monthField.value;
    var year = yearField.value;
    var rpay = new RPay();

    var valid = rpay.cardExpirationValidate(year, month);
    if (!valid) {
        return false;
    } else {
        return true;
    }
}

function getBrand(self) {
    if (validateCreditCardNumber(self.value)) {
        var rpay = new RPay();
        brand = rpay.cardBrand(unmask(self.value));
        document.getElementById('rakutenpay_credit_card_creditCardBrand').value = brand;
    }
}

/**
 * Return the value of 'el' without letters
 * @param {string} el
 * @returns {string}
 */
function removeLetters(el) {
    return el.replace(/[a-zA-ZçÇ]/g, '');
}

/**
 * Return the value of 'el' without numbers
 * @param {string} el
 * @returns {string}
 */
function removeNumbers(el) {
    return el.replace(/[0-9]/g, '');
}