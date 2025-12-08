<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixRequestStatusColumnType extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes request_status column type for PostgreSQL.
     * The original migration defined this as boolean, but the app uses values 0, 1, 2.
     * MySQL treats boolean as TINYINT(1) so it works there, but PostgreSQL
     * has strict BOOLEAN type that only accepts true/false.
     *
     * @return void
     */
    public function up()
    {
        if (config('database.default') === 'pgsql') {
            // PostgreSQL: Change boolean to smallint
            // First convert existing boolean values to integers
            DB::statement('ALTER TABLE products ALTER COLUMN request_status DROP DEFAULT');
            DB::statement('ALTER TABLE products ALTER COLUMN request_status TYPE SMALLINT USING CASE WHEN request_status THEN 1 ELSE 0 END');
            DB::statement('ALTER TABLE products ALTER COLUMN request_status SET DEFAULT 0');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('database.default') === 'pgsql') {
            // Convert back to boolean (only 0 and 1 will be preserved correctly)
            DB::statement('ALTER TABLE products ALTER COLUMN request_status DROP DEFAULT');
            DB::statement('ALTER TABLE products ALTER COLUMN request_status TYPE BOOLEAN USING request_status::int::bool');
            DB::statement('ALTER TABLE products ALTER COLUMN request_status SET DEFAULT false');
        }
    }
}
