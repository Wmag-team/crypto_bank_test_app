<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Страница создания пользователя в админ-панели.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
