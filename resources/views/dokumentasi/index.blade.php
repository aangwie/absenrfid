@extends('layouts.admin')
@section('title', 'Dokumentasi')
@section('content')
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs font-semibold mb-5">
    @foreach ([['dashboard', 'Dashboard'], ['siswa', 'Siswa'], ['kartu-pelajar', 'Kartu Pelajar'], ['kelas', 'Kelas'], ['laporan', 'Laporan'], ['whatsapp', 'Whatsapp'], ['pengaturan', 'Pengaturan'], ['api', 'API RFID']] as [$v, $label])
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
        <li>Hanya kode RFID yang diterima; mengetik NISN akan ditolak. Kartu yang tidak terdaftar menampilkan pesan "tidak terdaftar".</li>
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
        <li><span class="text-white font-medium">Pas foto 3x4</span> — upload lewat tombol 📷 Foto di menu ini atau form Tambah/Edit Siswa (jpg/png/webp maks 300 Kb, otomatis dikonversi ke WebP agar kecil). File disimpan di <span class="font-mono">storage/app/public/foto</span> dan ditampilkan lewat route <span class="font-mono">/media/foto/…</span> (tidak butuh symlink). Foto lama yang masih base64 tetap tampil. Kosong = tampil inisial nama.</li>
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
      <p class="text-[11px] text-slate-500">Konfigurasi gateway, template pesan, uji kirim, dan pantau notifikasi absensi ke orang tua/wali.</p>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Fungsi tiap tab</h3>
      <ul class="list-disc pl-5 flex flex-col gap-1.5 text-[13px]">
        <li><span class="text-white font-medium">Konfigurasi</span> — simpan parameter API:
          <span class="font-mono text-slate-200">URL</span> (endpoint gateway, misal <span class="font-mono text-slate-200">https://wa.billnesia.com/send-message</span>),
          <span class="font-mono text-slate-200">API Key</span>, dan
          <span class="font-mono text-slate-200">Number</span> (nomor pengirim milik sekolah). Ada juga centang <span class="text-white font-medium">status pesan yang dikirim</span> (Tepat Waktu, Terlambat, Pulang) serta <span class="text-white font-medium">Durasi</span> dan <span class="text-white font-medium">Jumlah pesan per durasi</span> untuk mengatur kecepatan kirim.</li>
        <li><span class="text-white font-medium">Antrean</span> — daftar pesan yang sudah dijadwalkan tetapi belum terkirim (jadwal kirim, siswa, tipe, keterangan, tujuan, isi pesan). Setiap baris punya tombol <span class="text-rose-300 font-medium">Batalkan</span> selama pesannya belum terkirim.</li>
        <li><span class="text-white font-medium">Uji Kirim</span> — pilih tujuan lewat tombol <span class="text-white font-medium">Nomor Tertentu</span> (ketik satu nomor) atau <span class="text-white font-medium">Nomor Siswa Terdaftar</span> (multi select yang bisa dicari berdasarkan nama, NISN, atau nomor HP). Pesan bisa diisi dari template. Placeholder seperti <span class="font-mono text-slate-200">{nama}</span>, <span class="font-mono text-slate-200">{nisn}</span>, <span class="font-mono text-slate-200">{kelas}</span> otomatis diganti saat mengirim ke siswa terdaftar. Hasil tiap tujuan tampil lengkap dengan kode HTTP dan isi respons API.</li>
        <li><span class="text-white font-medium">Template</span> — kumpulan template pesan WhatsApp ke siswa/orang tua. Template aktif per kategori (Tepat Waktu, Terlambat, Pulang, Tandai Manual, Umum) dipakai otomatis saat menyusun notifikasi. Template bisa ditambah, diedit, dihapus, dan diaktifkan/nonaktifkan. Bila tidak ada template aktif, sistem memakai teks bawaan.</li>
        <li><span class="text-white font-medium">Log Notifikasi</span> — riwayat setiap notifikasi absensi: waktu, jadwal, siswa, tipe (MASUK/PULANG), nomor tujuan, status (Antre/Terkirim/Gagal/Dibatalkan), kode HTTP, dan respons.</li>
      </ul>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-white mb-2">Cara kerja notifikasi absensi</h3>
      <ol class="list-decimal pl-5 flex flex-col gap-1.5 text-[13px]">
        <li>Siswa tap kartu (Masuk/Pulang) dan absensi berhasil direkam.</li>
        <li>Sistem membuat baris log berstatus <span class="text-amber-300 font-medium">Antre</span> berisi pesan otomatis dari <span class="text-white font-medium">template aktif</span> (nama, NISN, kelas, tanggal, jam, status) untuk nomor HP ortu/wali siswa tersebut. Nomor dinormalisasi otomatis (08xx → 628xx).</li>
        <li>Hanya notifikasi dengan status yang <span class="text-white font-medium">dicentang di tab Konfigurasi</span> (Tepat Waktu, Terlambat, Pulang) yang dibuatkan antrean; status yang tidak dicentang tidak dikirim. Notifikasi dari aksi Tandai manual (Izin, Sakit, Tanpa Keterangan) selalu dikirim karena merupakan tindakan admin.</li>
        <li>Semua pesan terpilih dikirim bertahap mengikuti pengaturan durasi/jumlah. Contoh: 10 menit / 10 pesan = 1 pesan tiap 60 detik; 90 pesan selesai dalam ±90 menit. Saat status dinonaktifkan, antrean lamanya otomatis dibatalkan. Tujuannya agar nomor WhatsApp tidak diblokir karena mengirim ratusan pesan sekaligus.</li>
        <li>Worker mengeksekusi job sesuai <span class="font-mono text-slate-200">jadwal_kirim</span>: kirim POST form-urlencoded (<span class="font-mono text-slate-200">nomor_pengirim, api_key, nomor_penerima, pesan</span>) ke URL gateway, lalu update log menjadi <span class="text-emerald-300 font-medium">Terkirim</span> atau <span class="text-rose-300 font-medium">Gagal</span> beserta HTTP dan responsnya. Pesan yang gagal tidak dikirim ulang otomatis agar tidak terjadi pesan ganda.</li>
        <li>Admin bisa membatalkan pesan yang belum terkirim dari tab <span class="text-white font-medium">Antrean</span>; statusnya berubah menjadi <span class="text-slate-300 font-medium">Dibatalkan</span> dan tidak akan dikirim.</li>
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
        <li><span class="text-white font-medium">Perawatan</span> — Bersihkan Cache, Migrasi DB, Symlink Storage (opsional: membuat / memperbaiki link <span class="font-mono">public/storage → storage/app/public</span>; status symlink tampil di panel), dan <span class="text-white font-medium">Jalankan Antrean</span> (memproses jobs WhatsApp yang tertunda, berhenti saat kosong, maks 55 detik). Panel statistik menampilkan jumlah jobs antre, jobs gagal, dan notifikasi antre.</li>
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

  @elseif ($tab === 'api')
    <div>
      <h2 class="text-base font-semibold text-white mb-1">API RFID (ESP32 + MFRC522)</h2>
      <p class="text-[11px] text-slate-500">Endpoint JSON untuk perangkat reader. Aturan absensinya sama persis dengan kios web: tap pertama Masuk, tap berikutnya Pulang, di luar jam ditolak.</p>
    </div>

    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 text-[13px]">
      <p class="font-semibold text-white mb-1.5">Alamat &amp; autentikasi</p>
      <ul class="list-disc pl-5 flex flex-col gap-1">
        <li>Base URL: <span class="font-mono text-slate-200">https://domain-anda.com/api</span></li>
        <li>Setiap request wajib mengirim header <span class="font-mono text-slate-200">X-API-Key</span>. Kunci dibuat di <span class="text-white font-medium">Pengaturan → API Perangkat</span>.</li>
        <li>Kunci induk dari <span class="font-mono text-slate-200">.env</span> (<span class="font-mono text-slate-200">RFID_API_KEY</span>) juga diterima, cocok untuk uji cepat.</li>
        <li>Tidak memakai sesi, cookie, maupun CSRF — aman untuk firmware.</li>
        <li>Hasil bisnis selalu HTTP <span class="font-mono text-slate-200">200</span>; hanya <span class="font-mono text-slate-200">401</span> (kunci salah), <span class="font-mono text-slate-200">403</span> (perangkat nonaktif), <span class="font-mono text-slate-200">429</span> (terlalu sering), dan <span class="font-mono text-slate-200">503</span> (kunci server kosong) yang berbeda.</li>
      </ul>
    </div>

    <div>
      <h3 class="text-sm font-semibold text-white mb-2">1. Endpoint</h3>
      <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
        <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-2.5 px-4">Method</th><th class="py-2.5 px-4">Path</th><th class="py-2.5 px-4">Fungsi</th></tr></thead>
        <tbody class="divide-y divide-slate-700/60">
          <tr><td class="py-2.5 px-4 font-mono text-emerald-300">POST</td><td class="py-2.5 px-4 font-mono text-slate-200">/api/tap</td><td class="py-2.5 px-4 text-slate-400">Catat tap, balas status siswa</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-sky-300">GET</td><td class="py-2.5 px-4 font-mono text-slate-200">/api/ping</td><td class="py-2.5 px-4 text-slate-400">Uji koneksi + kunci + jam server</td></tr>
        </tbody>
      </table></div>
    </div>

    <div>
      <h3 class="text-sm font-semibold text-white mb-2">2. Contoh request</h3>
      <p class="text-[13px] text-slate-400 mb-2">Parameter <span class="font-mono text-slate-200">tag</span> boleh berisi RFID atau NISN. Mendukung form-urlencoded (disarankan ESP32) dan JSON.</p>
      <pre class="bg-slate-950/70 border border-slate-700 rounded-xl p-3 overflow-x-auto text-[11px] font-mono text-slate-300">curl -X POST https://domain-anda.com/api/tap \
  -H "X-API-Key: KUNCI_PERANGKAT" \
  -H "Accept: application/json" \
  -d "tag=2234749610"</pre>
      <p class="text-[11px] text-slate-500 mt-2">Uji di server lokal: ganti domain dengan <span class="font-mono">http://127.0.0.1:8000</span>.</p>
    </div>

    <div>
      <h3 class="text-sm font-semibold text-white mb-2">3. Contoh respons sukses</h3>
      <pre class="bg-slate-950/70 border border-slate-700 rounded-xl p-3 overflow-x-auto text-[11px] font-mono text-emerald-200">{
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
}</pre>
      <p class="text-[11px] text-slate-500 mt-2">Saat gagal, <span class="font-mono">ok=false</span>, <span class="font-mono">siswa</span> dan <span class="font-mono">absensi</span> bisa <span class="font-mono">null</span>, dan <span class="font-mono">pesan</span> berisi alasan.</p>
    </div>

    <div>
      <h3 class="text-sm font-semibold text-white mb-2">4. Arti kode</h3>
      <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
        <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-2.5 px-4">kode</th><th class="py-2.5 px-4">ok</th><th class="py-2.5 px-4">Arti</th></tr></thead>
        <tbody class="divide-y divide-slate-700/60">
          <tr><td class="py-2.5 px-4 font-mono text-emerald-300">MASUK</td><td class="py-2.5 px-4 text-emerald-300">true</td><td class="py-2.5 px-4 text-slate-400">Tap masuk, tepat waktu</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-amber-300">TERLAMBAT</td><td class="py-2.5 px-4 text-emerald-300">true</td><td class="py-2.5 px-4 text-slate-400">Tap masuk, terlambat</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-indigo-300">PULANG</td><td class="py-2.5 px-4 text-emerald-300">true</td><td class="py-2.5 px-4 text-slate-400">Tap pulang berhasil</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-slate-300">SUDAH_ABSEN</td><td class="py-2.5 px-4 text-rose-300">false</td><td class="py-2.5 px-4 text-slate-400">Sudah tap masuk &amp; pulang</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-slate-300">SUDAH_MASUK</td><td class="py-2.5 px-4 text-rose-300">false</td><td class="py-2.5 px-4 text-slate-400">Sudah masuk, belum jam pulang</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-rose-300">PULANG_TANPA_MASUK</td><td class="py-2.5 px-4 text-rose-300">false</td><td class="py-2.5 px-4 text-slate-400">Tap pulang sebelum tap masuk</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-rose-300">DI_LUAR_JAM</td><td class="py-2.5 px-4 text-rose-300">false</td><td class="py-2.5 px-4 text-slate-400">Di luar semua rentang jam absensi</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-rose-300">TAG_TIDAK_DIKENAL</td><td class="py-2.5 px-4 text-rose-300">false</td><td class="py-2.5 px-4 text-slate-400">RFID tidak terdaftar</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-rose-300">NISN_DITOLAK</td><td class="py-2.5 px-4 text-rose-300">false</td><td class="py-2.5 px-4 text-slate-400">Tag berisi NISN, bukan kode RFID</td></tr>
          <tr><td class="py-2.5 px-4 font-mono text-rose-300">GAGAL</td><td class="py-2.5 px-4 text-rose-300">false</td><td class="py-2.5 px-4 text-slate-400">Tag kosong / error lain</td></tr>
        </tbody>
      </table></div>
    </div>

    <div>
      <h3 class="text-sm font-semibold text-white mb-2">5. Setup ESP32 + MFRC522</h3>
      <ol class="list-decimal pl-5 flex flex-col gap-1.5 text-[13px]">
        <li>Pasang library lewat Arduino IDE → Library Manager: <span class="font-mono text-slate-200">MFRC522 by GithubCommunity</span> dan <span class="font-mono text-slate-200">ArduinoJson v7</span>.</li>
        <li>Wiring (MFRC522 <span class="text-rose-300 font-medium">wajib 3.3V</span>): SS→5, SCK→18, MOSI→23, MISO→19, RST→22, 3.3V→3.3V, GND→GND.</li>
        <li>Buka <span class="font-mono text-slate-200">docs/esp32_tap/esp32_tap.ino</span>, isi <span class="font-mono text-slate-200">WIFI_SSID</span>, <span class="font-mono text-slate-200">WIFI_PASS</span>, <span class="font-mono text-slate-200">API_BASE_URL</span>, dan <span class="font-mono text-slate-200">API_KEY</span> (dari tab Pengaturan → API Perangkat).</li>
        <li>Upload. Buka Serial Monitor 115200 untuk melihat UID, HTTP code, dan respons JSON tiap tap.</li>
        <li>Firmware sudah memakai <span class="text-white font-medium">debounce 3 detik</span> agar satu kartu tidak terkirim berulang, plus auto-reconnect WiFi.</li>
      </ol>
    </div>

    <div>
      <h3 class="text-sm font-semibold text-white mb-2">6. Contoh kode ESP32 (Arduino IDE)</h3>
      <div class="bg-amber-500/10 border border-amber-500/40 rounded-xl p-4 text-[13px] mb-3">
        <p class="font-semibold text-amber-200 mb-1.5">Cara memasang API key</p>
        <ol class="list-decimal pl-5 flex flex-col gap-1 text-amber-100/90">
          <li>Buka <span class="text-white font-medium">Pengaturan → API Perangkat</span>, tambahkan reader, lalu salin kunci 48 karakter yang tampil.</li>
          <li>Di kode di bawah, tempel kunci tersebut pada baris
            <span class="font-mono text-amber-200">const char* API_KEY = "...";</span></li>
          <li>Isi juga <span class="font-mono text-amber-200">WIFI_SSID</span>, <span class="font-mono text-amber-200">WIFI_PASS</span>, dan <span class="font-mono text-amber-200">API_BASE_URL</span> (domain hosting, tanpa garis miring di akhir).</li>
          <li>Upload, lalu buka Serial Monitor 115200. Setiap tap akan menampilkan UID, HTTP code, dan JSON hasil dari server.</li>
        </ol>
      </div>

      <div class="flex items-center justify-between gap-3 mb-2">
        <p class="text-[11px] text-slate-500">Kode lengkap siap tempel (library: <span class="font-mono">MFRC522</span> + <span class="font-mono">ArduinoJson v7</span>).</p>
        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('kode-esp32').innerText).then(function(){this.textContent='Tersalin!'}.bind(this))" class="bg-slate-700 hover:bg-slate-600 text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg">Salin Kode</button>
      </div>
      <pre id="kode-esp32" class="bg-slate-950/80 border border-slate-700 rounded-xl p-4 overflow-x-auto text-[10.5px] leading-relaxed font-mono text-slate-200">#include &lt;WiFi.h&gt;
