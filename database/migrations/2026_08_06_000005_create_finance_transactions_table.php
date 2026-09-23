<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('finance_wallets')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->foreignId('transfer_pair_id')->nullable()->constrained('finance_transactions')->nullOnDelete();
            $table->foreignId('recurring_id')->nullable()->constrained('finance_recurrings')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('transaction_date');
            $table->index(['user_id', 'transaction_date']);
            $table->index(['wallet_id', 'transaction_date']);
            $table->index(['category_id', 'transaction_date']);
            $table->index(['type', 'transaction_date']);
            $table->index('transfer_pair_id');
            $table->index('recurring_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE finance_transactions ADD CONSTRAINT chk_transactions_amount CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};