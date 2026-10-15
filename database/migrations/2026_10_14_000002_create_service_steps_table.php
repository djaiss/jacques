<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_steps', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('service_id')->comment('service the step is part of');
            $table->unsignedInteger('position')->comment('rank of the step in the service, starting at 1');
            $table->unsignedInteger('active_duration')->comment('minutes the team member works on the client');
            $table->unsignedInteger('processing_duration')->default(0)->comment('minutes of processing after the active part, during which the team member is free');
            $table->timestamps();

            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->unique(['service_id', 'position']);
        });
    }
};
