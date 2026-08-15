<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_wallet_id')->constrained('finance_wallets')->restrictOnDelete();
            $table->foreignId('to_wallet_id')->constrained('finance_wallets')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('transfer_date');
            $table->timestamps();

            $table->index('transfer_date');
            $table->index('from_wallet_id');
            $table->index('to_wallet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transfers');
    }
};