#include &lt;HTTPClient.h&gt;
#include &lt;WiFiClientSecure.h&gt;
#include &lt;SPI.h&gt;
#include &lt;MFRC522.h&gt;
#include &lt;ArduinoJson.h&gt;

// ================== KONFIGURASI ==================
const char* WIFI_SSID    = "NAMA_WIFI";
const char* WIFI_PASS    = "PASSWORD_WIFI";

// Domain hosting, TANPA garis miring di akhir.
// Lokal: "http://192.168.1.10:8000"
const char* API_BASE_URL = "https://domain-anda.com";

// >>> TEMPEL KUNCI DARI: Pengaturan -> API Perangkat <<<
const char* API_KEY      = "GANTI_DENGAN_KUNCI_PERANGKAT_ANDA";

// 1 = UID desimal (2234749610), 0 = UID hex (8532A6AA)
#define UID_FORMAT_DECIMAL 1

// Abaikan kartu sama dalam 3 detik agar tidak dobel kirim.
const unsigned long DEBOUNCE_MS = 3000;

// ------------------ PIN MFRC522 ------------------
#define SS_PIN  5
#define RST_PIN 22

MFRC522 rfid(SS_PIN, RST_PIN);
String lastUid = "";
unsigned long lastTap = 0;

void setup() {
  Serial.begin(115200);
  delay(300);

  SPI.begin();            // SCK=18, MISO=19, MOSI=23
  rfid.PCD_Init();

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Menghubungkan WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.reconnect();
    delay(1000);
    return;
  }

  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;

  String tag = uidKeString(rfid.uid.uidByte, rfid.uid.size);
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  if (tag == lastUid &amp;&amp; (millis() - lastTap) &lt; DEBOUNCE_MS) return;
  lastUid = tag;
  lastTap = millis();

  Serial.println("UID: " + tag);
  kirimTap(tag);
}

