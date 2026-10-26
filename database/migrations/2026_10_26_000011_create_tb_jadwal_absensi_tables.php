<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jadwal rutin per hari: 1 = Senin ... 7 = Minggu (ISO-8601).
        if (! Schema::hasTable('tb_jadwal_absensi')) {
            Schema::create('tb_jadwal_absensi', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('hari')->unique();
                $table->string('masuk_mulai', 5);
                $table->string('masuk_selesai', 5);
                $table->string('terlambat_mulai', 5);
                $table->string('terlambat_selesai', 5);
                $table->string('pulang_mulai', 5);
                $table->string('pulang_selesai', 5);
                $table->timestamps();
            });
        }

        // Jadwal khusus pada rentang tanggal tertentu, menimpa jadwal rutin.
        if (! Schema::hasTable('tb_jadwal_absensi_khusus')) {
            Schema::create('tb_jadwal_absensi_khusus', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai');
                $table->string('masuk_mulai', 5);
                $table->string('masuk_selesai', 5);
                $table->string('terlambat_mulai', 5);
                $table->string('terlambat_selesai', 5);
                $table->string('pulang_mulai', 5);
                $table->string('pulang_selesai', 5);
                $table->string('keterangan', 150)->nullable();
                $table->timestamps();

                $table->index(['tanggal_mulai', 'tanggal_selesai']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_jadwal_absensi_khusus');
        Schema::dropIfExists('tb_jadwal_absensi');
    }
};
