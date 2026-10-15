<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_absences', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('staff_id')->comment('staff member who is absent');
            $table->unsignedBigInteger('location_id')->nullable()->comment('location the absence concerns, null when it concerns every location of the staff member');
            $table->datetime('starts_at')->comment('when the absence starts, in UTC');
            $table->datetime('ends_at')->comment('when the absence ends, in UTC');
            $table->string('reason')->nullable()->comment('short reason for the absence');
            $table->text('notes')->nullable()->comment('notes about the absence');
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->index(['staff_id', 'starts_at']);
        });
    }
};
