<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('description');
            $table->string('slug')->nullable();
            $table->decimal('promo',10,2);
            $table->decimal('prix', 10,2);
            $table->string('origine');
            $table->string('coupe');
            $table->boolean('hasVariants')->default(true);
            $table->integer('stock_global')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
