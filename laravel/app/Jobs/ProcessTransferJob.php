<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\CryptoBalanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job асинхронной обработки перевода между пользователями.
 *
 * Выполняет перевод через CryptoBalanceService внутри очереди.
 * При исключении (например InsufficientBalanceException) транзакция БД откатывается,
 * балансы не изменяются.
 */
class ProcessTransferJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Количество попыток выполнения job.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * @param int $fromUserId ID пользователя-отправителя
     * @param int $toUserId ID пользователя-получателя
     * @param string $amount Сумма перевода
     */
    public function __construct(
        public int $fromUserId,
        public int $toUserId,
        public string $amount,
    ) {}

    /**
     * Выполнить перевод через CryptoBalanceService.
     *
     * При любом исключении транзакция внутри сервиса откатывается.
     */
    public function handle(CryptoBalanceService $service): void
    {
        $from = User::findOrFail($this->fromUserId);
        $to = User::findOrFail($this->toUserId);
        $service->transfer($from, $to, $this->amount);
    }
}
