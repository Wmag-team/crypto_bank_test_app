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
 * Админ видит все транзакции; обычный пользователь — только свои (та же таблица и просмотр).
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
                TextEntry::make('display_user_email')
                    ->label('Email пользователя')
                    ->getConstantStateUsing(function (?Transaction $record): ?string {
                        if ($record === null) {
                            return null;
                        }
                        if (auth()->user()?->isAdmin() === true) {
                            return $record->user?->email;
                        }
                        if ($record->type === 'transfer') {
                            return $record->counterparty_email;
                        }
                        return null;
                    })
                    ->placeholder('—'),
                TextEntry::make('type_label')
                    ->label('Тип')
                    ->getConstantStateUsing(function (?Transaction $record): string {
                        if ($record === null) {
                            return '—';
                        }
                        if ($record->type === 'transfer') {
                            return $record->direction === 'in' ? 'Входящий перевод' : 'Исходящий перевод';
                        }
                        return match ($record->type) {
                            'deposit' => 'Пополнение',
                            'withdraw' => 'Списание',
                            'commission' => 'Комиссия',
                            default => $record->type,
                        };
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
                TextEntry::make('counterparty_description')
                    ->label('Контрагент')
                    ->placeholder('—')
                    ->visible(fn (?Transaction $record): bool => $record !== null && $record->type === 'transfer'),
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
                // TextColumn::make('user_id')
                    // ->label('ID пользователя')
                    // ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email пользователя')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function (Transaction $record): ?string {
                        if (auth()->user()?->isAdmin() === true) {
                            return $record->user?->email;
                        }
                        if ($record->type === 'transfer') {
                            return $record->counterparty_email;
                        }
                        return null;
                    })
                    ->placeholder('—'),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->getStateUsing(function (Transaction $record): string {
                        if ($record->type === 'transfer') {
                            return $record->direction === 'in' ? 'Входящий перевод' : 'Исходящий перевод';
                        }
                        return match ($record->type) {
                            'deposit' => 'Пополнение',
                            'withdraw' => 'Списание',
                            'commission' => 'Комиссия',
                            default => $record->type,
                        };
                    })
                    ->color(fn (Transaction $record): string => match ($record->type) {
                        'deposit' => 'success',
                        'withdraw' => 'warning',
                        'transfer' => 'info',
                        'commission' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('counterparty_description')
                    ->label('Контрагент')
                    ->placeholder('—')
                    ->visible(fn (?Transaction $record): bool => $record !== null && $record->type === 'transfer'),
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
        $query = parent::getEloquentQuery();
        $query->with(['user', 'counterparty']);
        if (auth()->user()?->isAdmin() !== true) {
            $query->where('user_id', auth()->id());
        }
        return $query;
    }

    /** Транзакции только для просмотра — создание через сервис/очередь. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** Раздел виден всем авторизованным: админу — все, пользователю — свои транзакции. */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getViewAnyAuthorizationResponse(): Response
    {
        return Response::allow();
    }

    public static function getViewAuthorizationResponse(Model $record): Response
    {
        if (auth()->user()?->isAdmin() === true) {
            return Response::allow();
        }
        return (int) $record->user_id === (int) auth()->id()
            ? Response::allow()
            : Response::deny('Доступ только к своим транзакциям.');
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
