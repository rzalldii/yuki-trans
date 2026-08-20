<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('finance_wallets')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('finance_categories')->restrictOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date');
            $table->date('last_generated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('next_due_date');
            $table->index('is_active');
            $table->index(['is_active', 'next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_recurring_transactions');
    }
};