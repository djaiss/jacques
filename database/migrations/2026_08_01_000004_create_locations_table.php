<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the location belongs to');
            $table->string('name')->comment('name of the location, as it reads on the screen');
            $table->string('country', 2)->nullable()->comment('country the location is in, as an iso 3166-1 alpha-2 code');
            $table->string('city')->nullable()->comment('city the location is in');
            $table->text('address')->nullable()->comment('street address of the location');
            $table->string('timezone', 50)->nullable()->comment('timezone the location keeps');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
