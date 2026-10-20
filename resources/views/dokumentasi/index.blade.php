@extends('layouts.admin')
@section('title', 'Dokumentasi')
@section('content')
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs font-semibold mb-5">
    @foreach ([['dashboard', 'Dashboard'], ['siswa', 'Siswa'], ['kartu-pelajar', 'Kartu Pelajar'], ['kelas', 'Kelas'], ['laporan', 'Laporan'], ['whatsapp', 'Whatsapp'], ['pengaturan', 'Pengaturan']] as [$v, $label])
    <a href="{{ route('dokumentasi.index', ['tab' => $v]) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ $tab === $v ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">{{ $label }}</a>
    @endforeach
  </div>

  <div class="max-w-3xl flex flex-col gap-4 text-sm text-slate-300">

  @if ($tab === 'dashboard')
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Menu Dashboard</h2>
      <p class="text-[11px] text-slate-500">Pusat pantauan kehadiran hari ini dan pencatatan tap manual.</p>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Fungsi</h3>
      <ul class="list-disc pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Kartu statistik</span> — Total Siswa, Siswa Masuk, Sudah Pulang, Terlambat, dan Belum Absen untuk hari berjalan.</li>
        <li><span class="text-white font-medium">Rekap Hari Ini</span> — tabel seluruh siswa beserta jam masuk/pulang dan statusnya. Ada filter tab (Semua/Masuk/Pulang/Terlambat/Belum), pencarian Nama/NISN/RFID, filter kelas, dan pagination. Data diperbarui otomatis tiap 5 detik tanpa reload.</li>
        <li><span class="text-white font-medium">Catat Masuk/Pulang</span> — form tap manual: ketik/tempel RFID atau NISN lalu Enter. Cara kerja sama persis dengan kios: tap pertama = Masuk, tap kedua = Pulang.</li>
        <li><span class="text-white font-medium">Tap Terakhir</span> — 8 tap terbaru hari ini.</li>
        <li><span class="text-white font-medium">Siswa per Kelas</span> — jumlah siswa tiap kelas.</li>
      </ul>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Cara penggunaan</h3>
      <ol class="list-decimal pl-5 flex flex-col gap-1.5 text-[13px]">
        <li>Buka menu <span class="text-white font-medium">Dashboard</span> setiap pagi untuk memastikan angka kehadiran berjalan.</li>
        <li>Untuk mencari siswa di rekap, ketik nama/NISN/RFID di kolom pencarian atau pilih kelas pada filter kelas.</li>
        <li>Untuk mencatat manual (misal kartu siswa tertinggal), fokuskan kursor ke kolom tap, ketik RFID/NISN, tekan Enter. Hasilnya muncul sebagai notifikasi hijau (MASUK/PULANG) atau merah bila ditolak.</li>
        <li>Input tap terkunci otomatis di luar jam absensi (lihat Pengaturan → Waktu Absensi).</li>
      </ol>
    </div>
    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 text-[13px]">
      <p class="font-semibold text-white mb-1.5">Aturan tap yang perlu diketahui</p>
      <ul class="list-disc pl-5 flex flex-col gap-1">
        <li>Tap pertama hari itu = <span class="text-emerald-300">Masuk</span> (status Tepat Waktu atau Terlambat sesuai jam).</li>
        <li>Tap kedua = <span class="text-indigo-300">Pulang</span>, hanya diterima pada rentang jam pulang.</li>
        <li>Tap pulang sebelum ada jam masuk akan ditolak; tap ketiga dan seterusnya juga ditolak.</li>
        <li>Kartu/NISN yang tidak terdaftar menampilkan pesan "tidak terdaftar".</li>
      </ul>
    </div>

  @elseif ($tab === 'siswa')
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Menu Siswa</h2>
      <p class="text-[11px] text-slate-500">Kelola data siswa: tambah, ubah, hapus, dan import massal.</p>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Fungsi</h3>
      <ul class="list-disc pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Tabel siswa</span> — kolom Nama, NISN/RFID, TTL/Tahun Masuk, Kelas, No HP Ortu/Wali, dan Gender. Mendukung pencarian dan pagination 10 baris per halaman.</li>
        <li><span class="text-white font-medium">Tambah Siswa</span> — form NISN (angka, maks 10 digit), RFID Tag (dicek duplikat otomatis saat mengetik), Nama, Tempat Lahir, Tanggal Lahir, Tahun Masuk (opsional), Pas Foto 3x4 (opsional, jpg/png/webp maks 300 Kb otomatis jadi WebP di <span class="font-mono">storage/app/public/foto</span>), Kelas, Gender, dan Nomor HP Orang Tua/Wali (opsional, dipakai untuk notifikasi WhatsApp absensi).</li>
        <li><span class="text-white font-medium">Template .xlsx</span> — unduh contoh file import yang benar.</li>
        <li><span class="text-white font-medium">Import .xlsx</span> — tambah/update ratusan siswa sekaligus. NISN yang sudah ada = data di-update, NISN baru = ditambah.</li>
        <li><span class="text-white font-medium">Edit/Hapus</span> — ubah data atau hapus siswa (menghapus juga seluruh riwayat absensinya).</li>
      </ul>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Cara penggunaan</h3>
      <ol class="list-decimal pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Tambah satu siswa:</span> klik + Tambah Siswa → isi semua field → wajib pilih Kelas (buat dulu di menu Kelas bila kosong) → Simpan.</li>
        <li><span class="text-white font-medium">Import massal:</span> klik Template .xlsx → isi baris sesuai contoh dengan kolom <span class="font-mono text-slate-200">NISN | RFID_TAG | NAMA | KELAS | GENDER (L/P) | NO_HP_ORTU | TEMPAT_LAHIR | TANGGAL_LAHIR | TAHUN_MASUK</span> → upload lewat form Import .xlsx → periksa ringkasan "ok/gagal" dan daftar baris yang gagal.</li>
        <li><span class="text-white font-medium">Kelas baru saat import:</span> nama kelas yang belum ada otomatis dibuatkan.</li>
        <li><span class="text-white font-medium">No HP ortu:</span> isi dengan format 08xx atau 628xx (contoh 6281234567890). Boleh dikosongkan, tetapi siswa tersebut tidak akan menerima notifikasi WhatsApp.</li>
      </ol>
    </div>
    <div class="bg-amber-500/10 border border-amber-500/40 rounded-xl p-4 text-[13px]">
      <p class="font-semibold text-amber-200 mb-1.5">Aturan validasi import</p>
      <ul class="list-disc pl-5 flex flex-col gap-1 text-amber-100/90">
        <li>NISN wajib angka maks 10 digit; RFID tidak boleh dipakai siswa lain.</li>
        <li>Gender hanya L atau P; Tempat/Tanggal Lahir & Tahun Masuk opsional (Tanggal format YYYY-MM-DD, Tahun 2000-2100); baris tak lengkap ditolak dengan keterangan nomor barisnya.</li>
      </ul>
    </div>

  @elseif ($tab === 'kartu-pelajar')
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Menu Kartu Pelajar</h2>
      <p class="text-[11px] text-slate-500">Pilih siswa lalu cetak kartu pelajar ukuran KTP di kertas A4.</p>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Fungsi</h3>
      <ul class="list-disc pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Pilih siswa</span> — cari nama/NISN, saring per kelas, centang satu/bulk, atau klik Cetak per baris.</li>
        <li><span class="text-white font-medium">Pas foto 3x4</span> — upload lewat tombol 📷 Foto di menu ini atau form Tambah/Edit Siswa (jpg/png/webp maks 300 Kb, otomatis dikonversi ke WebP agar kecil). File disimpan di <span class="font-mono">storage/app/public/foto</span>; aktifkan <span class="text-white font-medium">Symlink Storage</span> di Pengaturan → Perawatan agar foto terbaca browser. Foto lama yang masih base64 tetap tampil. Kosong = tampil inisial nama.</li>
        <li><span class="text-white font-medium">Halaman cetak</span> — terbuka di tab baru, 1 siswa = 1 lembar A4 berisi kartu <span class="text-white font-medium">Depan di kiri</span> dan <span class="text-white font-medium">Belakang di kanan</span>, ukuran KTP 85.6 × 53.98 mm dengan sudut lengkung.</li>
        <li><span class="text-white font-medium">Isi kartu</span> — Depan (kop atas): judul Kartu Pelajar, nama + alamat institusi, lalu pas foto 3x4 di kiri dan Nama, Tempat/Tanggal Lahir, NISN, Tahun Masuk. Belakang: tata tertib pemegang kartu + tanda tangan Kepala Sekolah (nama & NIP dari Pengaturan → Institusi).</li>
        <li><span class="text-white font-medium">Unduh gambar</span> — pilih sisi Depan / Belakang / Depan + Belakang lalu Unduh Gambar (atau ⬇ PNG per baris). 1 gambar langsung terunduh sebagai PNG, banyak gambar dibungkus otomatis dalam 1 file ZIP.</li>
      </ul>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Cara penggunaan</h3>
      <ol class="list-decimal pl-5 flex flex-col gap-1.5 text-[13px]">
        <li>Pastikan <span class="text-white font-medium">Pengaturan → Institusi</span> (logo, nama, alamat) sudah terisi.</li>
        <li>Buka <span class="text-white font-medium">Kartu Pelajar</span> → lengkapi pas foto bila perlu → centang siswa → Cetak Terpilih (atau Cetak per baris).</li>
        <li>Di halaman cetak klik <span class="text-white font-medium">Cetak / Simpan PDF</span> → pilih kertas A4, margin default, aktifkan background graphics agar warna kartu ikut tercetak.</li>
      </ol>
    </div>

  @elseif ($tab === 'kelas')
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Menu Kelas</h2>
      <p class="text-[11px] text-slate-500">Kelola daftar kelas yang dipakai di menu Siswa dan Laporan.</p>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Fungsi</h3>
      <ul class="list-disc pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Tambah Kelas</span> — cukup ketik nama (contoh XII RPL 1, maks 30 karakter).</li>
        <li><span class="text-white font-medium">Cari, Edit, Hapus</span> — edit lewat dialog, hapus dengan konfirmasi.</li>
        <li><span class="text-white font-medium">Kolom Jml Siswa</span> — melihat berapa siswa di tiap kelas.</li>
      </ul>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Cara penggunaan</h3>
      <ol class="list-decimal pl-5 flex flex-col gap-1.5 text-[13px]">
        <li>Buat semua kelas terlebih dahulu <span class="text-white font-medium">sebelum</span> mengisi data siswa (menu Siswa terkunci bila belum ada kelas).</li>
        <li>Kelas yang masih dipakai siswa <span class="text-white font-medium">tidak bisa dihapus</span> — pindahkan/hapus siswanya dulu.</li>
      </ol>
    </div>

  @elseif ($tab === 'laporan')
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Menu Laporan</h2>
      <p class="text-[11px] text-slate-500">Lihat, koreksi, tandai, dan unduh rekap absensi.</p>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Fungsi</h3>
      <ul class="list-disc pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Mode Harian/Periode/Bulanan</span> — pilih rentang tanggal yang ingin ditampilkan.</li>
        <li><span class="text-white font-medium">Ekspor</span> — unduh laporan ke Excel (.xlsx) atau PDF, orientasi Portrait/Landscape.</li>
        <li><span class="text-white font-medium">Edit baris</span> — koreksi jam/status satu baris absensi (tombol aksi tiap baris).</li>
        <li><span class="text-white font-medium">Tandai</span> — beri status manual (Di Sekolah/Izin/Sakit/Tanpa Keterangan) pada siswa yang belum punya baris absensi di tanggal tersebut.</li>
        <li><span class="text-white font-medium">Panel Belum Absen</span> (mode harian) — daftar siswa yang belum tercatat, bisa langsung ditandai dari situ.</li>
      </ul>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Cara penggunaan</h3>
      <ol class="list-decimal pl-5 flex flex-col gap-1.5 text-[13px]">
        <li>Pilih mode (Harian/Periode/Bulanan) → isi tanggal → klik Tampil.</li>
        <li>Untuk mengunduh: pilih format Excel/PDF dan orientasi → klik Unduh.</li>
        <li>Untuk mengoreksi: klik ikon edit pada baris → ubah jam/status → simpan.</li>
        <li>Untuk siswa izin/sakit: cari di panel Belum Absen → pilih status → klik Tandai.</li>
      </ol>
    </div>

  @elseif ($tab === 'whatsapp')
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Menu Whatsapp</h2>
      <p class="text-[11px] text-slate-500">Konfigurasi gateway, uji kirim, dan pantau notifikasi absensi ke orang tua/wali.</p>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Fungsi tiap tab</h3>
      <ul class="list-disc pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Konfigurasi</span> — simpan 3 parameter API:
          <span class="font-mono text-slate-200">URL</span> (endpoint gateway, misal <span class="font-mono text-slate-200">https://wa.billnesia.com/send-message</span>),
          <span class="font-mono text-slate-200">API Key</span>, dan
          <span class="font-mono text-slate-200">Number</span> (nomor pengirim milik sekolah).</li>
        <li><span class="text-white font-medium">Uji Kirim</span> — form Nomor Tujuan + Pesan untuk mengetes API. Hasilnya tampil sebagai kartu <span class="text-emerald-300 font-medium">BERHASIL</span>/<span class="text-rose-300 font-medium">GAGAL</span> lengkap dengan kode HTTP dan isi respons API.</li>
        <li><span class="text-white font-medium">Log Notifikasi</span> — riwayat setiap notifikasi absensi: waktu, siswa, tipe (MASUK/PULANG), nomor tujuan, status (Antre/Terkirim/Gagal), kode HTTP, dan respons.</li>
      </ul>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Cara kerja notifikasi absensi</h3>
      <ol class="list-decimal pl-5 flex flex-col gap-1.5 text-[13px]">
        <li>Siswa tap kartu (Masuk/Pulang) dan absensi berhasil direkam.</li>
        <li>Sistem membuat baris log berstatus <span class="text-amber-300 font-medium">Antre</span> berisi pesan otomatis (nama, NISN, kelas, tanggal, jam, status) untuk nomor HP ortu/wali siswa tersebut. Nomor dinormalisasi otomatis (08xx → 628xx).</li>
        <li>Job pengiriman dijadwalkan <span class="text-white font-medium">10 detik</span> setelah absensi. Bila banyak absensi menumpuk, tiap notifikasi diberi jeda <span class="text-white font-medium">10 detik dari notifikasi sebelumnya</span> (notifikasi ke-1 = 10 dtk, ke-2 = 20 dtk, dst).</li>
        <li>Worker mengeksekusi job: kirim POST form-urlencoded (<span class="font-mono text-slate-200">nomor_pengirim, api_key, nomor_penerima, pesan</span>) ke URL gateway, lalu update log menjadi <span class="text-emerald-300 font-medium">Terkirim</span> atau <span class="text-rose-300 font-medium">Gagal</span> beserta HTTP dan responsnya. Gagal koneksi dicoba ulang 3x.</li>
        <li>Siswa tanpa nomor HP ortu/wali langsung dicatat <span class="text-rose-300 font-medium">Gagal</span> dengan keterangan "Nomor HP ortu/wali kosong atau tidak valid" (tidak mengantre).</li>
      </ol>
    </div>
    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 text-[13px]">
      <p class="font-semibold text-white mb-1.5">Pengaturan di local (laptop/komputer sekolah)</p>
      <ol class="list-decimal pl-5 flex flex-col gap-1">
        <li>Isi tab Konfigurasi (URL, API Key, Number) → Simpan.</li>
        <li>Buka satu terminal di folder proyek, jalankan: <span class="font-mono text-slate-200">php artisan queue:work</span> dan biarkan berjalan selama aplikasi dipakai. Perintah inilah yang mengeksekusi antrean tiap ada job jatuh tempo.</li>
        <li>Tidak perlu cron job di local — cukup terminal worker tersebut. Untuk memproses manual sesekali, bisa juga klik <span class="text-white font-medium">Pengaturan → Perawatan → Jalankan Antrean</span>.</li>
      </ol>
    </div>
    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 text-[13px]">
      <p class="font-semibold text-white mb-1.5">Pengaturan di hosting</p>
      <ol class="list-decimal pl-5 flex flex-col gap-1">
        <li>Isi tab Konfigurasi seperti di local.</li>
        <li><span class="text-white font-medium">Ya, perlu cron job</span> — karena tombol Perawatan dan perintah manual hanya berjalan saat diklik/dijalankan, sedangkan notifikasi harus terkirim otomatis walau tidak ada yang membuka web. Daftarkan cron tiap menit di panel hosting (Cron Jobs):
          <span class="font-mono text-slate-200 break-all">* * * * * cd /home/username/public_html && php artisan queue:work --stop-when-empty --max-time=50 &gt;/dev/null 2&gt;&amp;1</span>
          (sesuaikan path folder proyek). Cron ini tiap menit memproses semua job yang sudah jatuh tempo lalu berhenti sendiri.</li>
        <li>Alternatif di VPS (bukan shared hosting): jalankan worker kontinu memakai Supervisor/Managed process: <span class="font-mono text-slate-200">php artisan queue:work --sleep=3 --tries=3</span> — maka cron tidak diperlukan.</li>
        <li>Khusus shared hosting yang membatasi eksekusi lama: cron per menit di atas adalah cara yang disarankan; pastikan limit eksekusi PHP ≥ 60 detik.</li>
      </ol>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
      <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-2.5 px-4">Lingkungan</th><th class="py-2.5 px-4">Perlu cron?</th><th class="py-2.5 px-4">Cara eksekusi antrean</th></tr></thead>
      <tbody class="divide-y divide-slate-700/60">
        <tr><td class="py-2.5 px-4 text-white font-medium">Local</td><td class="py-2.5 px-4 text-slate-400">Tidak</td><td class="py-2.5 px-4 font-mono">php artisan queue:work (terminal dibiarkan jalan)</td></tr>
        <tr><td class="py-2.5 px-4 text-white font-medium">Hosting (shared)</td><td class="py-2.5 px-4 text-emerald-300 font-medium">Ya, tiap menit</td><td class="py-2.5 px-4 font-mono">cron → queue:work --stop-when-empty --max-time=50</td></tr>
        <tr><td class="py-2.5 px-4 text-white font-medium">VPS</td><td class="py-2.5 px-4 text-slate-400">Opsional</td><td class="py-2.5 px-4 font-mono">Supervisor → queue:work kontinu</td></tr>
      </tbody>
    </table></div>

  @elseif ($tab === 'pengaturan')
    <div>
      <h2 class="text-base font-semibold text-white mb-1">Menu Pengaturan</h2>
      <p class="text-[11px] text-slate-500">Konfigurasi identitas, jam, update, pemeliharaan, dan backup aplikasi.</p>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Fungsi tiap tab</h3>
      <ul class="list-disc pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Institusi</span> — Nama, Alamat, No HP, Nama & NIP Kepala Sekolah, Logo, TTD Kepala Sekolah, dan Stempel sekolah (TTD & stempel diupload base64 dengan preview). Nama tampil di sidebar dan laporan; logo tampil di sidebar dan favicon browser; nama, NIP, TTD & stempel tampil sebagai tanda tangan di belakang kartu pelajar.</li>
        <li><span class="text-white font-medium">Waktu Absensi</span> — 6 batas jam (Masuk Mulai/Selesai, Terlambat Mulai/Selesai, Pulang Mulai/Selesai, format HH:MM). Menentukan status Tepat Waktu/Terlambat, kapan tap Pulang diterima, dan kapan input dikunci.</li>
        <li><span class="text-white font-medium">Update GitHub</span> — simpan Repository (owner/repo), Branch, dan Token, lalu klik Perbarui Sekarang untuk menarik kode terbaru dari GitHub (git fetch + reset). Token tidak disimpan di log maupun config git.</li>
        <li><span class="text-white font-medium">Perawatan</span> — Bersihkan Cache, Migrasi DB, Symlink Storage (membuat / memperbaiki link <span class="font-mono">public/storage → storage/app/public</span> agar foto siswa bisa dibaca browser; status symlink tampil di panel), dan <span class="text-white font-medium">Jalankan Antrean</span> (memproses jobs WhatsApp yang tertunda, berhenti saat kosong, maks 55 detik). Panel statistik menampilkan jumlah jobs antre, jobs gagal, dan notifikasi antre.</li>
        <li><span class="text-white font-medium">Backup/Import</span> — Unduh seluruh database sebagai .sql, atau pulihkan dari file .sql (maks 10 MB).</li>
      </ul>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Cara penggunaan</h3>
      <ol class="list-decimal pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Awal setup:</span> isi Institusi → atur Waktu Absensi → isi Konfigurasi WhatsApp → buat Kelas → import/tambah Siswa.</li>
        <li><span class="text-white font-medium">Rutin:</span> pakai Backup tiap minggu sebelum perubahan besar; pakai Jalankan Antrean bila notifikasi menumpuk dan worker tidak jalan.</li>
        <li><span class="text-white font-medium">Update aplikasi:</span> pastikan repo/branch/token benar → Perbarui Sekarang → baca log hasilnya di halaman yang sama.</li>
      </ol>
    </div>
    <div class="bg-rose-500/10 border border-rose-500/40 rounded-xl p-4 text-[13px]">
      <p class="font-semibold text-rose-200 mb-1.5">Perhatian</p>
      <ul class="list-disc pl-5 flex flex-col gap-1 text-rose-100/90">
        <li>Update GitHub menimpa file lokal (reset --hard) — jangan ubah kode langsung di server produksi.</li>
        <li>Import database menimpa data — selalu unduh backup dulu.</li>
      </ul>
    </div>
  @endif

  </div>
</div>
@endsection
