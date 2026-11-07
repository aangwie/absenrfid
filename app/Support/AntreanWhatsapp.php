<?php

namespace App\Support;

use App\Jobs\KirimNotifikasiAbsensi;
use App\Models\Kelas;
use App\Models\NotifikasiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Penjadwal antrean notifikasi WhatsApp.
 *
 * Hanya status yang dicentang di menu WhatsApp yang dikirim:
 * tepat waktu, terlambat, dan pulang. Semua pesan terpilih masuk satu antrean
 * dan dikirim bertahap sesuai pengaturan durasi/jumlah (mis. 10 menit /
 * 10 pesan = 1 pesan tiap 60 detik). Status yang tidak dicentang tidak dibuat
 * notifikasinya. Notifikasi dari aksi "Tandai" manual di /laporan selalu
 * dikirim karena merupakan tindakan admin.
 *
 * Semua pesan tercatat di tb_notifikasi_log beserta jadwal kirimnya sehingga
 * admin bisa melihat dan membatalkan antrean yang belum terkirim.
 */
class AntreanWhatsapp
{
    /** Durasi bawaan: 10 menit. */
    public const DURASI_DEFAULT = 10;

    /** Jumlah pesan bawaan per durasi. */
    public const JUMLAH_DEFAULT = 10;

    /** Status yang bisa dipilih untuk dikirim: kunci => label. */
    public const KATEGORI = [
        'tepat_waktu' => 'Tepat Waktu',
        'terlambat' => 'Terlambat',
        'pulang' => 'Pulang',
    ];

    /** Kolom sesi WhatsApp yang bisa diisi per kelas. */
    public const KOLOM_SESI = ['wa_url', 'wa_api_key', 'wa_number'];

    /** Device sesi pusat di gateway (dipakai bila kelas belum punya nomor). */
    public const DEVICE_PUSAT = 'pusat';

    /** Id device gateway untuk sesi milik satu kelas. */
    public static function deviceKelas(int $kelasId): string
    {
        return 'kelas-'.$kelasId;
    }

    /**
     * Sesi WhatsApp milik satu kelas: kolom di tb_kelas yang terisi dipakai,
     * yang kosong jatuh ke konfigurasi pusat (tab Konfigurasi). $kelasId null
     * berarti murni konfigurasi pusat.
     */
    public static function sesi(?int $kelasId): array
    {
        $kelas = $kelasId ? Kelas::find($kelasId) : null;
        $pusat = Pengaturan::ambil();

        $sesi = [];
        foreach (self::KOLOM_SESI as $kolom) {
            $sesi[$kolom] = trim((string) ($kelas?->{$kolom} ?? '')) ?: trim((string) ($pusat[$kolom] ?? ''));
        }

        // Device milik kelas hanya dipakai bila nomornya sudah terisi (hasil
        // scan). Tanpa nomor, pengiriman lewat device pusat supaya tidak
        // menunjuk sesi yang belum pernah dipindai.
        $sesi['device'] = trim((string) ($kelas?->wa_number ?? '')) !== ''
            ? self::deviceKelas((int) $kelasId)
            : self::DEVICE_PUSAT;

        return $sesi;
    }

    /**
     * Satu-satunya penulis nomor pengirim sesi: hasil scan (phone_number dari
     * gateway) atau null saat sesi diputuskan. Nomor disimpan dalam format
     * gateway (digit, sudah berkode negara).
     */
    public static function simpanNomor(?int $kelasId, ?string $nomor): void
    {
        $nomor = preg_replace('~\D~', '', (string) $nomor) ?: null;

        // Scan berhasil: kunci sesi sekalian dibuat bila belum ada, jadi admin
        // tidak perlu mengetik API Key.
        if ($nomor) {
            self::pastikanApiKey($kelasId);
        }

        if ($kelasId) {
            $kelas = Kelas::find($kelasId);
            if ($kelas && $kelas->wa_number !== $nomor) {
                $kelas->update(['wa_number' => $nomor]);
            }

            return;
        }

        if (Pengaturan::nilai('wa_number') === (string) $nomor) {
            return;
        }
        DB::table('tb_pengaturan')->updateOrInsert(
            ['kunci' => 'wa_number'],
            ['nilai' => (string) $nomor, 'updated_at' => now(), 'created_at' => now()]
        );
        Pengaturan::lupakan();
    }

