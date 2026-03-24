<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->foreignId('commande_id')->nullable()->constrained('commandes')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('event_type');
            $table->string('status');
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
