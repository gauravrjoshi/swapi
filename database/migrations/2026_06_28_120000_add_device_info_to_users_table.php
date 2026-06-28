<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('app_version', 20)->nullable()->after('fcm_token');
            $table->string('app_build', 10)->nullable()->after('app_version');
            $table->string('platform', 10)->nullable()->after('app_build');   // 'android' | 'ios'
            $table->string('os_version', 50)->nullable()->after('platform');  // e.g. 'Android 14'
            $table->string('device_model', 100)->nullable()->after('os_version'); // e.g. 'Pixel 8'
            $table->timestamp('device_last_seen')->nullable()->after('device_model');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'app_version',
                'app_build',
                'platform',
                'os_version',
                'device_model',
                'device_last_seen',
            ]);
        });
    }
};
