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
        Schema::create('t_stock_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 255);
            $table->integer('user_id');
            $table->date('order_date');
            $table->enum('type', ['IN', 'OUT']);
            $table->string('pdf', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_stock_orders');
    }
};