String uidKeString(byte* uid, byte len) {
#if UID_FORMAT_DECIMAL
  unsigned long long n = 0;
  for (byte i = 0; i &lt; len; i++) {
    n = (n &lt;&lt; 8) | uid[i];
  }
  char buf[24];
  snprintf(buf, sizeof(buf), "%llu", n);
  return String(buf);
#else
  String s = "";
  for (byte i = 0; i &lt; len; i++) {
    if (uid[i] &lt; 0x10) s += "0";
    s += String(uid[i], HEX);
  }
  s.toUpperCase();
  return s;
#endif
}

void kirimTap(const String&amp; tag) {
  String url = String(API_BASE_URL) + "/api/tap";
  HTTPClient http;

  // Objek client harus hidup sampai http.end().
  WiFiClientSecure secure;
  WiFiClient plain;

  if (url.startsWith("https")) {
    secure.setInsecure();     // produksi: ganti dengan setCACert()
    http.begin(secure, url);
  } else {
    http.begin(plain, url);
  }

  http.setTimeout(8000);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-API-Key", API_KEY);   // &lt;-- autentikasi perangkat

  int code = http.POST("tag=" + tag);
  String body = http.getString();
  http.end();

  Serial.printf("HTTP %d\n", code);
  Serial.println("Respons: " + body);

  if (code == 401) { Serial.println("API KEY SALAH / sudah diganti."); return; }
  if (code == 403) { Serial.println("Perangkat dinonaktifkan admin."); return; }
  if (code == 429) { Serial.println("Terlalu banyak request, tunggu 1 menit."); return; }
  if (code != 200) return;

  JsonDocument doc;
  if (deserializeJson(doc, body)) {
    Serial.println("JSON tidak valid.");
    return;
  }

  const char* kode   = doc["kode"]   | "?";
  const char* status = doc["status"] | "";
  const char* pesan  = doc["pesan"]  | "";

  Serial.println("========================================");
  Serial.printf("KODE   : %s\n", kode);
  Serial.printf("STATUS : %s\n", status);
  Serial.printf("PESAN  : %s\n", pesan);
  Serial.println("========================================");
}</pre>
      <p class="text-[11px] text-slate-500 mt-2">Versi lengkap dengan LED/buzzer + komentar wiring: <span class="font-mono text-slate-400">docs/esp32_tap/esp32_tap.ino</span></p>
    </div>

    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 text-[13px]">
      <p class="font-semibold text-white mb-1.5">Format UID kartu</p>
      <p class="mb-1">Server mencocokkan <span class="text-white font-medium">string</span> <span class="font-mono text-slate-200">rfid_tag</span> di database. Firmware contoh mengirim UID 4 byte sebagai desimal big-endian:</p>
      <pre class="bg-slate-950/70 border border-slate-700 rounded-lg p-2.5 overflow-x-auto text-[10px] font-mono text-slate-300">uint64_t uid = 0;
