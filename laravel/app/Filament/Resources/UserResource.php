<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ресурс Filament для управления пользователями.
 * Доступ только у админа; у обычных пользователей свой функционал в панели.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи';

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
                TextInput::make('balance')
                    ->label('Баланс')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxLength(36),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('Баланс')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 2)),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ]);
    }

    /**
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
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

    public static function getViewAuthorizationResponse(User $record): Response
    {
        return static::getViewAnyAuthorizationResponse();
    }

    public static function getCreateAuthorizationResponse(): Response
    {
        return static::getViewAnyAuthorizationResponse();
    }

    public static function getEditAuthorizationResponse(User $record): Response
    {
        return static::getViewAnyAuthorizationResponse();
    }

    public static function getDeleteAuthorizationResponse(User $record): Response
    {
        return static::getViewAnyAuthorizationResponse();
    }
}
