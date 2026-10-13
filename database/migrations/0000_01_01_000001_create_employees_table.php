<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the employee works for');
            $table->string('employee_number')->nullable()->comment('identifier of the employee in the payroll or hr systems of the company');
            $table->string('first_name')->comment('first name of the employee');
            $table->string('last_name')->comment('last name of the employee');
            $table->string('display_name')->nullable()->comment('name the employee goes by, when it differs from their legal name');
            $table->string('avatar_path')->nullable()->comment('path of the avatar file');
            $table->string('work_email')->nullable()->comment('email address the employee uses at work');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'employee_number']);
            $table->unique(['company_id', 'work_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
