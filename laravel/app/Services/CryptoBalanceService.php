<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Сервис операций с крипто-балансом пользователей.
 *
 * Обеспечивает атомарность и защиту от гонок (lockForUpdate).
 * Все изменения отражаются только в таблице transactions; поле balance пользователя
 * обновляется через TransactionObserver только при статусе транзакции «completed».
 */
class CryptoBalanceService
{
    /**
     * Пополнение баланса пользователя.
     *
     * Атомарно увеличивает баланс и создаёт запись транзакции со статусом completed.
     * Точность суммы — до 18 знаков после запятой (decimal:18).
     *
     * @param User $user Пользователь, которому начисляется сумма
     * @param string $amount Положительная сумма пополнения (строка для точности decimal)
     * @return Transaction Созданная запись транзакции (type=deposit, status=completed)
     */
    public function deposit(User $user, string $amount): Transaction
    {
        $amount = $this->normalizeAmount($amount);
        if (bccomp($amount, '0', 18) <= 0) {
            throw new \InvalidArgumentException('Сумма пополнения должна быть положительной.');
        }

        return DB::transaction(function () use ($user, $amount): Transaction {
            $u = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            return Transaction::create([
                'user_id' => $u->id,
                'type' => 'deposit',
                'amount' => $amount,
                'status' => 'completed',
                'metadata' => null,
            ]);
        });
    }

    /**
     * Списание с баланса пользователя.
     *
     * Атомарно уменьшает баланс и создаёт запись транзакции.
     * При недостатке средств выбрасывает InsufficientBalanceException.
     *
     * @param User $user Пользователь, с которого списывается сумма
     * @param string $amount Положительная сумма списания
     * @return Transaction Созданная запись транзакции (type=withdraw, status=completed)
     * @throws InsufficientBalanceException Если баланс меньше суммы списания
     */
    public function withdraw(User $user, string $amount): Transaction
    {
        $amount = $this->normalizeAmount($amount);
        if (bccomp($amount, '0', 18) <= 0) {
            throw new \InvalidArgumentException('Сумма списания должна быть положительной.');
        }

        return DB::transaction(function () use ($user, $amount): Transaction {
            $u = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            $current = $u->getCalculatedBalance();
            if (bccomp($current, $amount, 18) < 0) {
                throw new InsufficientBalanceException('Недостаточно средств на балансе.');
            }
            return Transaction::create([
                'user_id' => $u->id,
                'type' => 'withdraw',
                'amount' => $amount,
                'status' => 'completed',
                'metadata' => null,
            ]);
        });
    }

    /**
     * Перевод средств между двумя пользователями.
     *
     * Атомарно списывает сумму с отправителя и зачисляет получателю.
     * Создаёт две записи транзакций (transfer): исходящий у отправителя, входящий у получателя (direction + counterparty_user_id).
     * При недостатке средств у отправителя выбрасывает InsufficientBalanceException.
     *
     * @param User $fromUser Отправитель
     * @param User $toUser Получатель
     * @param string $amount Сумма перевода (положительная)
     * @return array{0: Transaction, 1: Transaction} [транзакция отправителя, транзакция получателя]
     * @throws InsufficientBalanceException Если у отправителя недостаточно средств
     */
    public function transfer(User $fromUser, User $toUser, string $amount): array
    {
        $amount = $this->normalizeAmount($amount);
        if (bccomp($amount, '0', 18) <= 0) {
            throw new \InvalidArgumentException('Сумма перевода должна быть положительной.');
        }
        if ($fromUser->id === $toUser->id) {
            throw new \InvalidArgumentException('Нельзя перевести средства самому себе.');
        }

        return DB::transaction(function () use ($fromUser, $toUser, $amount): array {
            $ids = collect([$fromUser->id, $toUser->id])->sort()->values()->all();
            $locked = User::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $from = $locked->firstWhere('id', $fromUser->id);
            $to = $locked->firstWhere('id', $toUser->id);
            if (! $from || ! $to) {
                throw new \RuntimeException('Пользователь не найден.');
            }

            $fromBalance = $from->getCalculatedBalance();
            if (bccomp($fromBalance, $amount, 18) < 0) {
                throw new InsufficientBalanceException('Недостаточно средств на балансе.');
            }

            return Transaction::createTransferPair($from, $to, $amount, 'completed');
        });
    }

    /**
     * Нормализация строки суммы до 18 знаков после запятой.
     */
    private function normalizeAmount(string $amount): string
    {
        $amount = trim($amount);
        if ($amount === '') {
            throw new \InvalidArgumentException('Сумма не указана.');
        }
        if (! preg_match('/^-?\d+(\.\d+)?$/', $amount)) {
            throw new \InvalidArgumentException('Некорректный формат суммы.');
        }
        return number_format((float) $amount, 18, '.', '');
    }
}
