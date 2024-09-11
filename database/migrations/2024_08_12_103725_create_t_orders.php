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
        Schema::create('t_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            // $table->foreignId('client_id')->references('id')->on('users');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->date('order_date');
            $table->float('amount');
            // $table->date('log_date');
            // $table->date('log_user');
            $table->enum('status', ['pending', 'partial', 'paid']);
            $table->enum('type', ['basic', 'gst']);
            $table->string('order_invoice')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_orders');
    }
};
