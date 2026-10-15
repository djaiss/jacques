<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the service belongs to');
            $table->unsignedBigInteger('service_category_id')->nullable()->comment('category the service is listed in, null when it has none');
            $table->json('name')->comment('name of the service in each salon language, keyed by language code');
            $table->json('description')->nullable()->comment('what the service consists of, in each salon language, keyed by language code');
            $table->json('prices')->comment('price in each salon currency, in its smallest unit, keyed by currency code');
            $table->boolean('is_active')->default(true)->comment('whether the service is offered, so a service can be withdrawn without being deleted');
            $table->unsignedInteger('position')->comment('display order of the service in its category, starting at 1');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('service_category_id')->references('id')->on('service_categories')->nullOnDelete();
        });
    }
};
