<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_whatsapp_template', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            // tepat_waktu, terlambat, pulang, tandai, umum
            $table->string('kategori', 30)->index();
            $table->text('isi');
            $table->boolean('aktif')->default(true)->index();
            $table->timestamps();
        });

        // Template bawaan, sama persis dengan pesan yang selama ini dikirim
        // otomatis. Admin bisa mengubahnya lewat tab Template.
        $sekarang = now();
        $bawaan = [
            [
                'nama' => 'Notifikasi Masuk Tepat Waktu',
                'kategori' => 'tepat_waktu',
                'isi' => '[Absensi {institusi}] {nama} ({nisn}, {kelas}) telah MASUK pada {tanggal}{waktu} ({status}).',
            ],
            [
                'nama' => 'Notifikasi Masuk Terlambat',
                'kategori' => 'terlambat',
                'isi' => '[Absensi {institusi}] {nama} ({nisn}, {kelas}) telah MASUK pada {tanggal}{waktu} ({status}).',
            ],
            [
                'nama' => 'Notifikasi Pulang',
                'kategori' => 'pulang',
                'isi' => '[Absensi {institusi}] {nama} ({nisn}, {kelas}) telah PULANG pada {tanggal}{waktu} ({status}).',
            ],
            [
                'nama' => 'Notifikasi Tandai Manual',
                'kategori' => 'tandai',
                'isi' => '[Absensi {institusi}] {nama} ({nisn}, {kelas}) ditandai {status} pada {tanggal}{waktu}.',
            ],
            [
                'nama' => 'Pesan Umum',
                'kategori' => 'umum',
                'isi' => 'Halo Bapak/Ibu, ini pesan dari {institusi} mengenai siswa {nama} ({nisn}, {kelas}).',
            ],
        ];

        foreach ($bawaan as $template) {
            DB::table('tb_whatsapp_template')->insert($template + [
                'aktif' => true,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_whatsapp_template');
    }
};
