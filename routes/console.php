<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('absensi:tandai-alpha {tanggal?}', function (?string $tanggal = null) {
    $n = \App\Support\WaktuAbsensi::tandaiTanpaKeterangan($tanggal);
    $this->info("Ditandai Tanpa Keterangan: $n");
})->purpose('Tandai siswa tanpa absensi sebagai Tanpa Keterangan');

// Jalan tiap 5 menit; no-op bila jam absensi masih buka (cek tutup() di dalam).
Schedule::call(fn () => \App\Support\WaktuAbsensi::tandaiTanpaKeterangan())->everyFiveMinutes();
