<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDF : Client (Code Client#, Nom Client, Rue Client, Code Postal Client,
 *               Ville Client, Type Client, Remise Spéciale)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();                                   // internal Laravel key
            $table->string('code', 20)->nullable()->unique(); // Cli-000001 (generated from id)
            $table->string('name', 120);                     // Nom Client
            $table->string('street', 190);                   // Rue Client
            $table->string('postal_code', 10);               // Code Postal Client (text, keeps leading zeros)
            $table->string('city', 100);                     // Ville Client
            $table->enum('type', ['PU', 'PA', 'LI', 'AS', 'DI'])->default('PA'); // Type Client
            $table->unsignedTinyInteger('special_discount')->default(0);          // Remise Spéciale (0..100 %)
            $table->timestamp('archived_at')->nullable();    // archived instead of deleted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
