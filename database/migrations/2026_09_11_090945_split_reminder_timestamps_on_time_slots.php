<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->renameColumn('reminder_sent_at', 'contact_reminded_at');
            $table->timestamp('staff_reminded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropColumn('staff_reminded_at');
            $table->renameColumn('contact_reminded_at', 'reminder_sent_at');
        });
    }
};
