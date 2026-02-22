<?php

declare(strict_types=1);

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Страница списка транзакций в админ-панели.
 */
class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;
}
