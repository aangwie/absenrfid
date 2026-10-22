# API RFID untuk ESP32 + MFRC522

API ini dipakai perangkat RFID untuk mencatat absensi dan menerima status
kembali dalam bentuk JSON. Aturan absensi sama persis dengan kios web
(`/` dan `/dashboard`), karena keduanya memakai service `App\Support\TapAbsensi`.

- Base URL: `https://domain-anda.com/api`
- Format: JSON
- Autentikasi: header `X-API-Key`
- Tanpa sesi, tanpa CSRF, tanpa cookie
- Panduan yang sama juga tersedia di aplikasi: **Dokumentasi → API RFID** (termasuk contoh kode ESP32 siap tempel)

---

## 1. Autentikasi

Setiap request wajib mengirim kunci lewat header `X-API-Key`.

Ada dua jenis kunci:

1. **Kunci perangkat** (disarankan) — dibuat di menu
   **Pengaturan → API Perangkat**. Satu reader satu kunci, bisa dinonaktifkan
   atau dibuat ulang tanpa mengganggu reader lain.
2. **Kunci induk** — `RFID_API_KEY` di `.env`, untuk uji cepat / master.

```http
X-API-Key: KUNCI_PERANGKAT_ANDA
```

Perilaku perangkat:

- Kunci dihapus/diganti → request lama ditolak `401`.
- Perangkat dinonaktifkan (uncheck **Aktif**) → `403 PERANGKAT_NONAKTIF`.

Alternatif (bila perangkat tidak bisa mengatur header): parameter `api_key`
di body atau query string.

```bash
curl -X POST https://domain-anda.com/api/tap \
  -H "X-API-Key: KUNCI_ANDA" \
  -d "tag=2234749610"
```

Buat kunci baru:

```bash
php -r "echo bin2hex(random_bytes(24));"
```

Lalu isi di `.env`, dan jalankan `php artisan config:clear`.

| Kondisi | HTTP | `kode` |
|---|---|---|
| Kunci benar | 200 | sesuai hasil tap |
| Kunci salah / tidak dikirim | 401 | `UNAUTHORIZED` |
| Perangkat dinonaktifkan | 403 | `PERANGKAT_NONAKTIF` |
| Tidak ada kunci induk & perangkat tak dikenal | 503 | `API_KEY_BELUM_DISETEL` |
| Terlalu banyak request | 429 | `TOO_MANY_REQUESTS` |

---

## 2. `POST /api/tap`

Mencatat satu tap. Menerima `application/x-www-form-urlencoded`
(disarankan untuk ESP32) maupun `application/json`.

### Parameter

| Nama | Wajib | Keterangan |
|---|---|---|
| `tag` | ya | Isi RFID (`rfid_tag`) **atau** NISN siswa |
| `rfid_tag` / `uid` / `id` | — | Alias bila firmware memakai nama lain |

### Contoh

```bash
curl -X POST https://domain-anda.com/api/tap \
  -H "X-API-Key: KUNCI_ANDA" \
  -H "Accept: application/json" \
  -d "tag=2234749610"
```

### Respons sukses (MASUK / TERLAMBAT)

```json
{
  "ok": true,
  "kode": "TERLAMBAT",
  "status": "Terlambat",
  "pesan": "Muhammad Rizky Pratama tercatat MASUK pukul 07:42:10 (Terlambat).",
  "waktu": "2026-10-22T07:42:10+07:00",
  "siswa": {
    "nisn": "0082234749",
    "nama": "Muhammad Rizky Pratama",
    "kelas": "XII RPL 1",
    "rfid_tag": "2234749610"
  },
  "absensi": {
    "tanggal": "2026-10-22",
    "jam_masuk": "07:42:10",
    "jam_pulang": null,
    "status_masuk": "Terlambat",
    "status_pulang": "Belum"
  }
}
```

### Respons pulang

```json
{
  "ok": true,
  "kode": "PULANG",
  "status": "Selesai",
  "pesan": "Muhammad Rizky Pratama tercatat PULANG pukul 13:35:02.",
  "absensi": {
    "jam_masuk": "07:42:10",
    "jam_pulang": "13:35:02",
    "status_masuk": "Terlambat",
    "status_pulang": "Selesai"
  }
}
```

### Respons gagal

```json
{
  "ok": false,
  "kode": "TAG_TIDAK_DIKENAL",
  "status": null,
  "pesan": "Kartu [999999] tidak terdaftar!",
  "waktu": "2026-10-22T07:42:15+07:00",
  "siswa": null,
  "absensi": null
}
```

> Hasil bisnis **selalu HTTP 200** agar firmware cukup membaca `ok` dan `kode`.
> Hanya kesalahan autentikasi (401/503) dan throttling (429) yang memakai
> status HTTP lain.

### Daftar `kode`

