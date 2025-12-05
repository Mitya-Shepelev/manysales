<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if yookassa config already exists
        $exists = DB::table('addon_settings')
            ->where('key_name', 'yookassa')
            ->where('settings_type', 'payment_config')
            ->exists();

        if (!$exists) {
            DB::table('addon_settings')->insert([
                'id' => Str::uuid()->toString(),
                'key_name' => 'yookassa',
                'live_values' => json_encode([
                    'gateway' => 'yookassa',
                    'mode' => 'test',
                    'status' => 0,
                    'shop_id' => '',
                    'secret_key' => '',
                ]),
                'test_values' => json_encode([
                    'gateway' => 'yookassa',
                    'mode' => 'test',
                    'status' => 0,
                    'shop_id' => '',
                    'secret_key' => '',
                ]),
                'settings_type' => 'payment_config',
                'mode' => 'test',
                'is_active' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'additional_data' => json_encode([
                    'gateway_title' => 'YooKassa',
                    'gateway_image' => '',
                ]),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('addon_settings')
            ->where('key_name', 'yookassa')
            ->where('settings_type', 'payment_config')
            ->delete();
    }
};
