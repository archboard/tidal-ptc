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
        Schema::create('school_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained();
            $table->string('language');
            $table->integer('request_max')->default(0);
            $table->integer('overlap_max')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_languages');
    }
};
