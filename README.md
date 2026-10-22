# Absen RFID

Absensi sekolah via RFID / NISN. Laravel 12 + MySQL. Timezone default `Asia/Jakarta` (`APP_TIMEZONE`).

## Fitur

- `/tap` catat Masuk / Terlambat / Pulang by jam. Tolak di luar jam, tolak pulang tanpa masuk, tolak tap ganda.
- `/` home publik: rekap hari ini per siswa.
- `/dashboard` statistik: total siswa, masuk, pulang, terlambat, belum absen, rekap per kelas, 8 tap terakhir.
- `/siswa` CRUD + cek RFID duplikat + import + template. Search `q`, paginasi `withQueryString()`. Pas foto disimpan sebagai WebP di `storage/app/public/foto` (bukan base64).
- `/kelas` CRUD.
- `/laporan` filter `harian|periode|bulanan`, paginasi 15. Edit `Tanpa Keterangan` -> `Sakit|Ijin` saja. Ekspor `.xlsx` (ZipArchive stdlib) + PDF murni tanpa lib, `target=_blank`, `portrait|landscape`.
- `/pengaturan` 6 tab: `institusi|waktu|update|perawatan|backup|impor`.
  - Institusi: nama/alamat/no_hp + logo `jpg,jpeg,png,webp max:400KB` simpan `data:mime;base64` di `tb_pengaturan.logo_sekolah` (`LONGTEXT`).
  - Waktu: 6 kolom `masuk_mulai/selesai`, `terlambat_mulai/selesai`, `pulang_mulai/selesai` format `H:i`.
  - Update: `git fetch + reset --hard origin/{branch}` via token (tak disimpan, log `***`).
  - Perawatan: `optimize:clear`, `migrate --force`, symlink storage (`public/storage` → `storage/app/public`; opsional — foto siswa disajikan lewat route `/media/foto/…`).
  - Backup: dump `.sql` via PDO (`SHOW TABLES` + `SHOW CREATE TABLE` + `lazy(500)`).
  - Impor: `.sql max:10MB`, split batch 200 dalam transaksi.
- `/profil` update nama/password. `/login` email+password.
- `/api/tap` + `/api/ping` untuk perangkat **ESP32 + MFRC522** (header `X-API-Key`, JSON). Kunci per reader dikelola di **Pengaturan → API Perangkat** (tambah/nonaktifkan/regenerasi/hapus + log 50 tap terakhir). Aturan tap sama dengan kios. Dokumentasi: [`docs/api-rfid.md`](docs/api-rfid.md), firmware: [`docs/esp32_tap/esp32_tap.ino`](docs/esp32_tap/esp32_tap.ino), panduan dalam aplikasi: **Dokumentasi → API RFID**.

## Stack

- PHP 8.2, Composer 2.10, Laravel 12, MySQL, Blade.
- Tanpa lib ekspor PDF/Excel. `ZipArchive` + PDF manual.
- Tabel: `tb_kelas`, `tb_siswa`, `tb_absensi`, `tb_pengaturan`, `users`.

## Install (Windows + XAMPP)

Persyaratan: PHP ^8.2, Composer, MySQL jalan, Git.

```powershell
cd "d:\PROYEK LARAVEL\absenrfid"
composer install
copy .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME=AbsenRFID
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absenrfid
DB_USERNAME=root
DB_PASSWORD=
```

Buat DB bila belum ada:

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS absenrfid;"
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

`storage:link` membuat `public/storage → storage/app/public` (opsional). Foto siswa tetap tampil tanpa symlink karena disajikan lewat route `/media/foto/…`. Symlink bisa juga dibuat dari menu Pengaturan → Perawatan → Symlink Storage.

Seed buat:

- User `admin@sekolah.test` / `admin123`.
- 5 kelas: `XII RPL 1`, `XII RPL 2`, `XI TKJ 2`, `X AKL 3`, `X IPA 1`.
- 6 siswa contoh (NISN `0082234749` = RFID `2234749610` dst).

## Cara Akses

Base `http://localhost:8000`.

