<?php

namespace App\Support;

use App\Jobs\KirimNotifikasiAbsensi;
use App\Models\Absensi;
use App\Models\Siswa;

/**
 * Inti logika tap kartu RFID.
 *
 * Hanya kode RFID yang diterima. NISN sengaja ditolak agar absensi tidak bisa
 * dilakukan dengan mengetik nomor induk siswa.
 *
 * Dipakai bersama oleh kios web (DashboardController@tap) dan API perangkat
 * (Api\TapController@tap) supaya aturan absensi tidak pernah berbeda antara
 * keduanya.
 */
class TapAbsensi
{
    /**
     * Proses satu tap.
     *
     * @return array{
     *     ok: bool,
     *     kode: string,
     *     http: int,
     *     pesan: string,
     *     type: ?string,
     *     nama: ?string,
     *     jam: ?string,
     *     status: ?string,
     *     siswa: ?Siswa,
     *     absensi: ?Absensi
     * }
     */
    public function proses(string $tag): array
    {
        $tag = trim($tag);

        if ($tag === '') {
            return $this->hasil(false, 'GAGAL', 422, 'Tempel kartu RFID dulu.');
        }

        $siswa = Siswa::with('kelas')
            ->where('rfid_tag', $tag)
            ->first();

        if (! $siswa) {
            // NISN tidak boleh dipakai sebagai pengganti kartu RFID.
            if (Siswa::where('nisn', $tag)->exists()) {
                return $this->hasil(
                    false, 'NISN_DITOLAK', 422,
                    'NISN tidak boleh dipakai untuk absensi. Tempel kartu RFID.'
                );
            }

            return $this->hasil(false, 'TAG_TIDAK_DIKENAL', 404, "Kartu [$tag] tidak terdaftar!");
        }

        $sekarang = now();
        $today = $sekarang->toDateString();
        $jam = $sekarang->format('H:i:s');
        $abs = Absensi::firstOrNew(['nisn' => $siswa->nisn, 'tanggal' => $today]);
        $kat = WaktuAbsensi::kategori($jam, $today);

        if ($kat === 'Tanpa Keterangan') {
            return $this->hasil(
                false, 'DI_LUAR_JAM', 422,
                'Di luar jam absensi ('.$jam.'), status Tanpa Keterangan.',
                $siswa, $abs, $jam
            );
        }

        // Belum ada baris absensi / belum ada jam masuk.
        if (! $abs->exists || ! $abs->jam_masuk) {
            // Tap pulang lebih awal (di rentang pulang tapi belum ada masuk):
            // tolak agar urutan Masuk -> Pulang terjaga.
            if ($kat === 'Pulang') {
                return $this->hasil(
                    false, 'PULANG_TANPA_MASUK', 422,
                    $siswa->nama.' belum ada jam masuk, tap pulang ditolak.',
                    $siswa, $abs, $jam
                );
            }

            $abs->jam_masuk = $jam;
            $abs->status_masuk = $kat === 'Terlambat' ? 'Terlambat' : 'Tepat Waktu';
            $abs->status_pulang = 'Belum';
            $abs->save();

            $this->antrekanNotifikasi($siswa, $today, $jam, 'masuk', $abs->status_masuk);

            $kode = $abs->status_masuk === 'Terlambat' ? 'TERLAMBAT' : 'MASUK';

            return $this->hasil(
                true, $kode, 200,
                $siswa->nama.' tercatat MASUK pukul '.$jam.' ('.$abs->status_masuk.').',
                $siswa, $abs, $jam, 'MASUK', $abs->status_masuk
            );
        }

        // Sudah masuk, belum pulang.
        if (! $abs->jam_pulang) {
            if ($kat !== 'Pulang') {
                return $this->hasil(
                    false, 'SUDAH_MASUK', 422,
                    'Di luar jam pulang ('.$jam.'), tap pulang ditolak.',
                    $siswa, $abs, $jam
                );
            }

            $abs->jam_pulang = $jam;
            $abs->status_pulang = 'Selesai';
            $abs->save();

            $this->antrekanNotifikasi($siswa, $today, $jam, 'pulang', 'Selesai');

            return $this->hasil(
                true, 'PULANG', 200,
                $siswa->nama.' tercatat PULANG pukul '.$jam.'.',
                $siswa, $abs, $jam, 'PULANG', 'Selesai'
            );
        }

        // Sudah tap masuk & pulang.
        return $this->hasil(
            false, 'SUDAH_ABSEN', 200,
            $siswa->nama.' sudah tap masuk & pulang.',
            $siswa, $abs, $jam
        );
    }

    /**
     * Bangun hasil tap yang seragam untuk web dan API.
     */
    private function hasil(
        bool $ok,
        string $kode,
        int $http,
        string $pesan,
        ?Siswa $siswa = null,
        ?Absensi $absensi = null,
        ?string $jam = null,
        ?string $type = null,
        ?string $status = null
    ): array {
        return [
            'ok' => $ok,
            'kode' => $kode,
            'http' => $http,
            'pesan' => $pesan,
            'type' => $type,
            'nama' => $siswa?->nama,
            'jam' => $jam,
            'status' => $status,
            'siswa' => $siswa,
            'absensi' => $absensi,
        ];
    }

    /**
     * Catat notifikasi absensi ke tb_notifikasi_log lalu antrekan pengiriman
     * WA ke ortu/wali. Absensi MASUK "Tepat Waktu" ditampung dan dikirim
     * bertahap sesuai pengaturan durasi/jumlah di menu WhatsApp; status lain
     * (Terlambat, Izin, Sakit, Pulang) dikirim segera dengan jeda pengaman.
     */
    private function antrekanNotifikasi(Siswa $siswa, string $tanggal, string $jam, string $tipe, string $status): void
    {
        try {
            $pesan = KirimNotifikasiAbsensi::susunPesan($siswa, $tipe, $jam, $status, $tanggal);
            AntreanWhatsapp::antre($siswa, $tanggal, $tipe, $status, $pesan);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
