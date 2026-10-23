<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_technical_notes', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('client_id')->comment('client the service was performed on, copied from the appointment to list the notes of a client directly');
            $table->unsignedBigInteger('appointment_service_id')->comment('service of an appointment the note records');
            $table->unsignedBigInteger('staff_id')->nullable()->comment('staff member who wrote the note, null once the staff member is deleted');
            $table->string('author_name')->comment('name of the author when the note was written, kept once the staff member is deleted');
            $table->text('body')->comment('formula, products, processing time and result, encrypted');
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('appointment_service_id')->references('id')->on('appointment_services')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->index(['client_id', 'created_at']);
        });
    }
};
