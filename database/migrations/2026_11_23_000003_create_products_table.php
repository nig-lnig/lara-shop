<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDF : Produit (Code Produit#, Désignation, Prix Unitaire, Famille, Code Fournisseur)
 * Extra e-commerce columns : description, stock, low_stock_threshold, image_path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique();        // Pr-000001
            $table->string('designation', 150);                     // Désignation
            $table->text('description')->nullable();
            $table->decimal('unit_price', 10, 2);                    // Prix Unitaire (fixed precision)
            $table->enum('family', ['PA', 'BU', 'FO', 'LI']);       // Famille
            // Rule 6: one product has exactly one supplier.
            // restrictOnDelete: a supplier that still has products cannot be deleted.
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete(); // Code Fournisseur
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->string('image_path')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
