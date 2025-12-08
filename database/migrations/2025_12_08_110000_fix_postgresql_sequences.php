<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix PostgreSQL sequences that are out of sync with table data.
     * This happens when data is imported with explicit IDs.
     */
    public function up(): void
    {
        // Only run on PostgreSQL
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Tables that commonly have sequence issues
        $tables = [
            'migrations',  // Fix migrations table first!
            'business_settings',
            'users',
            'products',
            'orders',
            'order_details',
            'categories',
            'brands',
            'colors',
            'attributes',
            'sellers',
            'shops',
            'coupons',
            'flash_deals',
            'banners',
            'notifications',
            'shipping_addresses',
            'carts',
            'reviews',
            'wishlist',
            'compare_lists',
            'admins',
            'admin_roles',
            'currencies',
            'delivery_men',
            'addon_settings',
            'password_resets',
            'personal_access_tokens',
        ];

        foreach ($tables as $table) {
            $this->resetSequence($table);
        }
    }

    /**
     * Reset the sequence for a table to max(id) + 1
     */
    private function resetSequence(string $table): void
    {
        // Use separate connection to avoid transaction issues
        try {
            // Check if table exists
            $tableExists = DB::select(
                "SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = ?)",
                [$table]
            );

            if (!$tableExists || !$tableExists[0]->exists) {
                return;
            }

            // Check if table has 'id' column
            $columnExists = DB::select(
                "SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = 'id')",
                [$table]
            );

            if (!$columnExists || !$columnExists[0]->exists) {
                return;
            }

            // Get max id (minimum 1 for setval)
            $result = DB::select("SELECT COALESCE(MAX(id), 0) as max_id FROM \"{$table}\"");
            $maxId = $result[0]->max_id ?? 0;

            // Reset sequence
            // If table is empty (maxId=0), set to 1 with is_called=false (next insert gets 1)
            // If table has data, set to maxId with is_called=true (next insert gets maxId+1)
            $sequenceName = "{$table}_id_seq";

            if ($maxId == 0) {
                DB::statement("SELECT setval('{$sequenceName}', 1, false)");
            } else {
                DB::statement("SELECT setval('{$sequenceName}', ?, true)", [$maxId]);
            }

        } catch (\Exception $e) {
            // Log error but don't fail migration
            \Log::warning("Could not reset sequence for table {$table}: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse - sequences are automatically managed
    }
};
