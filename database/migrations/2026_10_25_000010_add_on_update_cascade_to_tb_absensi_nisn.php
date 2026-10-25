<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NISN bisa diedit dari halaman siswa, jadi FK tb_absensi.nisn perlu
     * ON UPDATE CASCADE agar riwayat absensi ikut berubah otomatis.
     */
    public function up(): void
    {
        Schema::table('tb_absensi', function (Blueprint $table) {
            $table->dropForeign(['nisn']);
            $table->foreign('nisn')->references('nisn')->on('tb_siswa')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tb_absensi', function (Blueprint $table) {
            $table->dropForeign(['nisn']);
            $table->foreign('nisn')->references('nisn')->on('tb_siswa')
                ->cascadeOnDelete();
        });
    }
};
