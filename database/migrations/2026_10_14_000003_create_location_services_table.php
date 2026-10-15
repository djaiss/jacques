<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_services', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('location_id')->comment('location that offers the service');
            $table->unsignedBigInteger('service_id')->comment('service the location offers');
            $table->unsignedInteger('price')->nullable()->comment('price at this location, in the smallest unit of the company currency, null to use the price of the service');
            $table->boolean('is_active')->default(true)->comment('whether the location offers the service at the moment');
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->unique(['location_id', 'service_id']);
        });
    }
};
