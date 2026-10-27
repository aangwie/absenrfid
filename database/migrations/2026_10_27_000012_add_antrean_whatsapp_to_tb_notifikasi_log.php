<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_notifikasi_log', function (Blueprint $table) {
            // Status absensi asli (Tepat Waktu, Terlambat, Selesai, Izin, ...).
            // Dipakai untuk menentukan pesan mana yang ditampung (dijadwalkan).
            $table->string('keterangan', 30)->nullable()->after('tipe');
            // Rencana waktu kirim. Pesan berjadwal ke depan belum disentuh worker.
            $table->timestamp('jadwal_kirim')->nullable()->after('status')->index();
            $table->timestamp('dibatalkan_at')->nullable()->after('sent_at');
        });

        // Status 'batal' untuk antrean yang dibatalkan admin. Enum MySQL harus
        // diubah eksplisit; driver lain menyimpan enum sebagai string biasa.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tb_notifikasi_log MODIFY status ENUM('antre','terkirim','gagal','batal') NOT NULL DEFAULT 'antre'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE tb_notifikasi_log SET status = 'gagal' WHERE status = 'batal'");
            DB::statement("ALTER TABLE tb_notifikasi_log MODIFY status ENUM('antre','terkirim','gagal') NOT NULL DEFAULT 'antre'");
        }

        Schema::table('tb_notifikasi_log', function (Blueprint $table) {
            $table->dropIndex(['jadwal_kirim']);
            $table->dropColumn(['keterangan', 'jadwal_kirim', 'dibatalkan_at']);
        });
    }
};
