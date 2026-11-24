<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDF : Fournisseur (Code Fournisseur#, Nom Fournisseur, Ville Fournisseur)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique(); // Fr-000001
            $table->string('name', 120);                      // Nom Fournisseur
            $table->string('city', 100);                      // Ville Fournisseur
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
