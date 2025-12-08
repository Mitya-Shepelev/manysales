<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix PostgreSQL sequences for all tables.
     * This is needed when data was imported with explicit IDs.
     */
    public function up(): void
    {
        if (config('database.default') === 'pgsql') {
            // Fix all sequences in the database
            $tables = DB::select("
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = 'public'
                AND table_type = 'BASE TABLE'
            ");

            foreach ($tables as $table) {
                $tableName = $table->table_name;
                $sequenceName = "{$tableName}_id_seq";

                // Check if sequence exists
                $sequenceExists = DB::select("
                    SELECT 1 FROM pg_sequences WHERE schemaname = 'public' AND sequencename = ?
                ", [$sequenceName]);

                if (!empty($sequenceExists)) {
                    // Get max ID from table
                    $maxId = DB::table($tableName)->max('id') ?? 0;

                    if ($maxId > 0) {
                        DB::statement("SELECT setval(?, ?, true)", [$sequenceName, $maxId]);
                        \Log::info("Fixed sequence {$sequenceName}, set to {$maxId}");
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse - sequences were already broken before
    }
};
