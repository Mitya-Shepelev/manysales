<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes duplicate system_tax_setups records,
     * keeping only the one with is_default=true or the oldest one.
     */
    public function up(): void
    {
        // Get all unique tax_payer values
        $taxPayers = DB::table('system_tax_setups')
            ->select('tax_payer')
            ->distinct()
            ->pluck('tax_payer');

        foreach ($taxPayers as $taxPayer) {
            // Find the record to keep: prefer is_default=true, then oldest by ID
            $keepRecord = DB::table('system_tax_setups')
                ->where('tax_payer', $taxPayer)
                ->orderByDesc('is_default')
                ->orderBy('id', 'asc')
                ->first();

            if ($keepRecord) {
                // Delete all other records with the same tax_payer
                $deleted = DB::table('system_tax_setups')
                    ->where('tax_payer', $taxPayer)
                    ->where('id', '!=', $keepRecord->id)
                    ->delete();

                if ($deleted > 0) {
                    \Log::info("Cleaned up {$deleted} duplicate system_tax_setups for tax_payer={$taxPayer}, kept id={$keepRecord->id}");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore deleted duplicates
    }
};
