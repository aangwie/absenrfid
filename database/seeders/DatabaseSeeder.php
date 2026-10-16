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

        $kelasList = ['7A', '7B', '8A', '8B', '9A', '9B'];
        foreach ($kelasList as $nama) {
            Kelas::firstOrCreate(['nama' => $nama]);
        }

        $siswas = [
            ['nisn' => '0082234749', 'rfid_tag' => '2234749610', 'nama' => 'Muhammad Rizky Pratama', 'kelas' => '7A', 'gender' => 'L', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2011-05-12', 'tahun_masuk' => 2023],
            ['nisn' => '0081234567', 'rfid_tag' => '1084291', 'nama' => 'Budi Santoso', 'kelas' => '7B', 'gender' => 'L', 'tempat_lahir' => 'Jakarta', 'tanggal_lahir' => '2011-03-08', 'tahun_masuk' => 2023],
            ['nisn' => '0082345678', 'rfid_tag' => '2094812', 'nama' => 'Siti Aminah', 'kelas' => '8A', 'gender' => 'P', 'tempat_lahir' => 'Surabaya', 'tanggal_lahir' => '2012-08-20', 'tahun_masuk' => 2024],
            ['nisn' => '0083456789', 'rfid_tag' => '3019284', 'nama' => 'Ahmad Rizky', 'kelas' => '8B', 'gender' => 'L', 'tempat_lahir' => 'Yogyakarta', 'tanggal_lahir' => '2011-11-02', 'tahun_masuk' => 2023],
            ['nisn' => '0084567890', 'rfid_tag' => '4082910', 'nama' => 'Dewi Lestari', 'kelas' => '9A', 'gender' => 'P', 'tempat_lahir' => 'Semarang', 'tanggal_lahir' => '2012-01-15', 'tahun_masuk' => 2024],
            ['nisn' => '0086789012', 'rfid_tag' => '6012938', 'nama' => 'Anisa Rahmawati', 'kelas' => '9B', 'gender' => 'P', 'tempat_lahir' => 'Medan', 'tanggal_lahir' => '2012-06-30', 'tahun_masuk' => 2024],
        ];

        foreach ($siswas as $s) {
            $kelas = Kelas::firstOrCreate(['nama' => $s['kelas']]);
            Siswa::updateOrCreate(['nisn' => $s['nisn']], [
                'rfid_tag' => $s['rfid_tag'], 'nama' => $s['nama'], 'kelas_id' => $kelas->id, 'gender' => $s['gender'],
                'tempat_lahir' => $s['tempat_lahir'], 'tanggal_lahir' => $s['tanggal_lahir'], 'tahun_masuk' => $s['tahun_masuk'],
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
