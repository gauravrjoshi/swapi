<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_version_settings', function (Blueprint $table) {
            $table->id();
            $table->string('latest_version', 20)->default('1.0.0');
            $table->string('min_required_version', 20)->default('1.0.0');
            $table->boolean('force_update')->default(false);
            $table->text('update_message')->nullable();
            $table->string('android_store_url')->nullable();
            $table->string('ios_store_url')->nullable();
            $table->timestamps();
        });

        // Seed the single settings row
        DB::table('app_version_settings')->insert([
            'latest_version'       => '1.0.0',
            'min_required_version' => '1.0.0',
            'force_update'         => false,
            'update_message'       => 'A new version of Unnati is available with improvements and bug fixes. Please update to continue.',
            'android_store_url'    => 'https://play.google.com/store/apps/details?id=com.sw.unnati',
            'ios_store_url'        => 'https://apps.apple.com/app/unnati/id0000000000',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_version_settings');
    }
};
