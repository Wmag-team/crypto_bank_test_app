<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\InsufficientBalanceException;
use App\Jobs\ProcessTransferJob;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CryptoBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature-тесты логики CryptoBalanceService.
 *
 * Проверяют достаточность средств, атомарность при сбое Job, поведение при конкурентном списании
 * и точность decimal при пополнении.
 */
class CryptoBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private CryptoBalanceService $CryptoBalanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CryptoBalanceService = app(CryptoBalanceService::class);
    }

    /**
     * Тест на достаточность средств: списание суммы больше текущего баланса
     * должно выбрасывать InsufficientBalanceException.
     *
     * @return void
     */
    public function test_withdraw_throws_exception_when_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => '100.00']);

        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('Недостаточно средств на балансе.');

        $this->CryptoBalanceService->withdraw($user, '150.00');
    }

    /**
     * Тест на достаточность средств: списание суммы равной балансу разрешено.
     *
     * @return void
     */
    public function test_withdraw_succeeds_when_balance_equals_amount(): void
    {
        $user = User::factory()->create(['balance' => '100.00']);
        $this->CryptoBalanceService->withdraw($user, '100.00');
        $user->refresh();
        $this->assertSame('0.000000000000000000', (string) $user->balance);
    }

    /**
     * Тест на атомарность: при сбое внутри Job (исключение в сервисе — недостаточно средств)
     * транзакция в БД откатывается и баланс не изменяется.
     *
     * @return void
     */
    public function test_transfer_rolls_back_on_exception_balance_unchanged(): void
    {
        $from = User::factory()->create(['balance' => '10.00']);
        $to = User::factory()->create(['balance' => '0']);
        $fromBalanceBefore = (string) $from->balance;
        $toBalanceBefore = (string) $to->balance;

        Queue::fake();
        ProcessTransferJob::dispatch($from->id, $to->id, '50.00');
        $jobs = Queue::pushed(ProcessTransferJob::class);
        $this->assertCount(1, $jobs);

        $job = $jobs[0];
        try {
            $job->handle(app(CryptoBalanceService::class));
        } catch (InsufficientBalanceException) {
            // ожидаем при недостатке средств
        }

        $from->refresh();
        $to->refresh();
        $this->assertSame($fromBalanceBefore, (string) $from->balance);
        $this->assertSame($toBalanceBefore, (string) $to->balance);
    }

    /**
     * Тест на атомарность: при выброшенном исключении в сервисе внутри DB::transaction
     * балансы и записи транзакций откатываются.
     *
     * @return void
     */
    public function test_transfer_rollback_on_insufficient_balance(): void
    {
        $from = User::factory()->create(['balance' => '10.00']);
        $to = User::factory()->create(['balance' => '0']);
        $countBefore = Transaction::count();

        try {
            $this->CryptoBalanceService->transfer($from, $to, '50.00');
        } catch (InsufficientBalanceException) {
            // ожидаем
        }

        $from->refresh();
        $to->refresh();
        $this->assertSame('10.000000000000000000', (string) $from->balance);
        $this->assertSame('0.000000000000000000', (string) $to->balance);
        $this->assertSame($countBefore, Transaction::count());
    }

    /**
     * Тест на race condition (блокировки): при последовательном выполнении
     * нескольких списаний всей суммы только первое успешно, остальные получают ошибку;
     * баланс не уходит в минус.
     *
     * @return void
     */
    public function test_only_one_full_withdraw_succeeds_balance_never_negative(): void
    {
        $user = User::factory()->create(['balance' => '100.00']);
        $successCount = 0;
        $failCount = 0;

        for ($i = 0; $i < 10; $i++) {
            try {
                $this->CryptoBalanceService->withdraw($user->fresh(), '100.00');
                $successCount++;
            } catch (InsufficientBalanceException) {
                $failCount++;
            }
        }

        $this->assertSame(1, $successCount);
        $this->assertSame(9, $failCount);
        $user->refresh();
        $this->assertTrue(bccomp((string) $user->balance, '0', 18) >= 0, 'Баланс не должен уйти в минус.');
    }

    /**
     * Тест на пополнение: корректное обновление decimal-значений с точностью до 18 знаков.
     *
     * @return void
     */
    public function test_deposit_updates_balance_with_18_decimal_precision(): void
    {
        $user = User::factory()->create(['balance' => '0']);
        $amount = '0.123456789012345678';

        $this->CryptoBalanceService->deposit($user, $amount);
        $user->refresh();
        $this->assertSame('0.123456789012345678', (string) $user->balance);

        $this->CryptoBalanceService->deposit($user, '0.000000000000000001');
        $user->refresh();
        $this->assertSame('0.123456789012345679', (string) $user->balance);
    }
}
