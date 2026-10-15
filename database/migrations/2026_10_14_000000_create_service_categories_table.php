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
            $table->string('name')->comment('name of the category, as it reads on the screen');
            $table->text('description')->nullable()->comment('what the category groups together');
            $table->unsignedInteger('position')->comment('display order of the category in the company, starting at 1');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name']);
        });
    }
};
