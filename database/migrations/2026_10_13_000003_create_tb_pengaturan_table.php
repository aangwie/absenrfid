<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tb_pengaturan')) {
            Schema::create('tb_pengaturan', function (Blueprint $table) {
                $table->id();
                $table->string('kunci', 50)->unique();
                $table->string('nilai', 255)->nullable();
                $table->timestamps();
            });
        }

        $defaults = [
            'masuk_mulai' => '06:30',
            'masuk_selesai' => '07:30',
            'terlambat_mulai' => '07:31',
            'terlambat_selesai' => '13:29',
            'pulang_mulai' => '13:30',
            'pulang_selesai' => '14:30',
            'github_repo' => '',
            'github_branch' => 'main',
        ];
        foreach ($defaults as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $v, 'updated_at' => now(), 'created_at' => now()]);
        }

        // ponytail: VARCHAR agar kategori baru (Tanpa Keterangan) muat tanpa alter ENUM tiap ganti aturan. Balik ke ENUM bila butuh constraint ketat di DB.
        try {
            DB::statement("ALTER TABLE tb_absensi MODIFY status_masuk VARCHAR(20) NOT NULL DEFAULT 'Tepat Waktu'");
            DB::statement("ALTER TABLE tb_absensi MODIFY status_pulang VARCHAR(20) NOT NULL DEFAULT 'Belum'");
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_pengaturan');
    }
};
