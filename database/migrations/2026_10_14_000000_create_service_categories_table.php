<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the category belongs to');
            $table->json('name')->comment('name of the category in each salon language, keyed by language code');
            $table->json('description')->nullable()->comment('what the category groups together, in each salon language, keyed by language code');
            $table->unsignedInteger('position')->comment('display order of the category in the company, starting at 1');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }
};
