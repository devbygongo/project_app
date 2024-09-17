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
        Schema::create('t_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->integer('invoice_id');
            $table->string('product_code');
            $table->string('product_name');
            $table->float('rate');
            $table->float('quantity');
            $table->float('total');
            $table->enum('type', ['basic', 'gst']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_invoice_items');
    }
};
