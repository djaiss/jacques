<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->string('name')->comment('name of the company');
            $table->string('slug')->unique()->comment('url friendly identifier of the company');
            $table->string('legal_name')->nullable()->comment('registered legal name of the company');
            $table->string('logo_path')->nullable()->comment('path of the logo file');
            $table->string('website_url')->nullable()->comment('website of the company');
            $table->string('currency', 3)->nullable()->comment('default currency of the company');
            $table->string('billing_email')->nullable()->comment('email address the invoices are sent to');
            $table->datetime('trial_ends_at')->nullable()->comment('when the trial ends');
            $table->boolean('is_welcome_screen_pending')->default(false)->comment('whether the company still has to see the welcome screen shown after signing up');
            $table->boolean('is_onboarding_pending')->default(false)->comment('whether the company still has to go through the onboarding');
            $table->unsignedTinyInteger('onboarding_step')->default(1)->comment('furthest onboarding step the company reached, from 1 to 4');
            $table->json('languages')->nullable()->comment('ordered list of the languages the salon writes its catalogue in, the first one being the fallback');
            $table->datetime('default_catalogue_created_at')->nullable()->comment('when the default catalogue was copied into the company, null until then');
            $table->json('settings')->nullable()->comment('configuration that does not deserve a column of its own, starting with the modules the company has turned on');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
