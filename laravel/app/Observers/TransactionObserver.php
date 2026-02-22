<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Transaction;

/**
 * Наблюдатель за моделью Transaction.
 *
 * Обновляет баланс пользователя только когда транзакция получает статус «завершена» (completed).
 * Вызов recalculateBalanceFromTransactions() выполняется только в этом случае.
 */
class TransactionObserver
{
    /**
     * Обработка после создания или обновления транзакции.
     * Пересчёт баланса — только при статусе completed.
     */
    public function saved(Transaction $transaction): void
    {
        if ($transaction->status !== 'completed') {
            return;
        }

        $transaction->user?->recalculateBalanceFromTransactions();
    }
}
