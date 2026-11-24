<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDF : Commande (Numéro Commande#, Date commande, Condition, Code Client)
 * "Condition" = payment terms (LIV / T30). It is NOT the fulfillment status,
 * which has its own column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->nullable()->unique();      // Com-000001
            $table->dateTime('order_date');                          // Date commande
            $table->enum('payment_terms', ['LIV', 'T30']);           // Condition (paiement)
            // Rule 2: an order is placed by exactly one client.
            $table->foreignId('client_id')->constrained()->restrictOnDelete(); // Code Client
            $table->enum('status', ['pending', 'preparing', 'shipped', 'delivered', 'cancelled'])
                ->default('pending');                                // fulfillment status
            // Snapshot of the money at confirmation time (history never changes).
            $table->unsignedTinyInteger('discount_rate')->default(0); // % applied
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('total', 10, 2);
            // Safety columns
            $table->string('checkout_token', 64)->nullable()->unique(); // blocks double submits
            $table->timestamp('stock_restored_at')->nullable();         // stock restored only once
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
