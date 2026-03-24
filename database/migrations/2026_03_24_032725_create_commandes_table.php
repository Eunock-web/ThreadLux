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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('acheteur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vendeur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('address_id')->constrained('addresses')->onDelete('cascade');
            $table->decimal('montant_sous_total', 10, 2);
            $table->decimal('montant_livraison', 10, 2)->default(0.0);
            $table->decimal('montant_total', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('escrow_status', ['none', 'held', 'released', 'refunded', 'disputed'])->default('none');
            $table->string('tracking_number')->nullable();
            $table->text('note_acheteur')->nullable();
            $table->date('livraison_estimee')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
