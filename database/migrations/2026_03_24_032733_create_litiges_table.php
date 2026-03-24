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
        Schema::create('litiges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('initiateur_id')->constrained('users')->onDelete('cascade');
            $table->enum('raison', ['non_recu', 'non_conforme', 'defectueux', 'autre']);
            $table->text('description');
            $table->json('preuves')->nullable();
            $table->enum('status', ['ouverte', 'en_cours', 'resolue_acheteur', 'resolue_vendeur', 'fermee'])->default('ouverte');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('litiges');
    }
};
