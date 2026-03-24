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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('acheteur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vendeur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XOF');
            $table->enum('payment_method', ['mobile_money', 'card', 'virement']);
            $table->enum('provider', ['fedapay', 'stripe']);
            $table->string('provider_ref')->nullable();
            $table->enum('status', ['initiated', 'pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('initiated');
            $table->enum('escrow_status', ['none', 'held', 'released', 'refunded', 'disputed'])->default('none');
            $table->timestamp('escrow_held_at')->nullable();
            $table->timestamp('escrow_released_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
