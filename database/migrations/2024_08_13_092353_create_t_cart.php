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
        Schema::create('t_cart', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            // $table->string('products_id');
            $table->string('product_code');
            $table->string('product_name');
            $table->float('rate');
            $table->integer('quantity');
            $table->float('amount');
            $table->enum('type', ['basic', 'gst']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_cart');
    }
};
