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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamp('reserved_at')->nullable();
            $table->text('teacher_notes')->nullable();
            $table->text('contact_notes')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->boolean('allow_online_meetings')->default(false);
            $table->boolean('is_online')->default(false);
            $table->boolean('requested_online')->default(false);
            $table->boolean('contact_can_book')->default(true);
            $table->boolean('allow_translator_requests')->default(false);
            $table->string('language_id')->nullable();
            $table->text('translator_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
