<?php

namespace App\Http\Controllers;

use App\Jobs\KirimNotifikasiAbsensi;
use App\Models\Absensi;
use App\Models\Siswa;
use App\Support\AntreanWhatsapp;
use Illuminate\Http\Request;
use ZipArchive;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        [$mode, $judul, $q] = $this->filterQuery($request);
        // Pencarian per tabel: 'cari' untuk tabel sudah absen, 'cari_belum' untuk tabel belum absen.
        $cari = trim((string) $request->input('cari', ''));
        $cariBelum = trim((string) $request->input('cari_belum', ''));
        if ($cari !== '') {
            $q->where(function ($w) use ($cari) {
                $w->where('nisn', 'like', "%{$cari}%")
                    ->orWhereHas('siswa', fn ($s) => $s->where('nama', 'like', "%{$cari}%")->orWhere('rfid_tag', 'like', "%{$cari}%"))
                    ->orWhereHas('siswa.kelas', fn ($k) => $k->where('nama', 'like', "%{$cari}%"));
            });
        }
        $rows = $q->orderBy('tanggal')->orderBy('jam_masuk')->paginate(15)->withQueryString();
        // ponytail: belum-absen hanya mode harian (1 query). Tambah periode/bulanan bila diminta.
        $belum = collect();
        if ($mode === 'harian') {
            $t = $request->date('tanggal')?->toDateString() ?? now()->toDateString();
            $ada = Absensi::whereDate('tanggal', $t)->pluck('nisn')->all();
            $belum = Siswa::with('kelas')->when($ada, fn ($q2) => $q2->whereNotIn('nisn', $ada))->orderBy('nama');
            if ($cariBelum !== '') {
                $belum->where(function ($w) use ($cariBelum) {
                    $w->where('nama', 'like', "%{$cariBelum}%")
                        ->orWhere('nisn', 'like', "%{$cariBelum}%")
                        ->orWhere('rfid_tag', 'like', "%{$cariBelum}%")
                        ->orWhereHas('kelas', fn ($k) => $k->where('nama', 'like', "%{$cariBelum}%"));
                });
            }
            $belum = $belum->paginate(10, ['*'], 'page_belum')->withQueryString();
        }

        return view('laporan.index', [
            'rows' => $rows, 'mode' => $mode, 'judul' => $judul, 'belum' => $belum,
            'cari' => $cari, 'cariBelum' => $cariBelum,
            'tanggal' => $request->input('tanggal', now()->toDateString()),
            'dari' => $request->input('dari', now()->toDateString()),
            'sampai' => $request->input('sampai', now()->toDateString()),
            'bulan' => $request->input('bulan', now()->format('Y-m')),
        ]);
    }

    // Harian: satu tanggal. Periode: rentang dari-sampai. Bulanan: YYYY-MM.
    protected function filterQuery(Request $request): array
    {
        $mode = $request->input('mode', 'harian');
        if (! in_array($mode, ['harian', 'periode', 'bulanan'])) {
            $mode = 'harian';
        }
        $q = Absensi::with('siswa.kelas');
        if ($mode === 'periode') {
            $dari = $request->date('dari')?->toDateString() ?? now()->toDateString();
            $sampai = $request->date('sampai')?->toDateString() ?? $dari;
            if ($sampai < $dari) {
                [$dari, $sampai] = [$sampai, $dari];
            }
            $q->whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai);
            $judul = "Periode $dari s.d. $sampai";
        } elseif ($mode === 'bulanan') {
            $b = (string) $request->input('bulan', now()->format('Y-m'));
            if (! preg_match('/^\d{4}-\d{2}$/', $b)) {
                $b = now()->format('Y-m');
            }
            $q->where('tanggal', 'like', "$b%");
            $judul = 'Bulan '.$b;
        } else {
            $mode = 'harian';
            $t = $request->date('tanggal')?->toDateString() ?? now()->toDateString();
            $q->whereDate('tanggal', $t);
            $judul = 'Harian '.$t;
        }

        return [$mode, $judul, $q];
    }

    public function edit(Absensi $absen)
    {
        $absen->load('siswa.kelas');

        return view('laporan.edit', ['r' => $absen]);
    }

    public function perbarui(Request $request, Absensi $absen)
    {
        $data = $request->validate([
            'status_masuk' => ['required', 'in:Di Sekolah,Izin,Sakit,Tanpa Keterangan,Ijin'],
        ]);
        // Normalisasi ejaan lama Ijin -> Izin.
        $st = $data['status_masuk'] === 'Ijin' ? 'Izin' : $data['status_masuk'];
        $this->terapkanStatus($absen, $st);
        $absen->save();

        return redirect()->route('laporan.index', request()->only(['mode', 'tanggal', 'dari', 'sampai', 'bulan']))->with('ok', 'Status diubah jadi '.$st.'.');
    }

    /**
     * Terapkan status manual ke satu baris absensi.
     * Di Sekolah = hadir (jam masuk diisi bila belum ada); status lain
     * mengosongkan jam agar tidak dihitung masuk.
     */
    private function terapkanStatus(Absensi $absen, string $st): void
    {
        if ($st === 'Di Sekolah') {
            if (! $absen->jam_masuk) {
                $absen->jam_masuk = now()->format('H:i:s');
            }
            $absen->status_masuk = 'Di Sekolah';
            if (! $absen->jam_pulang) {
                $absen->status_pulang = 'Belum';
            }

            return;
        }

        $absen->jam_masuk = null;
        $absen->jam_pulang = null;
        $absen->status_masuk = $st;
        $absen->status_pulang = 'Belum';
    }

    public function hapus(Absensi $absen)
    {
        $absen->delete();

        return back()->with('ok', 'Data absensi dihapus.');
    }

    public function bulkHapus(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tb_absensi,id'],
        ], [
            'ids.required' => 'Centang minimal 1 data dulu.',
        ]);

        $count = Absensi::whereIn('id', $data['ids'])->count();
        Absensi::whereIn('id', $data['ids'])->delete();

        return back()->with('ok', "$count data absensi dihapus.");
    }

    public function tandai(Request $request)
    {
        $data = $request->validate([
            'nisn' => ['required', 'exists:tb_siswa,nisn'],
            'tanggal' => ['required', 'date'],
            'status_masuk' => ['required', 'in:Di Sekolah,Izin,Sakit,Tanpa Keterangan,Ijin'],
        ]);
        $st = $data['status_masuk'] === 'Ijin' ? 'Izin' : $data['status_masuk'];
        $absen = Absensi::firstOrNew(['nisn' => $data['nisn'], 'tanggal' => $data['tanggal']]);
        $this->terapkanStatus($absen, $st);
        $absen->save();

        $siswa = Siswa::with('kelas')->where('nisn', $data['nisn'])->first();
        $waHasil = '';
        if ($siswa) {
            $jam = $absen->jam_masuk ? (string) $absen->jam_masuk : '';
            $waHasil = $this->antrekanNotifikasi($siswa, $data['tanggal'], $jam, $st);
        }

        $pesan = 'Status diubah jadi '.$st.'.';
        if ($waHasil !== '') {
            $pesan .= ' WhatsApp '.$waHasil.' dikirim.';
        }

        return redirect()->route('laporan.index', ['mode' => 'harian', 'tanggal' => $data['tanggal']])->with('ok', $pesan);
    }

    /**
     * Tandai banyak siswa sekaligus dari tabel Belum Absen.
     * Notifikasi WA diantrekan (bukan dikirim satu per satu di request ini)
     * agar penandaan puluhan siswa tidak membuat halaman menggantung.
     */
    public function tandaiBulk(Request $request)
    {
        $data = $request->validate([
            'nisn' => ['required', 'array', 'min:1'],
            'nisn.*' => ['string', 'exists:tb_siswa,nisn'],
            'tanggal' => ['required', 'date'],
            'status_masuk' => ['required', 'in:Di Sekolah,Izin,Sakit,Tanpa Keterangan,Ijin'],
        ], [
            'nisn.required' => 'Centang minimal 1 siswa dulu.',
            'nisn.min' => 'Centang minimal 1 siswa dulu.',
        ]);

        $st = $data['status_masuk'] === 'Ijin' ? 'Izin' : $data['status_masuk'];
        $siswas = Siswa::with('kelas')->whereIn('nisn', $data['nisn'])->get();
        $jumlah = 0;
        foreach ($siswas as $siswa) {
            $absen = Absensi::firstOrNew(['nisn' => $siswa->nisn, 'tanggal' => $data['tanggal']]);
            $this->terapkanStatus($absen, $st);
            $absen->save();

            $jam = $absen->jam_masuk ? (string) $absen->jam_masuk : '';
            $this->antrekanNotifikasi($siswa, $data['tanggal'], $jam, $st, false);
            $jumlah++;
        }

        return redirect()->route('laporan.index', ['mode' => 'harian', 'tanggal' => $data['tanggal']])
            ->with('ok', "$jumlah siswa ditandai $st. Notifikasi WhatsApp masuk antrean.");
    }

    /**
     * Catat notifikasi WA untuk aksi Tandai di /laporan (Belum Absen).
     */
    private function antrekanNotifikasi(Siswa $siswa, string $tanggal, string $jam, string $status, bool $langsung = true): string
    {
        try {
            $pesan = KirimNotifikasiAbsensi::susunPesanTandai($siswa, $status, $jam, $tanggal);
            $log = AntreanWhatsapp::antre($siswa, $tanggal, 'masuk', $status, $pesan, $langsung);

            if (! $log) {
                return '';
            }

            if ($log->status === 'gagal') {
                return 'gagal';
            }

            return $langsung
                ? ($log->status === 'terkirim' ? 'berhasil' : 'gagal')
                : 'antre';
        } catch (\Throwable $e) {
            report($e);

            return 'gagal';
        }
    }

    public function ekspor(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:harian,periode,bulanan'],
            'format' => ['required', 'in:xlsx,pdf'],
            'orientasi' => ['required', 'in:portrait,landscape'],
        ]);
        [$mode, $judul, $q] = $this->filterQuery($request);
        // ponytail: muat semua baris filter sekaligus. Pindah ke chunk + stream bila >10rb baris.
        $rows = $q->orderBy('tanggal')->orderBy('jam_masuk')->get();
        $head = ['No', 'Tanggal', 'NISN', 'Nama', 'Kelas', 'Masuk', 'Status Masuk', 'Pulang', 'Status Pulang'];
        $body = [];
        foreach ($rows as $i => $r) {
            $body[] = [$i + 1, (string) $r->tanggal, $r->nisn, $r->siswa->nama ?? '-', $r->siswa->kelas->nama ?? '-', (string) ($r->jam_masuk ?? '-'), (string) $r->status_masuk, (string) ($r->jam_pulang ?? '-'), (string) $r->status_pulang];
        }
        $nama = 'laporan-'.$mode.'-'.now()->format('Ymd-His');

        return $data['format'] === 'xlsx'
            ? $this->unduhXlsx($nama.'.xlsx', $judul, $head, $body)
            : $this->unduhPdf($nama.'.pdf', $judul, $head, $body, $data['orientasi']);
    }

    protected function baris(array $row, int $n, $st = 0): string
    {
        $s = "<row r=\"$n\">";
        foreach ($row as $c => $v) {
            $s0 = is_array($st) ? ($st[$c] ?? 0) : $st;
            $s .= '<c r="'.chr(65 + $c).$n.'"'.($s0 > 0 ? ' s="'.$s0.'"' : '').' t="inlineStr"><is><t>'.htmlspecialchars((string) $v, ENT_XML1).'</t></is></c>';
        }

        return $s.'</row>';
    }

    protected function unduhXlsx(string $nama, string $judul, array $head, array $body)
    {
        // Tabel beneran: judul merge A1:I1, header fill+border, body border, lebar kolom, freeze baris 2.
        $sd = $this->baris([$judul], 1, 1).$this->baris($head, 2, 2);
        foreach ($body as $i => $r) {
            $sd .= $this->baris($r, $i + 3, [4, 4, 4, 3, 3, 4, 4, 4, 4]);
        }
        $last = count($body) + 2;
        $border = '<border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/></border>';
        $styles = '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts><font><sz val="14"/><b/></font><font><sz val="11"/><b/><color rgb="FFFFFFFF"/></font><font><sz val="11"/></font></fonts>'
            .'<fills><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4ED8"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders><border><left/><right/><top/><bottom/></border>'.$border.'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'</cellXfs></styleSheet>';
        $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<cols><col min="1" max="1" width="6"/><col min="2" max="3" width="14"/><col min="4" max="4" width="30"/><col min="5" max="5" width="14"/><col min="6" max="9" width="15"/></cols>'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<mergeCells count="1"><mergeCell ref="A1:I1"/></mergeCells>'
            .'<autoFilter ref="A2:I'.$last.'"/>'
            .'<sheetData>'.$sd.'</sheetData></worksheet>';
        $parts = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => $sheet,
            'xl/styles.xml' => $styles,
        ];
        $tmp = tempnam(sys_get_temp_dir(), 'lap').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($parts as $k => $v) {
            $zip->addFromString($k, $v);
        }
        $zip->close();

        return response()->download($tmp, $nama, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    protected function unduhPdf(string $nama, string $judul, array $head, array $body, string $orientasi)
    {
        // Tabel garis: header fill abu + grid tiap sel, teks potong pas lebar kolom.
        $W = $orientasi === 'landscape' ? 842 : 595;
        $H = $orientasi === 'landscape' ? 595 : 842;
        $m = 36;
        $uw = $W - 2 * $m;
        $fr = [0.05, 0.12, 0.12, 0.24, 0.12, 0.08, 0.105, 0.08, 0.085];
        $cw = array_map(fn ($f) => $uw * $f, $fr);
        $cx = [$m];
        for ($i = 1; $i < 9; $i++) {
            $cx[$i] = $cx[$i - 1] + $cw[$i - 1];
        }
        $esc = fn ($s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string) preg_replace('/[^\x20-\x7E]/', '?', $s));
        $tgl = fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : substr((string) $v, 0, 10);
        $pot = fn ($s, $w) => substr((string) $s, 0, max(1, (int) ($w / 4.2)));
        $rows = [];
        foreach ($body as $i => $r) {
            $rows[] = [$i + 1 > 999 ? '***' : $i + 1, $tgl($r[1]), substr((string) $r[2], 0, 10), substr((string) $r[3], 0, 32), substr((string) $r[4], 0, 14), substr((string) $r[5], 0, 8), substr((string) $r[6], 0, 12), substr((string) $r[7], 0, 8), substr((string) $r[8], 0, 10)];
        }
        if (! $rows) {
            $rows[] = ['-', '-', '-', 'Tidak ada data.', '-', '-', '-', '-', '-'];
        }
        $rh = 13;
        $hh = 15;
        $per = max(5, (int) ((($H - 2 * $m - 60 - $hh) / $rh)));
        $chunks = array_chunk($rows, $per);
        $n = count($chunks);
        $objs = [3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'];
        $kids = [];
        $short = ['No', 'Tanggal', 'NISN', 'Nama', 'Kelas', 'Masuk', 'St.Masuk', 'Pulang', 'St.Plg'];
        foreach ($chunks as $pi => $chunk) {
            $pNum = 5 + $pi * 2;
            $cNum = 6 + $pi * 2;
            $kids[] = "$pNum 0 R";
            $y0 = $H - $m - 14;
            $t = "BT /F1 11 Tf $m $y0 Td (".$esc('Laporan Absensi').") Tj ET\n";
            $y0 -= 13;
            $t .= "BT /F2 8 Tf $m $y0 Td (".$esc($judul.' | '.$orientasi).") Tj ET\n";
            $y = $y0 - 16;
            $t .= "0.85 g\n";
            for ($c = 0; $c < 9; $c++) {
                $t .= sprintf("%.1f %.1f %.1f %.1f re f\n", $cx[$c], $y - $hh, $cw[$c], $hh);
            }
            $t .= "0 g 0.4 w\n";
            for ($c = 0; $c < 9; $c++) {
                $t .= sprintf("%.1f %.1f %.1f %.1f re S\n", $cx[$c], $y - $hh, $cw[$c], $hh);
                $t .= 'BT /F1 7 Tf '.sprintf('%.1f %.1f', $cx[$c] + 2, $y - $hh + 5).' Td ('.$esc($pot($short[$c], $cw[$c])).") Tj ET\n";
            }
            $y -= $hh;
            foreach ($chunk as $r) {
                $y -= $rh;
                for ($c = 0; $c < 9; $c++) {
                    $t .= sprintf("%.1f %.1f %.1f %.1f re S\n", $cx[$c], $y, $cw[$c], $rh);
                    $t .= 'BT /F2 7 Tf '.sprintf('%.1f %.1f', $cx[$c] + 2, $y + 4.5).' Td ('.$esc($pot($r[$c], $cw[$c])).") Tj ET\n";
                }
            }
            $t .= "BT /F1 7 Tf $m ".($m - 4).' Td ('.$esc('Hal '.($pi + 1).' dari '.$n).") Tj ET\n";
            $objs[$cNum] = '<< /Length '.strlen($t)." >>\nstream\n$t\nendstream";
            $objs[$pNum] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $W $H] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $cNum 0 R >>";
        }
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$n.' >>';
        ksort($objs);
        $pdf = "%PDF-1.4\n";
        $offs = [];
        foreach ($objs as $num => $bd) {
            $offs[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$bd\nendobj\n";
        }
        $xp = strlen($pdf);
        $cnt = max(array_keys($objs)) + 1;
        $pdf .= "xref\n0 $cnt\n0000000000 65535 f \n";
        for ($i = 1; $i < $cnt; $i++) {
            $pdf .= isset($offs[$i]) ? sprintf("%010d 00000 n \n", $offs[$i]) : "0000000000 00000 f \n";
        }
        $pdf .= "trailer\n<< /Size $cnt /Root 1 0 R >>\nstartxref\n$xp\n%%EOF";

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$nama.'"']);
    }
}
