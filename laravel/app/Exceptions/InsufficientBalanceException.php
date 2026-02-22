<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Исключение при недостаточном балансе для списания или перевода.
 *
 * Выбрасывается сервисом CryptoBalanceService при попытке списать сумму,
 * превышающую текущий баланс пользователя.
 */
class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = 'Недостаточно средств на балансе.')
    {
        parent::__construct($message);
    }
}
