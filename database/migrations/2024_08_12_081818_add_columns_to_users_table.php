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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->integer('mobile');
            $table->enum('role', ['admin', 'user']);
            $table->string('address_line_1')->nullable(); 
            $table->string('address_line_2')->nullable(); 
            $table->string('city')->nullable(); 
            $table->integer('pincode')->nullable(); 
            $table->string('gstin')->nullable(); 
            $table->string('state')->nullable(); 
            $table->string('country')->nullable(); 
            $table->integer('discount')->default(0); 
            $table->longText('category_discount'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
