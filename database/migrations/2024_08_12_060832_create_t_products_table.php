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
        Schema::create('t_products', function (Blueprint $table) {
            $table->string('SKU')->primary();
            $table->string('Product_Code');
            $table->string('Product_Name');
            $table->string('Category')->nullable();;
            $table->string('Sub_Category')->nullable();;
            $table->longText('Product_Image');
            $table->float('basic');
            $table->float('gst');
            $table->float('mark_up');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_products');
    }
};
