<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Filament\Resources\TransactionResource\Pages\ViewTransaction;
use App\Models\Transaction;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ресурс Filament для просмотра истории транзакций.
 * Доступ только у админа; у пользователей будет свой раздел с их транзакциями.
 */
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Транзакции';

    protected static ?string $modelLabel = 'транзакция';

    protected static ?string $pluralModelLabel = 'Транзакции';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    /** Infolist для страницы просмотра транзакции. */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('user_id')->label('ID пользователя'),
                TextEntry::make('user.email')->label('Email пользователя'),
                TextEntry::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'deposit' => 'Пополнение',
                        'withdraw' => 'Списание',
                        'transfer' => 'Перевод',
                        'commission' => 'Комиссия',
                        default => $state,
                    }),
                TextEntry::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 2)),
                TextEntry::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'В ожидании',
                        'completed' => 'Завершена',
                        'failed' => 'Ошибка',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('metadata')->label('Метаданные')->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '—'),
                TextEntry::make('created_at')->label('Создана')->dateTime('d.m.Y H:i'),
                TextEntry::make('updated_at')->label('Обновлена')->dateTime('d.m.Y H:i'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label('ID пользователя')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email пользователя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'deposit' => 'Пополнение',
                        'withdraw' => 'Списание',
                        'transfer' => 'Перевод',
                        'commission' => 'Комиссия',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'success',
                        'withdraw' => 'warning',
                        'transfer' => 'info',
                        'commission' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Сумма')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 2)),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'В ожидании',
                        'completed' => 'Завершена',
                        'failed' => 'Ошибка',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'view' => ViewTransaction::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    /** Транзакции только для просмотра — создание через сервис. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** Раздел только для администратора. */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getViewAnyAuthorizationResponse(): Response
    {
        return auth()->user()?->isAdmin()
            ? Response::allow()
            : Response::deny('Доступ только для администратора.');
    }

    public static function getViewAuthorizationResponse(Model $record): Response
    {
        return static::getViewAnyAuthorizationResponse();
    }

    public static function getEditAuthorizationResponse(Model $record): Response
    {
        return static::getViewAnyAuthorizationResponse();
    }

    public static function getDeleteAuthorizationResponse(Model $record): Response
    {
        return static::getViewAnyAuthorizationResponse();
    }
}
