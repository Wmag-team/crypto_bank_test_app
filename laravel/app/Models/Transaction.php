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
 * @property string $amount Сумма транзакции в условных единицах
 * @property string $status Статус транзакции (pending, completed, failed)
 * @property array|null $metadata Дополнительные данные транзакции в формате массива
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время создания транзакции
 * @property \Illuminate\Support\Carbon|null $updated_at Дата и время обновления транзакции
 * @property-read \App\Models\User $user Пользователь, связанный с транзакцией
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
}

