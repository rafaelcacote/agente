<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('api_key_hash', 255)->nullable()->after('settings');
            $table->string('api_key_prefix', 12)->nullable()->after('api_key_hash');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['api_key_hash', 'api_key_prefix']);
        });
    }
};
