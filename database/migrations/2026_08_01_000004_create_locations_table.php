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
            $table->string('region')->nullable()->comment('state, province or county the location is in');
            $table->string('city')->nullable()->comment('city the location is in');
            $table->string('postal_code', 20)->nullable()->comment('postal code of the location');
            $table->string('address_line_1')->nullable()->comment('street and number of the location');
            $table->string('address_line_2')->nullable()->comment('building, floor or suite of the location');
            $table->string('phone', 50)->nullable()->comment('phone number of the location');
            $table->string('email')->nullable()->comment('email address of the location');
            $table->string('timezone', 50)->nullable()->comment('timezone the location keeps');
            $table->timestamp('archived_at')->nullable()->comment('day the location was closed, null while it is still open');
            $table->boolean('is_headquarters')->default(false)->comment('whether the location is the headquarters of the company');
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
