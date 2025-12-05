<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('payment_requests', 'transaction_id')) {
            Schema::table('payment_requests', function (Blueprint $table) {
                $table->string('transaction_id', 100)->nullable()->after('is_paid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('payment_requests', 'transaction_id')) {
            Schema::table('payment_requests', function (Blueprint $table) {
                $table->dropColumn('transaction_id');
            });
        }
    }
};
