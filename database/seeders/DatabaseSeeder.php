<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sekolah.test'],
            ['name' => 'Admin', 'password' => Hash::make('admin123')]
        );

        $kelasList = ['XII RPL 1', 'XII RPL 2', 'XI TKJ 2', 'X AKL 3', 'X IPA 1'];
        foreach ($kelasList as $nama) {
            Kelas::firstOrCreate(['nama' => $nama]);
        }

        $siswas = [
            ['nisn' => '0082234749', 'rfid_tag' => '2234749610', 'nama' => 'Muhammad Rizky Pratama', 'kelas' => 'XII RPL 1', 'gender' => 'L'],
            ['nisn' => '0081234567', 'rfid_tag' => '1084291', 'nama' => 'Budi Santoso', 'kelas' => 'XII RPL 1', 'gender' => 'L'],
            ['nisn' => '0082345678', 'rfid_tag' => '2094812', 'nama' => 'Siti Aminah', 'kelas' => 'XII RPL 2', 'gender' => 'P'],
            ['nisn' => '0083456789', 'rfid_tag' => '3019284', 'nama' => 'Ahmad Rizky', 'kelas' => 'XI TKJ 2', 'gender' => 'L'],
            ['nisn' => '0084567890', 'rfid_tag' => '4082910', 'nama' => 'Dewi Lestari', 'kelas' => 'X AKL 3', 'gender' => 'P'],
            ['nisn' => '0086789012', 'rfid_tag' => '6012938', 'nama' => 'Anisa Rahmawati', 'kelas' => 'X IPA 1', 'gender' => 'P'],
        ];

        foreach ($siswas as $s) {
            $kelas = Kelas::firstOrCreate(['nama' => $s['kelas']]);
            Siswa::updateOrCreate(['nisn' => $s['nisn']], [
                'rfid_tag' => $s['rfid_tag'], 'nama' => $s['nama'], 'kelas_id' => $kelas->id, 'gender' => $s['gender'],
            ]);
        }

        $today = now()->toDateString();
        $hadir = [
            ['nisn' => '0081234567', 'jam_masuk' => '06:45:12', 'jam_pulang' => '15:30:05', 'status_masuk' => 'Tepat Waktu', 'status_pulang' => 'Selesai'],
            ['nisn' => '0082345678', 'jam_masuk' => '06:50:00', 'jam_pulang' => null, 'status_masuk' => 'Tepat Waktu', 'status_pulang' => 'Belum'],
            ['nisn' => '0083456789', 'jam_masuk' => '07:18:45', 'jam_pulang' => null, 'status_masuk' => 'Terlambat', 'status_pulang' => 'Belum'],
            ['nisn' => '0084567890', 'jam_masuk' => '06:30:10', 'jam_pulang' => '15:15:22', 'status_masuk' => 'Tepat Waktu', 'status_pulang' => 'Selesai'],
        ];

        foreach ($hadir as $h) {
            Absensi::updateOrCreate(
                ['nisn' => $h['nisn'], 'tanggal' => $today],
                $h + ['tanggal' => $today]
            );
        }
    }
}
