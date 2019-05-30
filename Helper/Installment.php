<?php

namespace Rakuten\RakutenPay\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Rakuten\Connector\Exception\RakutenException;

/**
 * Class Installment
 * @package Rakuten\RakutenPay\Helper
 */
class Installment
{
    const DEFAULT_MINIMUM_VALUE = 10.0;
    const DEFAULT_INSTALLMENTS = 1;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $rakutenHelper;

    /**
     * Installments constructor.
     * @param Data $rakutenHelper
     */
    public function __construct(Data $rakutenHelper)
    {
        $this->rakutenHelper = $rakutenHelper;
    }

    /**
     * Get the bigger installments list returned by the RakutenPay service
     *
     * @param $amount
     * @return bool|mixed
     * @throws RakutenException
     */
    public function create($amount)
    {
        try {
            $minimumValue = $this->rakutenHelper->getMinimumInstallmentsValue();
            $maximumInstallments = $this->rakutenHelper->getMaxInstallmentsQuantity();
            $installments = $this->createInstallments($amount, $minimumValue, $maximumInstallments);

            return $installments;
        } catch (RakutenException $exception) {
            //TODO Log Implements

            return false;
        }
    }

    /**
     * Returns the maximum number of installments
     * @param $amount
     * @param $minimumValue
     * @param $maximumInstallments
     * @return float
     */
    private function getMaxNoInstallments($amount, $minimumValue, $maximumInstallments)
    {
        $installments = $this->getInstallmentsByMinimumValue($amount, $minimumValue);
        if (!empty($maximumInstallments) && $maximumInstallments > 0) {
            if ($installments > $maximumInstallments) {
                return $maximumInstallments;
            }
        }

        return $installments;
    }

    /**
     * @param $amount
     * @param $minimumValue
     * @return float
     */
    private function getInstallmentsByMinimumValue($amount, $minimumValue)
    {
        if (is_null($minimumValue) || is_nan($minimumValue) || $minimumValue < 0) {
            $minimumValue = self::DEFAULT_MINIMUM_VALUE;
        }
        $installments = floor ($amount / (float) $minimumValue);
        if ($amount <= (float) $minimumValue || false === $this->rakutenHelper->isInstallments()) {
            return self::DEFAULT_INSTALLMENTS;
        }

        return $installments;
    }

    /**
     * @param $amount
     * @param $minimumValue
     * @param $maximumInstallments
     * @return array
     * @throws RakutenException
     */
    private function createInstallments($amount, $minimumValue, $maximumInstallments)
    {
        $installments = [];
        if ($this->rakutenHelper->isCustomerInterest()) {
            $minimumInstallment = (int) $this->rakutenHelper->getCustomerInterestMinimum();
            $rakutenPay = $this->rakutenHelper->getRakutenPay();
            $customerInterestInstallments = $rakutenPay->checkout($amount);

            foreach($customerInterestInstallments->getInstallments() as $installment) {

                $quantity = $installment['quantity'];
                if ($quantity >= $minimumInstallment) {
                    $installments[$quantity]['quantity'] = $quantity;
                    $installments[$quantity]['amount'] = $installment['installment_amount'];
                    $installments[$quantity]['total_amount'] = $installment['total'];
                    $installments[$quantity]['interest_amount'] = $installment['interest_amount'];
                    $installments[$quantity]['interest_percent'] = $installment['interest_percent'];
                    $installments[$quantity]['text'] = str_replace('.', ',', $this->getInstallmentText
                    (
                        $installment['installment_amount'], $quantity, $installment['total'], false)
                    );
                } else {
                    $value = $amount / $quantity;
                    $value = ceil($value * 100) / 100;// rounds up to the nearest cent
                    $total = $value * $quantity;
                    $total = ceil($total * 100) / 100;
                    $installments[$quantity]['quantity'] = $quantity;
                    $installments[$quantity]['amount'] = $value;
                    $installments[$quantity]['total_amount'] = $total;
                    $installments[$quantity]['interest_amount'] = 0.0;
                    $installments[$quantity]['interest_percent'] = 0.0;
                    $installments[$quantity]['text'] = str_replace('.', ',', $this->getInstallmentText
                    (
                        $value, $quantity, $amount, true)
                    );
                }
            }
        } else {
            $maxNoInstallments = $this->getMaxNoInstallments($amount, $minimumValue, $maximumInstallments);
            for ($quantity = 1; $quantity <= $maxNoInstallments; $quantity++) {
                $value = $amount / $quantity;
                $value = ceil($value * 100) / 100;// rounds up to the nearest cent
                $total = $value * $quantity;
                $total = ceil($total * 100) / 100;
                $installments[$quantity]['quantity'] = $quantity;
                $installments[$quantity]['amount'] = $value;
                $installments[$quantity]['total_amount'] = $total;
                $installments[$quantity]['interest_amount'] = 0.0;
                $installments[$quantity]['interest_percent'] = 0.0;
                $installments[$quantity]['text'] = str_replace('.', ',', $this->getInstallmentText
                (
                    $value, $quantity, $amount,true)
                );
            }
        }

        return $installments;
    }

    /**
     * Mount the text message of the installment
     *
     * @param $amount
     * @param $quantity
     * * @param $total
     * @param $interestFree
     * @return string
     */
    private function getInstallmentText($amount, $quantity, $total, $interestFree)
    {
        return sprintf(
            "%s x de R$ %.2f %s juros %s",
            $quantity,
            $amount,
            $this->getInterestFreeText($interestFree),
            $this->getTotalText($total)
        );
    }

    /**
     * Get the string relative to if it is an interest free or not
     *
     * @param string $interestFree
     *
     * @return string
     */
    private function getInterestFreeText($interestFree)
    {
        return ($interestFree === true) ? 'sem' : 'com';
    }

    /**
     * @param $total
     * @return string
     */
    private function getTotalText($total)
    {
        return sprintf('- Valor Total R$ %.2f', $total);
    }
}