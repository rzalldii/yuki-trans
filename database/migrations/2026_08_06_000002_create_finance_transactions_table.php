<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('finance_categories')->restrictOnDelete();
            $table->string('amount');
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->timestamps();

            $table->index('transaction_date');
            $table->index(['user_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};