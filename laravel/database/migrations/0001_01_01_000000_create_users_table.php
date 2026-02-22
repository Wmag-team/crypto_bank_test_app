<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Выполнить миграции.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id()
                ->comment('Первичный ключ пользователя');

            $table->string('name')
                ->comment('Имя пользователя');

            $table->string('email')
                ->unique()
                ->comment('Уникальный адрес электронной почты пользователя');

            $table->timestamp('email_verified_at')
                ->nullable()
                ->comment('Дата и время подтверждения email пользователя');

            $table->string('password')
                ->comment('Хэш пароля пользователя');

            $table->decimal('balance', 36, 18)
                ->default(0)
                ->comment('Текущий баланс пользователя в условных единицах');

            $table->rememberToken()
                ->comment('Токен для функции “запомнить меня”');

            $table->timestamp('created_at')
                ->nullable()
                ->comment('Дата и время создания записи пользователя');

            $table->timestamp('updated_at')
                ->nullable()
                ->comment('Дата и время последнего обновления записи пользователя');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')
                ->primary()
                ->comment('Email пользователя, для которого запрошен сброс пароля');

            $table->string('token')
                ->comment('Токен для сброса пароля');

            $table->timestamp('created_at')
                ->nullable()
                ->comment('Дата и время создания токена сброса пароля');
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')
                ->primary()
                ->comment('Идентификатор сессии');

            $table->foreignId('user_id')
                ->nullable()
                ->index()
                ->comment('Идентификатор пользователя, связанного с сессией');

            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IP-адрес пользователя');

            $table->text('user_agent')
                ->nullable()
                ->comment('User-Agent браузера или клиента пользователя');

            $table->longText('payload')
                ->comment('Сериализованные данные сессии');

            $table->integer('last_activity')
                ->index()
                ->comment('Метка времени последней активности в сессии');
        });
    }

    /**
     * Откатить миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
