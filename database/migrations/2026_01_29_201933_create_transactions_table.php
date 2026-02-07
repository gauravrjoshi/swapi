<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->string('transaction_details');
            $table->string('other_transaction_details')->nullable();
            $table->string('account');
            $table->decimal('amount', 10, 2);
            $table->string('ref_no')->nullable();
            $table->string('order_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('tag')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
