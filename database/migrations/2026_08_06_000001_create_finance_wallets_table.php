<?php

declare(strict_types=1);

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
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE finance_wallets ADD active_lock TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED');
            DB::statement('ALTER TABLE finance_wallets ADD UNIQUE INDEX unique_wallet (name, active_lock)');
            DB::statement('ALTER TABLE finance_wallets ADD CONSTRAINT chk_wallets_balance CHECK (current_balance >= 0)');
            DB::statement('ALTER TABLE finance_wallets ADD CONSTRAINT chk_wallets_initial_balance CHECK (initial_balance >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_wallets');
    }
};