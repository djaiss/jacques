<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the client is a client of');
            $table->string('first_name')->nullable()->comment('first name of the client, null once the client is anonymized');
            $table->string('last_name')->nullable()->comment('last name of the client, null once the client is anonymized');
            $table->string('phone', 20)->nullable()->comment('mobile phone of the client in the international format, unique in the company, null once the client is anonymized');
            $table->string('email')->nullable()->comment('email address of the client');
            $table->string('gender', 20)->nullable()->comment('gender of the client, when the client gives it');
            $table->date('birth_date')->nullable()->comment('birth date of the client, when the client gives it');
            $table->string('address_line_1')->nullable()->comment('street and number of the client');
            $table->string('address_line_2')->nullable()->comment('building, floor or apartment of the client');
            $table->string('city')->nullable()->comment('city the client lives in');
            $table->string('region')->nullable()->comment('state, province or county the client lives in');
            $table->string('postal_code', 20)->nullable()->comment('postal code of the client');
            $table->string('country', 2)->nullable()->comment('country the client lives in, as an iso 3166-1 alpha-2 code');
            $table->unsignedBigInteger('preferred_staff_id')->nullable()->comment('staff member the client prefers to be served by');
            $table->text('special_instructions')->nullable()->comment('instructions the team must follow with the client, encrypted');
            $table->boolean('receives_sms_reminders')->default(true)->comment('whether the client receives appointment reminders by sms');
            $table->boolean('is_online_booking_blocked')->default(false)->comment('whether the client is refused on the public booking page');
            $table->datetime('anonymized_at')->nullable()->comment('when the personal data of the client was erased, in UTC');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('preferred_staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->unique(['company_id', 'phone']);
        });
    }
};
