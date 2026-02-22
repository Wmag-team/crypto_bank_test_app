<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавить direction и counterparty_user_id для переводов (для БД, созданных до введения этих полей).
     */
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (Schema::hasColumn('transactions', 'direction')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->enum('direction', ['in', 'out'])
                ->nullable()
                ->after('type')
                ->comment('Для type=transfer: in — входящий перевод, out — исходящий');

            $table->unsignedBigInteger('counterparty_user_id')
                ->nullable()
                ->after('direction')
                ->comment('Для type=transfer: от кого (in) или кому (out) средства');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreign('counterparty_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        $this->backfillFromMetadata();
    }

    private function backfillFromMetadata(): void
    {
        $rows = DB::table('transactions')
            ->where('type', 'transfer')
            ->whereNotNull('metadata')
            ->get(['id', 'user_id', 'metadata']);

        foreach ($rows as $row) {
            $meta = is_string($row->metadata) ? json_decode($row->metadata, true) : $row->metadata;
            if (! is_array($meta)) {
                continue;
            }
            $direction = $meta['direction'] ?? null;
            $counterpartyId = $meta['to_user_id'] ?? $meta['from_user_id'] ?? null;
            if ($direction !== 'in' && $direction !== 'out' || $counterpartyId === null) {
                continue;
            }
            if (! DB::table('users')->where('id', $counterpartyId)->exists()) {
                continue;
            }
            DB::table('transactions')
                ->where('id', $row->id)
                ->update([
                    'direction' => $direction,
                    'counterparty_user_id' => $counterpartyId,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['counterparty_user_id']);
            $table->dropColumn(['direction', 'counterparty_user_id']);
        });
    }
};
