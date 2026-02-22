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
 * @property int $id Идентификатор пользователя
 * @property string $name Имя пользователя
 * @property string $email Электронная почта пользователя
 * @property string $password Хэш пароля пользователя
 * @property string $balance Баланс пользователя в условных единицах
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
}
