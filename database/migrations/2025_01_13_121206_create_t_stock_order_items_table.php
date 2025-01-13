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
        Schema::create('t_stock_order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('stock_order_id');
            $table->string('product_code', 255);
            $table->string('product_name', 255);
            $table->integer('quantity');
            $table->enum('type', ['IN', 'OUT']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_stock_order_items');
    }
};
