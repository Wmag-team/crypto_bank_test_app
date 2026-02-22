<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для генерации фейковых транзакций.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Типы транзакций из миграции.
     *
     * @var array<string>
     */
    private const TYPES = ['deposit', 'withdraw', 'transfer', 'commission'];

    /**
     * Статусы транзакций из миграции.
     *
     * @var array<string>
     */
    private const STATUSES = ['pending', 'completed', 'failed'];

    /**
     * Определение состояния модели по умолчанию.
     * Для type=transfer задаются direction и counterparty_user_id; для остальных — null.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(self::TYPES);
        $status = fake()->randomElement(self::STATUSES);
        $amount = (string) fake()->randomFloat(2, 10, 5000);

        $base = [
            'user_id' => User::factory(),
            'type' => $type,
            'amount' => $amount,
            'status' => $status,
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];

        if ($type === 'transfer') {
            $base['direction'] = fake()->randomElement(['in', 'out']);
            $base['counterparty_user_id'] = User::factory();
            $base['metadata'] = null;
        } else {
            $base['direction'] = null;
            $base['counterparty_user_id'] = null;
            $base['metadata'] = [
                'comment' => fake()->optional(0.7)->sentence(4),
                'reference' => fake()->optional(0.5)->uuid(),
            ];
        }

        return $base;
    }

    /** Состояние: исходящий перевод (direction=out). */
    public function outgoing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'transfer',
            'direction' => 'out',
            'counterparty_user_id' => $attributes['counterparty_user_id'] ?? User::factory(),
            'metadata' => null,
        ]);
    }

    /** Состояние: входящий перевод (direction=in). */
    public function incoming(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'transfer',
            'direction' => 'in',
            'counterparty_user_id' => $attributes['counterparty_user_id'] ?? User::factory(),
            'metadata' => null,
        ]);
    }
}
