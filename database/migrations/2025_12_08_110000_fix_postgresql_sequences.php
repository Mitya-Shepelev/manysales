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
        try {
            // Check if table exists
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return;
            }

            // Check if table has 'id' column
            if (!DB::getSchemaBuilder()->hasColumn($table, 'id')) {
                return;
            }

            // Get max id
            $maxId = DB::table($table)->max('id') ?? 0;

            // Reset sequence to max + 1
            $sequenceName = "{$table}_id_seq";
            DB::statement("SELECT setval('{$sequenceName}', ?, true)", [$maxId]);

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
