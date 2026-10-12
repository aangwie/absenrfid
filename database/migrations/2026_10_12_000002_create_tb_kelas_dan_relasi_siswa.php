<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 30)->unique();
            $table->timestamps();
        });

        Schema::table('tb_siswa', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('nama')->constrained('tb_kelas')->nullOnDelete();
        });

        foreach (DB::table('tb_siswa')->select('kelas')->distinct()->pluck('kelas') as $nama) {
            $nama = trim((string) $nama);
            if ($nama === '') {
                continue;
            }
            $id = DB::table('tb_kelas')->insertGetId(['nama' => $nama, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('tb_siswa')->where('kelas', $nama)->update(['kelas_id' => $id]);
        }

        Schema::table('tb_siswa', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });
    }

    public function down(): void
    {
        Schema::table('tb_siswa', function (Blueprint $table) {
            $table->string('kelas', 20)->nullable()->after('nama');
        });

        foreach (DB::table('tb_siswa')->join('tb_kelas as k', 'k.id', '=', 'tb_siswa.kelas_id')->select('tb_siswa.id', 'k.nama')->get() as $row) {
            DB::table('tb_siswa')->where('id', $row->id)->update(['kelas' => $row->nama]);
        }

        Schema::table('tb_siswa', function (Blueprint $table) {
            $table->dropForeign(['kelas_id']);
            $table->dropColumn('kelas_id');
        });

        Schema::dropIfExists('tb_kelas');
    }
};
