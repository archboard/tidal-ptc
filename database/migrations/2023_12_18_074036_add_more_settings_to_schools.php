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
        Schema::table('schools', function (Blueprint $table) {
            $table->string('timezone')->nullable();
            $table->dateTime('open_for_contacts_at')->nullable();
            $table->dateTime('close_for_contacts_at')->nullable();
            $table->dateTime('open_for_teachers_at')->nullable();
            $table->dateTime('close_for_teachers_at')->nullable();
            $table->boolean('allow_online_meetings')->default(false);
            $table->boolean('allow_translator_requests')->default(false);
            $table->integer('booking_buffer_hours')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            //
        });
    }
};
