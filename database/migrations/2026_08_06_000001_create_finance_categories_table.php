<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['income', 'expense']);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE finance_categories ADD active_lock TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED');
        DB::statement('ALTER TABLE finance_categories ADD UNIQUE INDEX unique_category (name, type, active_lock)');
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_categories');
    }
};