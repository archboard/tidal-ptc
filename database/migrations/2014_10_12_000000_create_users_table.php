<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('sis_id')->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('password')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('timezone')->nullable();
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('school_user', function (Blueprint $table) {
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->primary(['school_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('school_user');
        Schema::dropIfExists('users');
    }
}
