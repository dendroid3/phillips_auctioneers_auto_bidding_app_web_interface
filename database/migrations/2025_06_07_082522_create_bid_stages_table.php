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
        Schema::create('bid_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('vehicle_id')->index();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('increment');
            $table->string('status')->default('dormant');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_stages');
    }
};
