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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->after('name');
        });

        // Бэкфилл: логин из локальной части email, при коллизии — числовой суффикс
        $taken = [];

        foreach (DB::table('users')->orderBy('id')->get(['id', 'email']) as $user) {
            $base = Str::of(Str::before($user->email, '@'))
                ->lower()
                ->replaceMatches('/[^a-z0-9_.\-]/', '')
                ->substr(0, 26)
                ->toString();

            if (mb_strlen($base) < 3) {
                $base = 'user'.$user->id;
            }

            $candidate = $base;
            $suffix = 2;

            while (in_array($candidate, $taken, true)) {
                $candidate = $base.$suffix++;
            }

            $taken[] = $candidate;
            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