    /**
     * API Key sesi: dipakai apa adanya bila sudah ada, kalau kosong digenerate
     * sekali lalu disimpan. Gateway BillNesia tidak memvalidasi field ini, jadi
     * kunci acak aman; nilainya hanya perlu stabil agar tidak berubah-ubah.
     */
    public static function pastikanApiKey(?int $kelasId): string
    {
        $kelas = $kelasId ? Kelas::find($kelasId) : null;
        $lama = trim((string) ($kelasId ? ($kelas?->wa_api_key ?? '') : Pengaturan::nilai('wa_api_key')));
        if ($lama !== '') {
            return $lama;
        }

        $kunci = 'wa_'.Str::random(40);

        if ($kelasId) {
            $kelas?->update(['wa_api_key' => $kunci]);
        } else {
            DB::table('tb_pengaturan')->updateOrInsert(
                ['kunci' => 'wa_api_key'],
                ['nilai' => $kunci, 'updated_at' => now(), 'created_at' => now()]
            );
            Pengaturan::lupakan();
        }

        return $kunci;
    }

    public static function durasiMenit(): int
    {
        return max(1, (int) Pengaturan::nilai('wa_batch_menit', (string) self::DURASI_DEFAULT));
    }

    public static function jumlahPesan(): int
    {
        return max(1, (int) Pengaturan::nilai('wa_batch_jumlah', (string) self::JUMLAH_DEFAULT));
    }

    /** Jarak antar pesan dalam antrean, dalam detik. */
    public static function jedaDetik(): int
    {
        return max(1, (int) round(self::durasiMenit() * 60 / self::jumlahPesan()));
    }

    /** Kategori sebuah notifikasi: tepat_waktu, terlambat, pulang, atau lain. */
    public static function kategori(string $tipe, ?string $keterangan): string
    {
        if ($tipe === 'pulang') {
            return 'pulang';
        }

        $keterangan = trim((string) $keterangan);
        if ($tipe === 'masuk') {
            if ($keterangan === 'Tepat Waktu') {
                return 'tepat_waktu';
            }
            if ($keterangan === 'Terlambat') {
                return 'terlambat';
            }
        }

        return 'lain';
    }

    public static function kunciKategori(string $kategori): string
    {
        return 'wa_kirim_' . $kategori;
    }

    /**
     * Apakah notifikasi dengan status ini boleh dikirim. Kategori "lain"
     * (tandai manual Izin/Sakit/Tanpa Keterangan) selalu boleh.
     */
    public static function aktif(string $tipe, ?string $keterangan): bool
    {
        $kategori = self::kategori($tipe, $keterangan);
        if ($kategori === 'lain') {
            return true;
        }

        return Pengaturan::nilai(self::kunciKategori($kategori), '1') !== '0';
    }

    /** Label status yang sedang aktif, untuk ditampilkan di UI. */
    public static function aktifLabel(): array
    {
        $label = [];
        foreach (self::KATEGORI as $kategori => $teks) {
            if (Pengaturan::nilai(self::kunciKategori($kategori), '1') !== '0') {
                $label[] = $teks;
            }
        }

        return $label;
    }

    /** Query log untuk satu kategori status. */
    public static function queryKategori(string $kategori): Builder
    {
        $query = NotifikasiLog::query();

        return match ($kategori) {
            'tepat_waktu' => $query->where('tipe', 'masuk')->where('keterangan', 'Tepat Waktu'),
            'terlambat' => $query->where('tipe', 'masuk')->where('keterangan', 'Terlambat'),
            'pulang' => $query->where('tipe', 'pulang'),
            default => $query->where('tipe', 'masuk')->whereNotIn('keterangan', ['Tepat Waktu', 'Terlambat']),
        };
    }

