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
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('running_balance', 15, 2)->nullable()->after('amount');
            $table->decimal('from_account_running_balance', 15, 2)->nullable()->after('running_balance');
            $table->decimal('to_account_running_balance', 15, 2)->nullable()->after('from_account_running_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['running_balance', 'from_account_running_balance', 'to_account_running_balance']);
        });
    }
};
