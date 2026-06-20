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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('tag_id')->nullable(); // signed integer, no constraint to support system tags (-1, -2, etc)
            $table->string('tag');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            // A user should only have one budget per tag name
            $table->unique(['user_id', 'tag']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
