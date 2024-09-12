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
            $table->id();
            $table->string('sku');
            $table->string('product_code');
            $table->string('product_name');
            $table->string('name_in_hindi')->nullable();
            $table->string('name_in_telugu')->nullable();
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->longText('product_image')->nullable();
            $table->float('basic')->nullable();
            $table->float('gst')->nullable();
            // $table->float('mark_up');
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
