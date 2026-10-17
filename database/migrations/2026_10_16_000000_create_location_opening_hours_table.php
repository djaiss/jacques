<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_opening_hours', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('location_id')->comment('location open during the range');
            $table->unsignedTinyInteger('day_of_week')->comment('day of the week the range repeats on, from 0 for Sunday to 6 for Saturday');
            $table->time('start_time')->comment('time the range starts, in the time zone of the location');
            $table->time('end_time')->comment('time the range ends, in the time zone of the location');
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->index(['location_id', 'day_of_week']);
        });
    }
};