for (byte i = 0; i &lt; rfid.uid.size; i++) {
  uid = (uid &lt;&lt; 8) | rfid.uid.uidByte[i];
}</pre>
      <p class="mt-1.5">Bila kartu didaftarkan dalam hex, ubah <span class="font-mono text-slate-200">UID_FORMAT_DECIMAL</span> menjadi <span class="font-mono text-slate-200">0</span> di firmware — atau samakan formatnya saat input di menu Siswa.</p>
    </div>

    <div class="bg-slate-900/60 border border-slate-700 rounded-xl p-4 text-[13px]">
      <p class="font-semibold text-white mb-1.5">Checklist hosting</p>
      <ol class="list-decimal pl-5 flex flex-col gap-1">
        <li><span class="text-white font-medium">Wajib HTTPS</span> — kunci dikirim lewat header, jangan lewat HTTP biasa.</li>
        <li>Buat perangkat di <span class="text-white font-medium">Pengaturan → API Perangkat</span>, salin kuncinya ke firmware.</li>
        <li>Pastikan <span class="font-mono text-slate-200">APP_URL</span> dan <span class="font-mono text-slate-200">APP_TIMEZONE=Asia/Jakarta</span> benar, lalu <span class="font-mono text-slate-200">php artisan config:clear</span>.</li>
        <li>Jalankan queue worker/cron untuk notifikasi WhatsApp (lihat tab Whatsapp).</li>
        <li>Opsional: batasi <span class="font-mono text-slate-200">/api/</span> per IP reader di Nginx/firewall.</li>
        <li>Bila banyak reader di balik satu IP NAT, naikkan <span class="font-mono text-slate-200">RFID_THROTTLE_PER_MINUTE</span> di <span class="font-mono text-slate-200">.env</span>.</li>
      </ol>
    </div>

    <div class="bg-rose-500/10 border border-rose-500/40 rounded-xl p-4 text-[13px]">
      <p class="font-semibold text-rose-200 mb-1.5">Troubleshooting</p>
      <ul class="list-disc pl-5 flex flex-col gap-1 text-rose-100/90">
        <li><span class="font-mono">401 UNAUTHORIZED</span> — kunci salah/terhapus. Salin ulang dari Pengaturan → API Perangkat.</li>
        <li><span class="font-mono">403 PERANGKAT_NONAKTIF</span> — centang Aktif pada perangkat tersebut.</li>
        <li><span class="font-mono">429</span> — request terlalu sering; tunggu satu menit atau naikkan batas throttle.</li>
        <li><span class="font-mono">DI_LUAR_JAM</span> — jam server di luar rentang; cek Pengaturan → Waktu Absensi dan zona waktu.</li>
        <li><span class="font-mono">TAG_TIDAK_DIKENAL</span> — UID tidak cocok. Cek format desimal/hex dan data RFID di menu Siswa.</li>
        <li>Log tap terakhir bisa dilihat di <span class="text-white font-medium">Pengaturan → API Perangkat</span>.</li>
      </ul>
    </div>

    <div class="text-[11px] text-slate-500">
      Dokumentasi teknis lengkap: <span class="font-mono text-slate-400">docs/api-rfid.md</span> · Firmware: <span class="font-mono text-slate-400">docs/esp32_tap/esp32_tap.ino</span>
    </div>
  @endif

  </div>
</div>
@endsection
