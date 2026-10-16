<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_siswa', function (Blueprint $table) {
            $table->string('tempat_lahir', 50)->nullable()->after('nama');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->unsignedSmallInteger('tahun_masuk')->nullable()->after('tanggal_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('tb_siswa', function (Blueprint $table) {
            $table->dropColumn(['tempat_lahir', 'tanggal_lahir', 'tahun_masuk']);
        });
    }
};
