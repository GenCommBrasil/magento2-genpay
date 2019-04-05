<?php
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

namespace Rakuten\RakutenPay\Model\Config\Source;

/**
 * Class MinimumInterestInstallment
 * @package Rakuten\RakutenPay\Model\Config\Source
 */
class MinimumInterestInstallment
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => '1'],
            ['value' => 2, 'label' => '2'],
            ['value' => 3, 'label' => '3'],
            ['value' => 4, 'label' => '4'],
            ['value' => 5, 'label' => '5'],
            ['value' => 6, 'label' => '6'],
            ['value' => 7, 'label' => '7'],
            ['value' => 8, 'label' => '8'],
            ['value' => 9, 'label' => '9'],
            ['value' => 10, 'label' => '10'],
            ['value' => 11, 'label' => '11'],
            ['value' => 12, 'label' => '12'],
        ];
    }
}