| URL | Akses | Fungsi |
|---|---|---|
| `/` | publik | rekap hari ini |
| `/login` | guest | login `admin@sekolah.test` / `admin123` |
| `/dashboard` | auth | statistik + tap terakhir |
| `/siswa?q=` | auth | CRUD + import |
| `/kelas` | auth | CRUD |
| `/laporan?mode=harian&tanggal=YYYY-MM-DD` | auth | filter + paginasi |
| `/laporan?mode=periode&dari=&sampai=` | auth | range (auto swap bila terbalik) |
| `/laporan?mode=bulanan&bulan=YYYY-MM` | auth | bulanan |
| `/laporan/ekspor?mode=&format=xlsx|pdf&orientasi=` | auth | unduh, buka tab baru |
| `/laporan/{id}/edit` | auth | ubah `Tanpa Keterangan` -> `Sakit|Ijin` |
| `/pengaturan?tab=institusi` | auth | nama/alamat/no_hp/logo |
| `/pengaturan?tab=waktu` | auth | jam kategori |
| `/pengaturan?tab=backup` | auth | unduh `.sql` |
| `/pengaturan?tab=impor` | auth | upload `.sql` |
| `/pengaturan?tab=api` | admin | kelola perangkat RFID + kunci + log tap |
| `/dokumentasi?tab=api` | auth | panduan API RFID di dalam aplikasi |
| `POST /tap` | publik | `tag`/`rfid_tag` -> JSON / redirect |
| `POST /api/tap` | header `X-API-Key` | tap perangkat RFID -> JSON status |
| `GET /api/ping` | header `X-API-Key` | uji koneksi + jam server |

Jam default (`WaktuAbsensi::rentang()`):

- Masuk `06:30-07:30` -> `Tepat Waktu`.
- Terlambat `07:31-13:29` -> `Terlambat`.
- Pulang `13:30-14:30` -> catat `jam_pulang` / `Selesai`.
- Luar itu -> `Tanpa Keterangan`, tap ditolak 422.

Contoh tap:

```powershell
curl -X POST http://localhost:8000/tap -H "Accept: application/json" -d "tag=2234749610"
```

## API Perangkat RFID (ESP32 + MFRC522)

Endpoint khusus perangkat, tanpa sesi/CSRF, autentikasi lewat `RFID_API_KEY` di `.env`:

```bash
# cek koneksi + kunci
curl -H "X-API-Key: $RFID_API_KEY" https://domain-anda.com/api/ping

# tap kartu -> JSON MASUK / TERLAMBAT / PULANG / GAGAL
curl -X POST https://domain-anda.com/api/tap \
  -H "X-API-Key: $RFID_API_KEY" -d "tag=2234749610"
```

Respons selalu JSON dengan `ok` + `kode`. Hasil bisnis memakai HTTP 200 agar firmware mudah memproses; hanya 401/429/503 untuk masalah kunci/throttling.

| `kode` | Arti |
|---|---|
| `MASUK` / `TERLAMBAT` | Tap masuk berhasil |
| `PULANG` | Tap pulang berhasil |
| `SUDAH_ABSEN` | Sudah masuk & pulang |
| `SUDAH_MASUK` | Sudah masuk, belum jam pulang |
| `PULANG_TANPA_MASUK` | Pulang sebelum masuk |
| `DI_LUAR_JAM` | Di luar rentang absensi |
| `TAG_TIDAK_DIKENAL` | RFID/NISN tidak terdaftar |
| `GAGAL` | Tag kosong / error lain |

Kunci perangkat dibuat otomatis di **Pengaturan → API Perangkat** (satu kunci per reader, bisa dicabut tanpa mengganggu reader lain). Kunci induk opsional untuk uji cepat:

```bash
php -r "echo bin2hex(random_bytes(24));"
# lalu isi RFID_API_KEY di .env, dan: php artisan config:clear
```

Detail lengkap + contoh firmware: [`docs/api-rfid.md`](docs/api-rfid.md).

## Peringatan

- Tab Update `reset --hard` hapus perubahan lokal permanen.
- Tab Perawatan `migrate --force` ubah struktur langsung.
- Tab Impor timpa semua data. Backup dulu.

`ponytail:` dump PDO + load ekspor sekaligus OK skala sekolah. Pindah `mysqldump` + chunk stream bila >10rb baris.
