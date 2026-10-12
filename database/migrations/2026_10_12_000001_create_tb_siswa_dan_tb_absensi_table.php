<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_siswa', function (Blueprint $table) {
            $table->id();
            $table->string('nisn', 20)->unique();
            $table->string('rfid_tag', 50)->unique();
            $table->string('nama', 100);
            $table->string('kelas', 20);
            $table->enum('gender', ['L', 'P']);
            $table->timestamps();
        });

        Schema::create('tb_absensi', function (Blueprint $table) {
            $table->id();
            $table->string('nisn', 20);
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->enum('status_masuk', ['Tepat Waktu', 'Terlambat'])->default('Tepat Waktu');
            $table->enum('status_pulang', ['Belum', 'Selesai'])->default('Belum');
            $table->timestamps();

            $table->unique(['nisn', 'tanggal']);
            $table->foreign('nisn')->references('nisn')->on('tb_siswa')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_absensi');
        Schema::dropIfExists('tb_siswa');
    }
};
