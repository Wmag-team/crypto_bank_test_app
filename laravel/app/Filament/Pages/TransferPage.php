<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\CryptoBalanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class TransferPage extends Page
{

    protected static ?string $slug = 'transfer';
    protected string $view = 'filament.pages.transfer-page';

    // Название в боковом меню
    protected static ?string $navigationLabel = 'Сделать перевод';

    // Заголовок на самой странице
    protected static ?string $title = 'Сделать перевод';

    // Оставляем плоские свойства — они залог стабильности в v4
    public ?string $to_user_id = null;
    public ?string $amount = null;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('to_user_id')
                    ->label('Получатель')
                    ->placeholder('Введите имя или email...')
                    ->searchable()
                    ->required()
                    // live() обязателен, чтобы ID сразу попадал в $this->to_user_id
                    ->live()
                    ->getSearchResultsUsing(function (string $search): array {
                        if (strlen($search) < 2) return [];

                        return User::query()
                            ->where('id', '!=', Auth::id())
                            ->where(function ($q) use ($search) {
                                $q->where('name', 'ilike', "%{$search}%")
                                    ->orWhere('email', 'ilike', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->email})"])
                            ->all();
                    })
                    ->getOptionLabelUsing(fn ($value) => $value ? User::find($value)?->name : null),

                TextInput::make('amount')
                    ->label('Сумма')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Отправить перевод')
                ->color('primary')
                ->requiresConfirmation(false)
                ->action(function (CryptoBalanceService $CryptoBalanceService) {
                    // Используем данные из свойств класса
                    $id = $this->to_user_id;
                    $sum = $this->amount;

                    if (!$id || !$sum) {
                        Notification::make()
                            ->title('Ошибка')
                            ->body('Выберите получателя и укажите сумму.')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $toUser = User::findOrFail($id);

                        $CryptoBalanceService->transfer(Auth::user(), $toUser, (string)$sum);

                        Notification::make()
                            ->title('Успешно')
                            ->body("Перевод для {$toUser->name} выполнен.")
                            ->success()
                            ->send();

                        // Сброс полей
                        $this->to_user_id = null;
                        $this->amount = null;
                    } catch (\Throwable $e) {
                        Notification::make()->title('Ошибка')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
