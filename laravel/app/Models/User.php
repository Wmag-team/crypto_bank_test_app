<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Класс модели пользователя системы.
 *
 * Представляет зарегистрированного пользователя банка и его основные данные.
 *
 * Два аспекта баланса:
 * - Поле balance — хранимое значение, отображаемое в панели (обновляется методом recalculateBalanceFromTransactions).
 * - Реальный баланс — считается по завершённым транзакциям (пополнения, полученные переводы минус списания и отправленные переводы).
 * После любых операций с деньгами пользователя нужно вызывать recalculateBalanceFromTransactions(), чтобы поле balance совпадало с реальным балансом.
 *
 * @property int $id Идентификатор пользователя
 * @property string $name Имя пользователя
 * @property string $email Электронная почта пользователя
 * @property string $password Хэш пароля пользователя
 * @property string $balance Баланс пользователя в условных единицах (должен обновляться через recalculateBalanceFromTransactions)
 * @property \Illuminate\Support\Carbon|null $email_verified_at Дата подтверждения email
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $transactions Коллекция транзакций пользователя
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * Атрибуты, которые можно массово заполнять.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    /**
     * Атрибуты, которые должны быть скрыты при сериализации.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Получить массив преобразований типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:18',
        ];
    }

    /**
     * Получить транзакции, связанные с пользователем.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Transaction>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Проверка доступа к панели Filament.
     * Доступ есть и у админа, и у обычных пользователей.
     * Разделение функционала (админ — шире, пользователи — свой) делается в ресурсах и страницах.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Является ли пользователь администратором (admin@mail.com).
     * Используется для расширенного функционала в Filament.
     */
    public function isAdmin(): bool
    {
        return $this->email === 'admin@mail.com';
    }

    /**
     * Рассчитывает реальный баланс пользователя по завершённым транзакциям.
     *
     * Учитываются: пополнения (+), списания (-), входящие переводы (+), исходящие переводы (-), комиссии (-).
     * Только транзакции со статусом completed. Точность — 18 знаков после запятой.
     *
     * @return string Баланс в виде строки (decimal:18)
     */
    public function getCalculatedBalance(): string
    {
        $rows = $this->transactions()
            ->where('status', 'completed')
            ->get(['type', 'direction', 'amount']);

        $total = '0';
        foreach ($rows as $tx) {
            $amount = (string) $tx->amount;
            $sign = match ($tx->type) {
                'deposit' => 1,
                'withdraw' => -1,
                'transfer' => $tx->direction === 'in' ? 1 : -1,
                'commission' => -1,
                default => 0,
            };
            if ($sign !== 0) {
                $total = $sign > 0 ? bcadd($total, $amount, 18) : bcsub($total, $amount, 18);
            }
        }

        return $total;
    }

    /**
     * Пересчитывает реальный баланс по транзакциям и обновляет поле balance у пользователя.
     *
     * Вызывать после каждого изменения, связанного с деньгами пользователя (перевод, пополнение, списание),
     * чтобы отображаемый баланс в панели всегда соответствовал сумме по транзакциям.
     */
    public function recalculateBalanceFromTransactions(): void
    {
        $this->balance = $this->getCalculatedBalance();
        $this->save();
    }
}
