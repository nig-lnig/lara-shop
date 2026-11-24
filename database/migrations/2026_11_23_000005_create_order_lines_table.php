<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDF : Ligne Commande (Numéro Commande#, Code Produit#, Quantité)
 * The PDF key is the pair (order, product). We keep a Laravel id for convenience
 * AND a unique index on (order_id, product_id) to enforce the PDF rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');                 // Quantité (> 0, validated)
            $table->decimal('unit_price', 10, 2);                 // price frozen at confirmation
            $table->decimal('line_total', 10, 2);
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
