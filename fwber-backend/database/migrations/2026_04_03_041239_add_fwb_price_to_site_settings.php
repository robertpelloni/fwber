<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \DB::table('site_settings')->insert([
            'key' => 'fwb_usd_price',
            'value' => '0.50',
            'type' => 'float',
            'description' => 'Target exchange rate for 1 FWB Token in USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        \DB::table('site_settings')->where('key', 'fwb_usd_price')->delete();
    }
};
