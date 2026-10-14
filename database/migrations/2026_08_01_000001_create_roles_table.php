<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the role belongs to, as a role never reaches outside it');
            $table->string('name')->comment('name of the role, as it reads on the screen');
            $table->text('description')->nullable()->comment('what the role is for, in a sentence, as it reads on the screen');
            $table->string('slug')->comment('url friendly identifier of the role, unique within the company');
            $table->boolean('is_default')->default(false)->comment('whether the role is one of the roles every company starts with');
            $table->boolean('is_editable')->default(true)->comment('whether the role may be renamed, regranted or deleted');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
