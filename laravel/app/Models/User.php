<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Класс модели пользователя системы.
 *
 * Представляет зарегистрированного пользователя банка и его основные данные.
 *
 * @property int $id Идентификатор пользователя
 * @property string $name Имя пользователя
 * @property string $email Электронная почта пользователя
 * @property string $password Хэш пароля пользователя
 * @property string $balance Баланс пользователя в условных единицах
 * @property \Illuminate\Support\Carbon|null $email_verified_at Дата подтверждения email
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $transactions Коллекция транзакций пользователя
 */
class User extends Authenticatable
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
}
