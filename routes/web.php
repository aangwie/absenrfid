<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumentasiController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Route;

Route::post('/tap', [DashboardController::class, 'tap'])->name('tap');

Route::get('/', function () {
    \App\Support\WaktuAbsensi::tandaiTanpaKeterangan();
    $today = now()->toDateString();
    $siswas = \App\Models\Siswa::leftJoin('tb_absensi as a', function ($join) use ($today) {
            $join->on('a.nisn', '=', 'tb_siswa.nisn')->whereDate('a.tanggal', $today);
        })
        ->leftJoin('tb_kelas as k', 'k.id', '=', 'tb_siswa.kelas_id')
        ->select('tb_siswa.nisn', 'tb_siswa.rfid_tag', 'tb_siswa.nama', 'k.nama as kelas', 'tb_siswa.gender', 'a.jam_masuk', 'a.jam_pulang', 'a.status_masuk', 'a.status_pulang')
        ->orderBy('k.nama')->orderBy('tb_siswa.nama')->get();

    $rentang = \App\Support\WaktuAbsensi::rentang();
    $boleh = \App\Support\WaktuAbsensi::boleh(now()->format('H:i:s'));

    if (request()->wantsJson()) {
        return response()->json($siswas);
    }

    return view('home', compact('siswas', 'rentang', 'boleh'));
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/rekap', [DashboardController::class, 'rekap'])->name('dashboard.rekap');
    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/create', [SiswaController::class, 'create'])->name('siswa.create');
    Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
    Route::get('/siswa/cek-rfid', [SiswaController::class, 'cekRfid'])->name('siswa.cek-rfid');
    Route::get('/siswa/template', [SiswaController::class, 'template'])->name('siswa.template');
    Route::post('/siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
    Route::get('/siswa/{siswa}/edit', [SiswaController::class, 'edit'])->name('siswa.edit');
    Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
    Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');
    Route::get('/kelas', [KelasController::class, 'index'])->name('kelas.index');
    Route::post('/kelas', [KelasController::class, 'store'])->name('kelas.store');
    Route::put('/kelas/{kelas}', [KelasController::class, 'update'])->name('kelas.update');
    Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::post('/laporan/tandai', [LaporanController::class, 'tandai'])->name('laporan.tandai');
    Route::get('/laporan/ekspor', [LaporanController::class, 'ekspor'])->name('laporan.ekspor');
    Route::get('/laporan/{absen}/edit', [LaporanController::class, 'edit'])->name('laporan.edit');
    Route::put('/laporan/{absen}', [LaporanController::class, 'perbarui'])->name('laporan.perbarui');
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::post('/pengaturan/institusi', [PengaturanController::class, 'simpanInstitusi'])->name('pengaturan.institusi');
    Route::post('/pengaturan/waktu', [PengaturanController::class, 'simpanWaktu'])->name('pengaturan.waktu');
    Route::post('/pengaturan/update-config', [PengaturanController::class, 'simpanUpdateConfig'])->name('pengaturan.update-config');
    Route::post('/pengaturan/update', [PengaturanController::class, 'updateApp'])->name('pengaturan.update-app');
    Route::post('/pengaturan/rawat', [PengaturanController::class, 'rawat'])->name('pengaturan.rawat');
    Route::get('/pengaturan/backup', [PengaturanController::class, 'unduhBackup'])->name('pengaturan.backup');
    Route::post('/pengaturan/impor', [PengaturanController::class, 'imporDb'])->name('pengaturan.impor');
    Route::get('/whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp/simpan', [WhatsappController::class, 'simpan'])->name('whatsapp.simpan');
    Route::post('/whatsapp/uji', [WhatsappController::class, 'uji'])->name('whatsapp.uji');
    Route::post('/whatsapp/ping', [WhatsappController::class, 'ping'])->name('whatsapp.ping');
    Route::post('/whatsapp/cek-cron', [WhatsappController::class, 'cek'])->name('whatsapp.cek');
    Route::delete('/whatsapp/log', [WhatsappController::class, 'hapusLog'])->name('whatsapp.log-hapus');
    Route::get('/dokumentasi', [DokumentasiController::class, 'index'])->name('dokumentasi.index');
    Route::get('/profil', [ProfilController::class, 'show'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
