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
        Schema::create('t_job_card', function (Blueprint $table) {
            $table->id();
            $table->string('client_name', 191);
            $table->string('job_id', 32)->unique(); // generated in backend e.g., ACE-0001
            $table->string('mobile', 20);
            $table->enum('warranty', ['in_warranty', 'outside_warranty']);
            $table->string('serial_no', 100)->nullable();
            $table->string('model_no', 100)->nullable();
            $table->text('problem_description')->nullable();
            $table->string('assigned_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_job_card');
    }
};
