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
        Schema::create('t_order_items', function (Blueprint $table) {
            $table->id();
            // $table->integer('orderID');
            $table->integer('order_id');
            // $table->string('item');
            $table->string('product_code');
            $table->string('product_name');
            $table->float('rate');
            // $table->float('discount');
            $table->float('quantity');
            // $table->float('line_total');
            $table->float('total');
            $table->string('size')->nullable();
            $table->enum('type', ['basic', 'gst']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_order_items');
    }
};
