<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_member_services', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('user_id')->comment('team member who performs the service');
            $table->unsignedBigInteger('location_service_id')->comment('service, as offered at one location, that the team member performs there');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('location_service_id')->references('id')->on('location_services')->cascadeOnDelete();
            $table->unique(['user_id', 'location_service_id']);
        });
    }
};
