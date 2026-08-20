<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('finance_transaction_tag', function (Blueprint $table) {
            $table->foreignId('transaction_id')->constrained('finance_transactions')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('finance_tags')->cascadeOnDelete();

            $table->primary(['transaction_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transaction_tag');
    }
};