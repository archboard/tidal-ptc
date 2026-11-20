<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, bool> table => nullable */
    private array $tables = [
        'schools' => false,
        'users' => true,
        'students' => false,
        'sections' => false,
        'courses' => false,
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $nullable) {
            Schema::table($table, function (Blueprint $table) use ($nullable) {
                $table->string('sis_id')->nullable($nullable)->change();
            });
        }
    }

    /**
     * Reverts to the original integer types. Fails if any sis_id is non-numeric.
     */
    public function down(): void
    {
        foreach ($this->tables as $table => $nullable) {
            Schema::table($table, function (Blueprint $table) use ($nullable) {
                $column = $table->getTable() === 'schools'
                    ? $table->integer('sis_id')
                    : $table->unsignedBigInteger('sis_id');

                $column->nullable($nullable)->change();
            });
        }
    }
};
