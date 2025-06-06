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
            // $table->string('sku')->nullable();
            $table->string('product_code');
            $table->string('product_name');
            $table->string('name_in_hindi')->nullable();
            $table->string('name_in_telugu')->nullable();
            $table->string('brand');
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('type');
            $table->string('size')->nullable();
            $table->string('machine_part_no');
            $table->longText('product_image')->nullable();
            $table->string('video_link', 100)->nullable();
            $table->float('basic')->nullable();
            $table->float('gst')->nullable();
            $table->double('special_basic')->nullable();
            $table->double('special_gst')->nullable();
            $table->double('outstation_basic')->nullable();
            $table->double('outstation_gst')->nullable();
            $table->double('guest_price')->nullable();
            $table->string('out_of_stock', 10)->default(0);
            $table->string('yet_to_launch', 10)->nullable()->default(0);
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
