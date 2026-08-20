<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#6B7280');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE finance_tags ADD active_lock TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED');
        DB::statement('ALTER TABLE finance_tags ADD UNIQUE INDEX unique_tag (name, active_lock)');
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tags');
    }
};