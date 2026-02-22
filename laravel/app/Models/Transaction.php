<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Класс модели банковской транзакции.
 *
 * Представляет финансовую операцию пользователя: пополнение, списание, перевод или комиссию.
 *
 * @property int $id Идентификатор транзакции
 * @property int $user_id Идентификатор пользователя, к которому относится транзакция
 * @property string $type Тип транзакции (deposit, withdraw, transfer, commission)
 * @property string|null $direction Для transfer: 'in' (входящий) или 'out' (исходящий)
 * @property int|null $counterparty_user_id Для transfer: id контрагента (от кого / кому)
 * @property string $amount Сумма транзакции в условных единицах
 * @property string $status Статус транзакции (pending, completed, failed)
 * @property array|null $metadata Дополнительные данные транзакции в формате массива
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время создания транзакции
 * @property \Illuminate\Support\Carbon|null $updated_at Дата и время обновления транзакции
 * @property-read \App\Models\User $user Пользователь, связанный с транзакцией
 * @property-read \App\Models\User|null $counterparty Для transfer: контрагент (от кого / кому)
 */
class Transaction extends Model
{
    use HasFactory;

    /**
     * Имя связанной таблицы базы данных.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * Атрибуты, которые можно массово заполнять.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'direction',
        'counterparty_user_id',
        'amount',
        'status',
        'metadata',
    ];

    /**
     * Получить массив преобразований типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:18',
            'metadata' => 'array',
        ];
    }

    /**
     * Получить пользователя, которому принадлежит транзакция.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, \App\Models\Transaction>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Для переводов: контрагент (другой пользователь). Для остальных типов — null.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, \App\Models\Transaction>
     */
    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counterparty_user_id');
    }

    /**
     * Создать пару транзакций перевода: от пользователя $from пользователю $to.
     * Чётко задаёт «от кого» и «кому»: исходящая запись у отправителя, входящая у получателя.
     *
     * @param User $from От кого перевод
     * @param User $to Кому перевод
     * @param string $amount Сумма (положительная)
     * @param string $status Статус (pending, completed, failed)
     * @param \DateTimeInterface|null $createdAt Дата создания (для сидера — одна на пару)
     * @return array{0: Transaction, 1: Transaction} [исходящая транзакция, входящая транзакция]
     */
    public static function createTransferPair(
        User $from,
        User $to,
        string $amount,
        string $status = 'completed',
        ?\DateTimeInterface $createdAt = null
    ): array {
        $attrs = [
            'amount' => $amount,
            'status' => $status,
            'metadata' => null,
        ];
        if ($createdAt !== null) {
            $attrs['created_at'] = $createdAt;
            $attrs['updated_at'] = $createdAt;
        }

        $txOut = self::create(array_merge($attrs, [
            'user_id' => $from->id,
            'type' => 'transfer',
            'direction' => 'out',
            'counterparty_user_id' => $to->id,
        ]));
        $txIn = self::create(array_merge($attrs, [
            'user_id' => $to->id,
            'type' => 'transfer',
            'direction' => 'in',
            'counterparty_user_id' => $from->id,
        ]));

        return [$txOut, $txIn];
    }

    /**
     * Для переводов: описание контрагента — «Кому: Имя (email)» или «От: Имя (email)».
     *
     * @return string|null
     */
    public function getCounterpartyDescriptionAttribute(): ?string
    {
        if ($this->type !== 'transfer' || $this->counterparty_user_id === null) {
            return null;
        }
        $counterparty = $this->counterparty;
        if (! $counterparty) {
            return null;
        }
        $label = $counterparty->name . ' (' . $counterparty->email . ')';

        return $this->direction === 'out' ? 'Кому: ' . $label : 'От: ' . $label;
    }

    /**
     * Для переводов: email контрагента.
     *
     * @return string|null
     */
    public function getCounterpartyEmailAttribute(): ?string
    {
        if ($this->type !== 'transfer') {
            return null;
        }

        return $this->counterparty?->email;
    }
}

