<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('initial_balance', 15, 2);
            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE finance_wallets ADD active_lock TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED');
        DB::statement('ALTER TABLE finance_wallets ADD UNIQUE INDEX unique_wallet (name, active_lock)');
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_wallets');
    }
};