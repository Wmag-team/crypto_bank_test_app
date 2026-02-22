<?php

use App\Http\Controllers\Api\V1\BalanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| Маршруты для работы с балансом и транзакциями. Все эндпоинты требуют
| авторизации (сессия или Sanctum). Финансовые операции обрабатываются
| асинхронно через очереди и возвращают 202 Accepted.
|
*/

// Группа API v1: префикс /api/v1, авторизация через сессию (auth:web).
// Для Bearer-токенов установите laravel/sanctum и замените на auth:sanctum.
Route::prefix('v1')->middleware('auth:web')->group(function (): void {
    /*
     * GET /api/v1/balance
     * Кто вызывает: клиент (SPA, мобильное приложение) для отображения текущего баланса.
     * Параметры: нет.
     * Тип ответа: 200 OK — JSON { "balance": "123.456789012345678900" }
     *              401 Unauthorized — не авторизован.
     */
    Route::get('balance', [BalanceController::class, 'balance']);

    /*
     * GET /api/v1/transactions
     * Кто вызывает: клиент для отображения истории транзакций пользователя.
     * Параметры: опционально page (пагинация), per_page.
     * Тип ответа: 200 OK — JSON { "data": [...], "meta": {...} } (список транзакций текущего пользователя)
     *              401 Unauthorized — не авторизован.
     */
    Route::get('transactions', [BalanceController::class, 'transactions']);

    /*
     * POST /api/v1/transfer
     * Кто вызывает: клиент для инициации перевода другому пользователю.
     * Параметры (JSON): to_user_id (int), amount (string или number).
     * Тип ответа: 202 Accepted — перевод принят в очередь, JSON { "message": "..." }
     *              401 Unauthorized — не авторизован.
     *              422 Unprocessable Entity — ошибки валидации (неверный to_user_id, сумма, недостаточно средств после валидации).
     *              500 Server Error — внутренняя ошибка.
     */
    Route::post('transfer', [BalanceController::class, 'transfer']);
});
