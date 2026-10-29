<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Template pesan WhatsApp ke siswa / orang tua.
 *
 * Kategori yang dipakai otomatis: tepat_waktu, terlambat, pulang, dan tandai
 * (aksi Tandai manual di /laporan). Kategori umum hanya untuk pesan bebas /
 * uji kirim. Hanya satu template aktif per kategori yang dipakai; bila tidak
 * ada yang aktif, teks bawaan di bawah tetap dipakai agar notifikasi tidak
 * pernah kosong.
 */
class WhatsappTemplate extends Model
{
    protected $table = 'tb_whatsapp_template';

    protected $fillable = ['nama', 'kategori', 'isi', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    /** Label kategori untuk ditampilkan di UI. */
    public const KATEGORI = [
        'tepat_waktu' => 'Masuk Tepat Waktu',
        'terlambat' => 'Masuk Terlambat',
        'pulang' => 'Pulang',
        'tandai' => 'Tandai Manual',
        'umum' => 'Umum / Bebas',
    ];

    /** Template bawaan bila tabel kosong / semua nonaktif. */
    public static function bawaan(): array
    {
        return [
            'tepat_waktu' => [
                'nama' => 'Notifikasi Masuk Tepat Waktu',
                'isi' => '[Absensi {institusi}] {nama} ({nisn}, {kelas}) telah MASUK pada {tanggal}{waktu} ({status}).',
            ],
            'terlambat' => [
                'nama' => 'Notifikasi Masuk Terlambat',
                'isi' => '[Absensi {institusi}] {nama} ({nisn}, {kelas}) telah MASUK pada {tanggal}{waktu} ({status}).',
            ],
            'pulang' => [
                'nama' => 'Notifikasi Pulang',
                'isi' => '[Absensi {institusi}] {nama} ({nisn}, {kelas}) telah PULANG pada {tanggal}{waktu} ({status}).',
            ],
            'tandai' => [
                'nama' => 'Notifikasi Tandai Manual',
                'isi' => '[Absensi {institusi}] {nama} ({nisn}, {kelas}) ditandai {status} pada {tanggal}{waktu}.',
            ],
            'umum' => [
                'nama' => 'Pesan Umum',
                'isi' => 'Halo Bapak/Ibu, ini pesan dari {institusi} mengenai siswa {nama} ({nisn}, {kelas}).',
            ],
        ];
    }

    /** Daftar placeholder beserta keterangannya. */
    public const PLACEHOLDER = [
        '{nama}' => 'Nama siswa',
        '{nisn}' => 'NISN siswa',
        '{kelas}' => 'Nama kelas',
        '{institusi}' => 'Nama institusi / sekolah',
        '{tanggal}' => 'Tanggal absensi',
        '{jam}' => 'Jam (07:15), kosong bila tidak ada',
        '{waktu}' => '" pukul 07:15", kosong bila tidak ada',
        '{status}' => 'Status absensi (Tepat Waktu, Terlambat, Selesai, Izin, ...)',
        '{tipe}' => 'MASUK / PULANG',
        '{keterangan}' => 'Sama dengan status',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /** Template aktif untuk satu kategori, atau teks bawaan bila tidak ada. */
    public static function untukKategori(string $kategori): self
    {
        try {
            $template = static::query()
                ->where('kategori', $kategori)
                ->aktif()
                ->orderBy('id')
                ->first();
        } catch (\Throwable $e) {
            $template = null;
        }

        if ($template) {
            return $template;
        }

        $bawaan = static::bawaan()[$kategori] ?? static::bawaan()['umum'];

        return new static([
            'nama' => $bawaan['nama'],
            'kategori' => $kategori,
            'isi' => $bawaan['isi'],
            'aktif' => true,
        ]);
    }

    /**
     * Ganti placeholder dengan data siswa / absensi.
     *
     * @param  array<string, string>  $data  nama, nisn, kelas, institusi, tanggal, jam, status, tipe
     */
    public function render(Siswa $siswa, array $data = []): string
    {
        $institusi = trim((string) ($data['institusi'] ?? Pengaturan::nilai('nama_institusi')));
        $jam = trim((string) ($data['jam'] ?? ''));
        $status = (string) ($data['status'] ?? '');
        $tipe = strtoupper((string) ($data['tipe'] ?? ''));

        $ganti = [
            '{nama}' => (string) $siswa->nama,
            '{nisn}' => (string) $siswa->nisn,
            '{kelas}' => (string) ($data['kelas'] ?? $siswa->kelas->nama ?? '-'),
            '{institusi}' => $institusi !== '' ? $institusi : 'RFID',
            '{tanggal}' => (string) ($data['tanggal'] ?? ''),
            '{jam}' => $jam,
            '{waktu}' => $jam !== '' ? ' pukul '.$jam : '',
            '{status}' => $status,
            '{keterangan}' => $status,
            '{tipe}' => $tipe,
        ];

        return strtr((string) $this->isi, $ganti);
    }
}
