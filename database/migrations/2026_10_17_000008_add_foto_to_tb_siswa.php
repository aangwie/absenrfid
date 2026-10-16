<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_siswa', function (Blueprint $table) {
            $table->longText('foto')->nullable()->after('tahun_masuk');
        });
    }

    public function down(): void
    {
        Schema::table('tb_siswa', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
