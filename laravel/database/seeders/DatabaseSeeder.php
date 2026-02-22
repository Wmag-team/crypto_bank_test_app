<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Сидер базы данных.
 * Создаёт админа, 10 тестовых пользователей с балансами и фейковую историю транзакций.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Запуск сидеров приложения.
     */
    public function run(): void
    {
        $this->seedAdmin();
        $users = $this->seedUsers();
        $this->seedTransactions($users);
    }

    /**
     * Создание учётной записи администратора (admin@mail.com).
     * Только этот пользователь имеет доступ к панели Filament /admin.
     */
    private function seedAdmin(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'Администратор',
                'password' => Hash::make('password'),
                'balance' => 0,
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Создание 10 пользователей (email1@mail.com ... email10@mail.com) с рандомными балансами 10 000–50 000.
     *
     * @return array<int, User>
     */
    private function seedUsers(): array
    {
        $users = [];

        for ($i = 1; $i <= 10; $i++) {
            $balance = (string) fake()->randomFloat(2, 10_000, 50_000);

            $user = User::query()->updateOrCreate(
                ['email' => "email{$i}@mail.com"],
                [
                    'name' => fake()->name(),
                    'password' => Hash::make('password'),
                    'balance' => $balance,
                    'email_verified_at' => now(),
                ]
            );

            $users[] = $user;
        }

        return $users;
    }

    /**
     * Генерация по 5–10 фейковых транзакций для каждого переданного пользователя.
     *
     * @param array<int, User> $users
     */
    private function seedTransactions(array $users): void
    {
        $types = ['deposit', 'withdraw', 'transfer', 'commission'];
        $statuses = ['pending', 'completed', 'failed'];

        foreach ($users as $user) {
            $count = random_int(5, 10);

            for ($i = 0; $i < $count; $i++) {
                Transaction::query()->create([
                    'user_id' => $user->id,
                    'type' => fake()->randomElement($types),
                    'amount' => (string) fake()->randomFloat(2, 10, 5000),
                    'status' => fake()->randomElement($statuses),
                    'metadata' => [
                        'comment' => fake()->optional(0.7)->sentence(4),
                        'reference' => fake()->optional(0.5)->uuid(),
                    ],
                    'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
                ]);
            }
        }
    }
}
