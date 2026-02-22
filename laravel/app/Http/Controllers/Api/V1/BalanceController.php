<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTransferJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * API-контроллер для операций с балансом и переводами.
 *
 * Все финансовые операции (перевод) выполняются через очереди и возвращают 202 Accepted.
 */
class BalanceController extends Controller
{
    /**
     * Получение текущего баланса авторизованного пользователя.
     *
     * @return JsonResponse Возможные HTTP-статусы: 200 (OK), 401 (Unauthorized)
     */
    public function balance(): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'balance' => (string) $user->balance,
        ], 200);
    }

    /**
     * История транзакций авторизованного пользователя (пагинация).
     *
     * @param Request $request Запрос (page, per_page)
     * @return JsonResponse Возможные HTTP-статусы: 200 (OK), 401 (Unauthorized)
     */
    public function transactions(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginator = $user->transactions()->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 200);
    }

    /**
     * Инициация перевода другому пользователю (постановка в очередь).
     *
     * Обработка идёт асинхронно через ProcessTransferJob.
     *
     * @param Request $request to_user_id (int), amount (string|number)
     * @return JsonResponse Возможные HTTP-статусы: 202 (Accepted), 401 (Unauthorized), 422 (Unprocessable Entity), 500 (Server Error)
     */
    public function transfer(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id', 'different:' . $user->id],
            'amount' => ['required', 'numeric', 'min:0.000001'],
        ], [
            'to_user_id.different' => 'Нельзя перевести средства самому себе.',
        ]);

        $toUserId = (int) $validated['to_user_id'];
        $amount = (string) $validated['amount'];

        ProcessTransferJob::dispatch($user->id, $toUserId, $amount);

        return response()->json([
            'message' => 'Перевод принят в обработку.',
        ], 202);
    }
}
