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
            $table->string('email')->nullable()->default(null)->change();
            $table->string('mobile', 13)->after('remember_token');
            $table->integer('otp')->after('mobile')->nullable();
            $table->timestamp('expires_at')->after('otp')->nullable();
            $table->enum('role', ['admin', 'user'])->default('user')->after('expires_at');
            $table->enum('verified', ['0', '1'])->default('0')->after('role');
            $table->string('address_line_1')->nullable()->after('verified')->nullable(); 
            $table->string('address_line_2')->nullable()->after('address_line_1')->nullable(); 
            $table->string('city')->nullable()->after('address_line_2')->nullable(); 
            $table->integer('pincode')->nullable()->after('city')->nullable(); 
            $table->string('gstin')->nullable()->after('pincode')->nullable(); 
            $table->string('state')->nullable()->after('gstin')->nullable(); 
            $table->string('country')->nullable()->after('state')->nullable(); 
            // $table->integer('discount')->default(0)->after('country'); 
            $table->integer('markup')->default(0)->after('country')->nullable(); 
            // $table->longText('category_discount')->after('discount'); 
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
