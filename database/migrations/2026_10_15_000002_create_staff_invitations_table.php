<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_invitations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('staff_id')->unique()->comment('staff member invited to create an account, sent to their email address');
            $table->unsignedBigInteger('role_id')->comment('role the account will hold once it is created');
            $table->unsignedBigInteger('author_id')->nullable()->comment('user who prepared the invitation, null once their account is deleted');
            $table->string('token_hash', 64)->nullable()->unique()->comment('sha256 of the token of the latest send, null while prepared and once accepted or revoked');
            $table->timestamp('sent_at')->nullable()->comment('when the invitation was last sent, null while prepared');
            $table->timestamp('expires_at')->nullable()->comment('when the link of the latest send stops working');
            $table->timestamp('accepted_at')->nullable()->comment('when the staff member accepted, after which the invitation is never reusable');
            $table->timestamp('revoked_at')->nullable()->comment('when the salon withdrew the invitation');
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
