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
            $table->string('name_in_hindi')->nullable()->after('mobile');
            $table->string('name_in_telugu')->nullable()->after('name_in_hindi');
            $table->integer('otp')->after('name_in_telugu')->nullable();
            $table->timestamp('expires_at')->after('otp')->nullable();
            $table->enum('role', ['admin', 'user', 'guest'])->default('user')->after('expires_at');
            $table->enum('is_verified', ['0', '1'])->default('0')->after('role');
            $table->string('address_line_1')->nullable()->after('is_verified')->nullable(); 
            $table->string('address_line_2')->nullable()->after('address_line_1')->nullable(); 
            $table->string('city')->nullable()->after('address_line_2')->nullable(); 
            $table->integer('pincode')->nullable()->after('city')->nullable(); 
            $table->string('gstin')->nullable()->after('pincode')->nullable(); 
            $table->string('state')->nullable()->after('gstin')->nullable(); 
            $table->string('country')->nullable()->after('state')->nullable(); 
            // $table->integer('discount')->default(0)->after('country'); 
            // $table->integer('markup')->default(0)->after('country')->nullable(); 
            // $table->longText('category_discount')->after('discount'); 
            $table->string('type')->after('country'); 
            $table->integer('app_status')->default(0)->after('type'); 
            $table->timestamp('last_viewed')->default(DB::raw('CURRENT_TIMESTAMP'))->after('app_status');
            $table->enum('purchase_lock', ['0', '1'])->nullable()->after('last_viewed');
            $table->string('purchase_limit')->nullable()->after('purchase_lock');
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
