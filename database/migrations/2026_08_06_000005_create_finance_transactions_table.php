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
            $table->foreignId('wallet_id')->constrained('finance_wallets')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->restrictOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer_in', 'transfer_out']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->foreignId('transfer_pair_id')->nullable()->constrained('finance_transactions')->nullOnDelete();
            $table->foreignId('recurring_id')->nullable()->constrained('finance_recurring_transactions')->nullOnDelete();
            $table->timestamps();

            $table->index('transaction_date');
            $table->index(['user_id', 'transaction_date']);
            $table->index('transfer_pair_id');
            $table->index('type');
            $table->index('recurring_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};