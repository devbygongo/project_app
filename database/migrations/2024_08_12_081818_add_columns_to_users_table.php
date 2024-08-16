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
            $table->enum('role', ['admin', 'user'])->after('expires_at');
            $table->string('address_line_1')->nullable()->after('role'); 
            $table->string('address_line_2')->nullable()->after('address_line_1'); 
            $table->string('city')->nullable()->after('address_line_1'); 
            $table->integer('pincode')->nullable()->after('city'); 
            $table->string('gstin')->nullable()->after('pincode'); 
            $table->string('state')->nullable()->after('gstin'); 
            $table->string('country')->nullable()->after('state'); 
            $table->integer('discount')->default(0)->after('country'); 
            $table->longText('category_discount')->after('discount'); 
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