    /**
     * Tentukan waktu kirim untuk pesan baru: melanjutkan jadwal antrean
     * terakhir kelas yang sama, atau sekarang bila antrean kelas itu kosong.
     * Tiap kelas punya nomor sendiri, jadi jeda antreannya juga dipisah.
     */
    public static function jadwalkan(?int $kelasId = null): Carbon
    {
        $terakhir = NotifikasiLog::query()
            ->antre()
            ->whereNotNull('jadwal_kirim')
            ->when($kelasId, fn ($q) => $q->whereHas('siswa', fn ($s) => $s->where('kelas_id', $kelasId)))
            ->max('jadwal_kirim');

        $sekarang = now();
        if (! $terakhir) {
            return $sekarang;
        }

        $berikut = Carbon::parse($terakhir)->addSeconds(self::jedaDetik());

        return $berikut->greaterThan($sekarang) ? $berikut : $sekarang;
    }

    /**
     * Kunci pengaturan penanda kapan nomor pengirim ini terakhir dipakai.
     * Jeda antar pesan dijaga per nomor, supaya kelas A tidak ikut menunggu
     * nomor kelas B.
     */
    public static function kunciTerakhirKirim(string $nomorPengirim): string
    {
        return 'wa_terakhir_kirim_'.substr(sha1($nomorPengirim), 0, 12);
    }

    /**
     * Catat notifikasi lalu antrekan pengirimannya. Mengembalikan null bila
     * statusnya tidak dicentang di pengaturan.
     *
     * @param  bool  $langsung  Kirim sinkron saat itu juga (dipakai aksi Tandai
     *                          satu siswa di /laporan agar status bisa tampil).
     */
    public static function antre(
        Siswa $siswa,
        string $tanggal,
        string $tipe,
        ?string $keterangan,
        string $pesan,
        bool $langsung = false
    ): ?NotifikasiLog {
        if (! self::aktif($tipe, $keterangan)) {
            return null;
        }

        $tujuan = KirimNotifikasiAbsensi::normalisasiNomor($siswa->no_hp_ortu);
        $jadwal = $langsung ? now() : self::jadwalkan($siswa->kelas_id);

        $log = NotifikasiLog::create([
            'nisn' => $siswa->nisn,
            'tanggal' => $tanggal,
            'tipe' => $tipe,
            'keterangan' => $keterangan,
            'nomor_tujuan' => $tujuan !== '' ? $tujuan : trim((string) $siswa->no_hp_ortu),
            'pesan' => $pesan,
            'status' => 'antre',
            'jadwal_kirim' => $jadwal,
        ]);

        if ($tujuan === '') {
            $log->update([
                'status' => 'gagal',
                'respons' => 'Nomor HP ortu/wali kosong atau tidak valid.',
                'sent_at' => now(),
            ]);

            return $log;
        }

        if ($langsung) {
            (new KirimNotifikasiAbsensi($log->id))->handle();
        } else {
            KirimNotifikasiAbsensi::dispatch($log->id)->delay($jadwal);
        }

        return $log->refresh();
    }

    /** Batalkan seluruh antrean pada satu kategori; mengembalikan jumlahnya. */
    public static function batalkanKategori(string $kategori): int
    {
        return self::queryKategori($kategori)->antre()->update([
            'status' => 'batal',
            'dibatalkan_at' => now(),
            'respons' => 'Dinonaktifkan di pengaturan WhatsApp.',
        ]);
    }

    /** Perkiraan kapan seluruh antrean selesai dikirim (null bila kosong). */
    public static function perkiraanSelesai(): ?Carbon
    {
        $terakhir = NotifikasiLog::query()->antre()->max('jadwal_kirim');

        return $terakhir ? Carbon::parse($terakhir) : null;
    }
}
