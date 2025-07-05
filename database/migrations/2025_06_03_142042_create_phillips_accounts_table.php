<?php

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
        Schema::create('phillips_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('email')->unique();
            $table->string('account_password')->nullable();
            $table->string('account_status')->default('dormant');
            $table->string('email_app_password')->nullable();
            $table->string('email_status')->default('dormant');
            $table->time('last_email_update')->nullable();
            $table->string('status')->default('dormant');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phillips_accounts');
    }
};
