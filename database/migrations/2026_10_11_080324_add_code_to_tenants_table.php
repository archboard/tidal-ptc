<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Districts enter this on the root domain to find their login page
            $table->string('code', 4)->nullable()->unique()->after('name');
        });

        DB::table('tenants')->whereNull('code')->pluck('id')->each(function (int $id) {
            do {
                $code = Str::upper(Str::random(4));
            } while (DB::table('tenants')->where('code', $code)->exists());

            DB::table('tenants')->where('id', $id)->update(['code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
