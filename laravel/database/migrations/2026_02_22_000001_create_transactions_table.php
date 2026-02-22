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
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id()
                ->comment('Первичный ключ транзакции');

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Идентификатор пользователя, связанного с транзакцией');

            $table->enum('type', ['deposit', 'withdraw', 'transfer', 'commission'])
                ->comment('Тип транзакции: deposit, withdraw, transfer или commission');

            $table->decimal('amount', 36, 18)
                ->comment('Сумма транзакции в условных единицах');

            $table->enum('status', ['pending', 'completed', 'failed'])
                ->default('pending')
                ->comment('Статус транзакции: pending, completed или failed');

            $table->jsonb('metadata')
                ->nullable()
                ->comment('Дополнительные данные транзакции в формате JSONB');

            $table->timestamp('created_at')
                ->nullable()
                ->comment('Дата и время создания записи транзакции');

            $table->timestamp('updated_at')
                ->nullable()
                ->comment('Дата и время последнего обновления записи транзакции');
        });
    }

    /**
     * Откатить миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