| `kode` | `ok` | Arti | Tindakan di perangkat |
|---|---|---|---|
| `MASUK` | true | Tap masuk, tepat waktu | Tampilkan nama + "Masuk" |
| `TERLAMBAT` | true | Tap masuk, terlambat | Tampilkan nama + "Terlambat" |
| `PULANG` | true | Tap pulang tercatat | Tampilkan nama + "Pulang" |
| `SUDAH_ABSEN` | false | Sudah tap masuk **dan** pulang | Pesan "sudah absen" |
| `SUDAH_MASUK` | false | Sudah masuk, belum waktunya pulang | Pesan "sudah masuk" |
| `PULANG_TANPA_MASUK` | false | Tap pulang sebelum tap masuk | Pesan "belum masuk" |
| `DI_LUAR_JAM` | false | Di luar semua rentang jam absensi | Pesan "di luar jam" |
| `TAG_TIDAK_DIKENAL` | false | RFID/NISN tidak ada di `tb_siswa` | Pesan "kartu tidak dikenal" |
| `GAGAL` | false | Tag kosong / kesalahan lain | Pesan umum |

`status` berisi status manusiawi: `Tepat Waktu`, `Terlambat`, `Selesai`, atau `null`.

---

## 3. `GET /api/ping`

Uji koneksi, API key, dan jam server. Cocok dipanggil firmware saat boot.

```bash
curl -H "X-API-Key: KUNCI_ANDA" https://domain-anda.com/api/ping
```

```json
{
  "ok": true,
  "kode": "PONG",
  "pesan": "API RFID aktif.",
  "sekolah": "SMK Contoh",
  "perangkat": "Reader Gerbang",
  "waktu": "2026-10-22T10:17:20+07:00",
  "zona_waktu": "Asia/Jakarta",
  "kategori_sekarang": "Terlambat",
  "rentang": {
    "masuk_mulai": "06:30",
    "masuk_selesai": "07:30",
    "terlambat_mulai": "07:31",
    "terlambat_selesai": "13:29",
    "pulang_mulai": "13:30",
    "pulang_selesai": "14:30"
  },
  "total_siswa": 6
}
```

---

## 4. Format UID kartu

Aplikasi mencocokkan **string** `rfid_tag` di database, jadi firmware harus
mengirim UID dengan format yang sama seperti saat kartu didaftarkan.

Data contoh di proyek ini memakai **desimal big-endian** dari 4 byte UID:

```cpp
uint64_t uid = 0;
for (byte i = 0; i < rfid.uid.size; i++) {
  uid = (uid << 8) | rfid.uid.uidByte[i];
}
String tag = String(uid);   // contoh: "2234749610"
```

Kalau kartu di sekolah Anda didaftarkan memakai hex (`A1B2C3D4`), ubah firmware
agar mengirim hex, atau samakan saat input di menu **Siswa**.

> Kartu MIFARE Classic umumnya 4 byte. Untuk UID 7 byte, gunakan `uint64_t`
> (di atas) agar tidak overflow; pastikan juga formatnya sama dengan data master.

---

## 5. Checklist hosting

1. **HTTPS wajib** — API key dikirim di header; jangan lewat HTTP biasa.
2. Buat perangkat di **Pengaturan → API Perangkat**, salin kuncinya ke firmware.
   Kunci induk `RFID_API_KEY` di `.env` bersifat opsional (untuk uji cepat).
3. `APP_TIMEZONE=Asia/Jakarta` agar jam absensi benar.
4. Jalankan queue worker untuk notifikasi WhatsApp:
   ```bash
   php artisan queue:work --tries=1
   ```
   (atur lewat Supervisor / cron `queue:work --stop-when-empty`).
5. Batasi akses `/api/*` per IP perangkat lewat firewall/Nginx bila memungkinkan.
6. Naikkan `RFID_THROTTLE_PER_MINUTE` bila banyak reader berada di balik satu IP NAT.
7. Pastikan `storage/` dan `bootstrap/cache/` writable.

Contoh aturan Nginx (opsional, hanya izinkan IP reader):

```nginx
location /api/ {
    allow 203.0.113.10;   # IP sekolah
    deny all;
    try_files $uri /index.php?$query_string;
}
```

---

## 6. Uji cepat

```bash
KEY="KUNCI_ANDA"
BASE="https://domain-anda.com"

# 1. cek koneksi
curl -s -H "X-API-Key: $KEY" "$BASE/api/ping"

# 2. tap kartu
curl -s -X POST "$BASE/api/tap" -H "X-API-Key: $KEY" -d "tag=2234749610"

# 3. kartu tidak dikenal
curl -s -X POST "$BASE/api/tap" -H "X-API-Key: $KEY" -d "tag=999999"

# 4. tanpa key (harus 401)
curl -s -o /dev/null -w "%{http_code}\n" -X POST "$BASE/api/tap" -d "tag=2234749610"
```

Contoh firmware lengkap: [`docs/esp32_tap/esp32_tap.ino`](esp32_tap/esp32_tap.ino).
