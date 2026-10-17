<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_staff', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('location_id')->comment('location the staff member is assigned to');
            $table->unsignedBigInteger('staff_id')->comment('staff member assigned to the location');
            $table->boolean('is_active')->default(true)->comment('whether the assignment is in effect, so it can be paused without being removed');
            $table->boolean('follows_opening_hours')->default(true)->comment('whether the staff member works the opening hours of the location rather than hours of their own');
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->unique(['location_id', 'staff_id']);
        });
    }
};
