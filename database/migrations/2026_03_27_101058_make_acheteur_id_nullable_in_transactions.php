<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Allow guest checkouts — these fields are optional
            $table->foreignId('acheteur_id')->nullable()->change();
            $table->foreignId('vendeur_id')->nullable()->change();
            $table->foreignId('commande_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('acheteur_id')->nullable(false)->change();
            $table->foreignId('vendeur_id')->nullable(false)->change();
            $table->foreignId('commande_id')->nullable(false)->change();
        });
    }
};
