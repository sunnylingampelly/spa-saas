<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();

            $table->string('employee_code');
            $table->string('name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            $table->date('joining_date')->nullable();
            $table->string('department')->nullable();
            $table->string('designation')->nullable();

            $table->decimal('salary', 10, 2)->nullable();
            $table->enum('commission_type', ['percentage', 'flat'])->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(0);

            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->json('skills')->nullable();
            $table->json('specializations')->nullable();

            $table->unsignedTinyInteger('performance_rating')->nullable();
            $table->text('performance_notes')->nullable();

            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['spa_id', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
