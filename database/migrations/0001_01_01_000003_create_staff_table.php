<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the staff member works for');
            $table->unsignedBigInteger('user_id')->nullable()->unique()->comment('account the staff member signs in with, null when they have none');
            $table->string('first_name')->comment('first name of the staff member');
            $table->string('last_name')->nullable()->comment('last name of the staff member');
            $table->string('display_name')->nullable()->comment('name the staff member goes by, when it differs from their legal name');
            $table->string('email')->nullable()->comment('email address the staff member uses at work');
            $table->string('phone')->nullable()->comment('phone number the staff member uses at work');
            $table->string('job_title')->nullable()->comment('job title shown to colleagues and clients');
            $table->text('bio')->nullable()->comment('short presentation of the staff member');
            $table->string('avatar_path')->nullable()->comment('path of the avatar file');
            $table->boolean('is_active')->default(true)->comment('whether the staff member still works for the company');
            $table->boolean('is_bookable')->default(true)->comment('whether clients can book the staff member');
            $table->unsignedInteger('position')->default(0)->comment('order of the staff member in the team');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
