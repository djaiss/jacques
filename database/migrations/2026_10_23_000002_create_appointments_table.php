<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the appointment is booked with');
            $table->unsignedBigInteger('location_id')->nullable()->comment('location the appointment takes place in, null once the location is deleted');
            $table->unsignedBigInteger('client_id')->comment('client who receives the services');
            $table->unsignedBigInteger('staff_id')->nullable()->comment('staff member who performs the services, null once the staff member is deleted');
            $table->datetime('starts_at')->comment('when the appointment starts, in UTC');
            $table->datetime('ends_at')->comment('when the appointment ends, in UTC');
            $table->string('status', 30)->default('scheduled')->comment('scheduled, attendance_confirmed, cancelled or no_show');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->index(['client_id', 'starts_at']);
            $table->index(['staff_id', 'starts_at']);
        });
    }
};
