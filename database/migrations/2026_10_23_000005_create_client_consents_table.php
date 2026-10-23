<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_consents', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('client_id')->comment('client whose consent changed');
            $table->string('channel', 20)->comment('channel the consent covers');
            $table->boolean('is_granted')->comment('whether the client granted or withdrew the consent');
            $table->unsignedBigInteger('staff_id')->nullable()->comment('staff member who recorded the change, null when the client gave it');
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->index(['client_id', 'channel', 'created_at']);
        });
    }
};
