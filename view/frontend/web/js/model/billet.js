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

function generateFingerprint() {
    var rpay = new RPay();
    console.log("call generateFingerprint");
    var fingerprintFields = document.querySelectorAll(".rakutenFingerprint");
    rpay.fingerprint(function(error, fingerprint) {
        if (error) {
            console.log("Erro ao gerar fingerprint", error);
            return;
        }
        console.log("complete generateFingerprint");
        for (var i = 0; i < fingerprintFields.length; i++) {
            fingerprintFields[i].value = fingerprint;
        }
    });
}