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
            $table->enum('type', ['credit', 'debit', 'transfer'])->after('id')->nullable();
            $table->foreignId('account_id')->nullable()->after('type')->constrained('accounts')->nullOnDelete();
            $table->foreignId('from_account_id')->nullable()->after('account_id')->constrained('accounts')->nullOnDelete();
            $table->foreignId('to_account_id')->nullable()->after('from_account_id')->constrained('accounts')->nullOnDelete();
            $table->text('description')->nullable()->after('to_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['from_account_id']);
            $table->dropForeign(['to_account_id']);
            $table->dropColumn(['type', 'account_id', 'from_account_id', 'to_account_id', 'description']);
        });
    }
};
