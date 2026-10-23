<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_services', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('appointment_id')->comment('appointment the service is performed in');
            $table->unsignedBigInteger('service_id')->nullable()->comment('service of the catalogue, null once it is deleted');
            $table->json('name')->comment('name of the service at booking in each salon language, keyed by language code');
            $table->unsignedInteger('price')->comment('price of the service at booking, in the smallest unit of the currency');
            $table->char('currency', 3)->comment('currency of the price, as an iso 4217 code');
            $table->unsignedInteger('duration')->comment('duration of the service at booking, in minutes');
            $table->unsignedInteger('position')->default(0)->comment('order of the service in the appointment');
            $table->timestamps();

            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });
    }
};
