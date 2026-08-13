<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('initial_balance', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_wallets');
    }
};
