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
        Schema::create('auction_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('vehicles_url');
            $table->date('date');
            $table->string('status')->default('unconfigurable');
            $table->time('start_time')->default('10:00:00');
            $table->time('end_time')->default('13:00:00');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_sessions');
    }
};
