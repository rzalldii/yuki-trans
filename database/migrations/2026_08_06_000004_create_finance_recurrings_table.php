<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_recurrings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('finance_wallets')->restrictOnDelete();
            $table->foreignId('to_wallet_id')->nullable()->constrained('finance_wallets')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('description', 1000)->nullable();
            $table->string('type', 20);
            $table->string('frequency', 20);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->date('last_generated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('next_due_date');
            $table->index('is_active');
            $table->index(['next_due_date', 'is_active']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE finance_recurrings ADD CONSTRAINT chk_recurrings_amount CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_recurrings');
    }
};