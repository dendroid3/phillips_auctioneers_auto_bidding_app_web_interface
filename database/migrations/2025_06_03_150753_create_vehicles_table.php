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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auction_session_id')->index();
            $table->unsignedBigInteger('phillips_account_id')->nullable()->index();
            $table->string('phillips_vehicle_id');
            $table->string('url')->nullable();
            $table->integer('current_bid')->nullable();
            $table->integer('start_amount')->nullable();
            $table->integer('maximum_amount')->nullable();
            $table->integer('lazy_stage_increment')->nullable();
            $table->integer('aggressive_stage_increment')->nullable();
            $table->integer('sniping_stage_increment')->nullable();
            $table->string('status')->default('unconfigurable ');
            $table->timestamps();
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
