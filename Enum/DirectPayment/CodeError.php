<?php

namespace GenComm\GenPay\Enum\DirectPayment;

/**
 * Class CodeError
 * @package GenComm\GenPay\Enum\DirectPayment
 */
class CodeError
{
    const ID_UNDEFINED = "undefined";
    const ID_CHARGE_NOT_FOUND = "charge_not_found";
    const ID_FORBIDDEN = "forbidden";
    const ID_REFUND_BLOCKED = "refund_blocked";
    const ID_PAYMENT_REQUIRED = "payment_required";
    const ID_INVALID_CHARGE_DATA = "invalid_charge_data";
    const ID_CHARGE_CANCEL = "charge_cancel";
    const ID_CANCELLATION_BLOCKED = "cancellation_blocked";
    const ID_CHARGE_CANNOT_BE_CANCELLED = "charge_cannot_be_cancelled";
    const ID_PAYMENT_AMOUNT_REQUIRED = "payment_amount_required";
    const ID_SELLER_NOT_FOUND = "seller_not_found";
    const ID_MERCHANT_NOT_FOUND = "merchant_not_found";
    const ID_CHARGE_VALIDATION = "charge_validation";
    const ID_CHARGE_ALREADY_EXISTS = "charge_already_exists";

    const CODE_UNDEFINED = 999;
    const CODE_CHARGE_NOT_FOUND = 1000;
    const CODE_FORBIDDEN = 1001;
    const CODE_REFUND_BLOCKED = 1002;
    const CODE_PAYMENT_REQUIRED = 1003;
    const CODE_INVALID_CHARGE_DATA = 1004;
    const CODE_CHARGE_CANCEL = 1005;
    const CODE_CANCELLATION_BLOCKED = 1006;
    const CODE_CHARGE_CANNOT_BE_CANCELLED = 1007;
    const CODE_PAYMENT_AMOUNT_REQUIRED = 1008;
    const CODE_SELLER_NOT_FOUND = 1009;
    const CODE_MERCHANT_NOT_FOUND = 1010;
    const CODE_CHARGE_VALIDATION = 1011;
    const CODE_CHARGE_ALREADY_EXISTS = 1012;
    
    const MESSAGE_UNDEFINED = "Algo de errado ocorreu.";
    const MESSAGE_CHARGE_NOT_FOUND = "Cobrança não encontrada.";
    const MESSAGE_FORBIDDEN = "Proibido.";
    const MESSAGE_REFUND_BLOCKED = "Recurso de Reembolso está bloqueado nessa conta.";
    const MESSAGE_PAYMENT_REQUIRED = "Parâmetro 'payments' é obrigatório e seu valor deve ser um array (Exemplo: [])";
    const MESSAGE_INVALID_CHARGE_DATA = "Data de Cobrança invalida.";
    const MESSAGE_CHARGE_CANCEL = "Algo de errado ocorreu ao cancelar a Cobrança.";
    const MESSAGE_CANCELLATION_BLOCKED = "Recurso de Cancelamento está bloqueado nessa conta.";
    const MESSAGE_CHARGE_CANNOT_BE_CANCELLED = "Cobrança não pode ser cancelada, faça um reembolso.";
    const MESSAGE_PAYMENT_AMOUNT_REQUIRED = "O valor do pagamento é necessário para os seguintes ids";
    const MESSAGE_SELLER_NOT_FOUND = "Vendedor não encontrado.";
    const MESSAGE_MERCHANT_NOT_FOUND = "Lojista não encontrado.";
    const MESSAGE_CHARGE_VALIDATION = "Validação identificou um error.";
    const MESSAGE_CHARGE_ALREADY_EXISTS = "Cobrança já existe com determinado status.";

    /**
     * @var array
     */
    private static $codeMapping = [
        self::CODE_UNDEFINED => self::MESSAGE_UNDEFINED,
        self::CODE_CHARGE_NOT_FOUND => self::MESSAGE_CHARGE_NOT_FOUND,
        self::CODE_FORBIDDEN => self::MESSAGE_FORBIDDEN,
        self::CODE_REFUND_BLOCKED => self::MESSAGE_REFUND_BLOCKED,
        self::CODE_PAYMENT_REQUIRED => self::MESSAGE_PAYMENT_REQUIRED,
        self::CODE_INVALID_CHARGE_DATA => self::MESSAGE_INVALID_CHARGE_DATA,
        self::CODE_CHARGE_CANCEL => self::MESSAGE_CHARGE_CANCEL,
        self::CODE_CANCELLATION_BLOCKED => self::MESSAGE_CANCELLATION_BLOCKED,
        self::CODE_CHARGE_CANNOT_BE_CANCELLED => self::MESSAGE_CHARGE_CANNOT_BE_CANCELLED,
        self::CODE_PAYMENT_AMOUNT_REQUIRED => self::MESSAGE_PAYMENT_AMOUNT_REQUIRED,
        self::CODE_SELLER_NOT_FOUND => self::MESSAGE_SELLER_NOT_FOUND,
        self::CODE_MERCHANT_NOT_FOUND => self::MESSAGE_MERCHANT_NOT_FOUND,
        self::CODE_CHARGE_VALIDATION => self::MESSAGE_CHARGE_VALIDATION,
        self::CODE_CHARGE_ALREADY_EXISTS => self::MESSAGE_CHARGE_ALREADY_EXISTS,

    ];

    /**
     * @var array
     */
    private static $idMapping = [
        self::ID_UNDEFINED => self::MESSAGE_UNDEFINED,
        self::ID_CHARGE_NOT_FOUND => self::MESSAGE_CHARGE_NOT_FOUND,
        self::ID_FORBIDDEN => self::MESSAGE_FORBIDDEN,
        self::ID_REFUND_BLOCKED => self::MESSAGE_REFUND_BLOCKED,
        self::ID_PAYMENT_REQUIRED => self::MESSAGE_PAYMENT_REQUIRED,
        self::ID_INVALID_CHARGE_DATA => self::MESSAGE_INVALID_CHARGE_DATA,
        self::ID_CHARGE_CANCEL => self::MESSAGE_CHARGE_CANCEL,
        self::ID_CANCELLATION_BLOCKED => self::MESSAGE_CANCELLATION_BLOCKED,
        self::ID_CHARGE_CANNOT_BE_CANCELLED => self::MESSAGE_CHARGE_CANNOT_BE_CANCELLED,
        self::ID_PAYMENT_AMOUNT_REQUIRED => self::MESSAGE_PAYMENT_AMOUNT_REQUIRED,
        self::ID_SELLER_NOT_FOUND => self::MESSAGE_SELLER_NOT_FOUND,
        self::ID_MERCHANT_NOT_FOUND => self::MESSAGE_MERCHANT_NOT_FOUND,
        self::ID_CHARGE_VALIDATION => self::MESSAGE_CHARGE_VALIDATION,
        self::ID_CHARGE_ALREADY_EXISTS => self::MESSAGE_CHARGE_ALREADY_EXISTS,

    ];

    /**
     * @param $code
     * @return bool|string
     */
    public static function getMessageByCode($code)
    {
        return isset(self::$codeMapping[$code]) ? self::$codeMapping[$code] : false;
    }

    /**
     * @param $id
     * @return bool|string
     */
    public static function getMessageById($id)
    {
        return isset(self::$idMapping[$id]) ? self::$idMapping[$id] : false;
    }
}
