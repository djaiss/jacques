<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_location_services', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('staff_id')->comment('staff member who performs the service');
            $table->unsignedBigInteger('location_service_id')->comment('service, as offered at one location, that the staff member performs there');
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->foreign('location_service_id')->references('id')->on('location_services')->cascadeOnDelete();
            $table->unique(['staff_id', 'location_service_id']);
        });
    }
};
