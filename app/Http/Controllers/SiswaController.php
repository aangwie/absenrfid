<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Support\FotoWebp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use ZipArchive;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        if (Kelas::count() === 0) {
            return response()->view('siswa.need-kelas');
        }
        $perPage = (int) $request->integer('per_page', 10);
        if (! in_array($perPage, [5, 10, 15, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $sortMap = [
            'nama' => 'tb_siswa.nama',
            'nisn' => 'tb_siswa.nisn',
            'rfid_tag' => 'tb_siswa.rfid_tag',
            'tempat_lahir' => 'tb_siswa.tempat_lahir',
            'tanggal_lahir' => 'tb_siswa.tanggal_lahir',
            'tahun_masuk' => 'tb_siswa.tahun_masuk',
            'kelas' => 'k.nama',
            'no_hp_ortu' => 'tb_siswa.no_hp_ortu',
            'gender' => 'tb_siswa.gender',
        ];
        $sort = (string) $request->query('sort', '');
        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $siswas = Siswa::with('kelas')
            ->leftJoin('tb_kelas as k', 'k.id', '=', 'tb_siswa.kelas_id')
            ->when($request->q, fn ($q) => $q->where('tb_siswa.nama', 'like', "%{$request->q}%")->orWhere('tb_siswa.nisn', 'like', "%{$request->q}%")->orWhere('tb_siswa.rfid_tag', 'like', "%{$request->q}%")->orWhere('tb_siswa.no_hp_ortu', 'like', "%{$request->q}%")->orWhere('tb_siswa.tempat_lahir', 'like', "%{$request->q}%")->orWhere('k.nama', 'like', "%{$request->q}%"))
            ->select('tb_siswa.*');

        if (isset($sortMap[$sort])) {
            $kolom = $sortMap[$sort];
            // Nilai kosong (NULL) selalu di bawah, baik ASC maupun DESC.
            $siswas->orderByRaw("($kolom IS NULL)")->orderBy($kolom, $dir);
            if ($sort !== 'nama') {
                $siswas->orderBy('tb_siswa.nama');
            }
            if ($sort !== 'kelas') {
                $siswas->orderBy('k.nama');
            }
        } else {
            $siswas->orderBy('k.nama')->orderBy('tb_siswa.nama');
        }

        $siswas = $siswas->paginate($perPage)->withQueryString();

        return view('siswa.index', ['siswas' => $siswas, 'kelasList' => Kelas::orderBy('nama')->get()]);
    }

    public function create()
    {
        if (Kelas::count() === 0) {
            return response()->view('siswa.need-kelas');
        }

        return view('siswa.create', ['kelasList' => Kelas::orderBy('nama')->get()]);
    }

    public function store(Request $request)
    {
        if (Kelas::count() === 0) {
            return redirect()->route('kelas.index', ['need' => 1]);
        }
        $data = $request->validate($this->rules(), $this->messages());
        $data['foto'] = $this->simpanFoto($request);
        if ($data['foto'] === null) {
            unset($data['foto']);
        }
        Siswa::create($data);

        return redirect()->route('siswa.index')->with('ok', 'Siswa ditambah.');
    }

    public function edit(Siswa $siswa)
    {
        if (Kelas::count() === 0) {
            return response()->view('siswa.need-kelas');
        }

        return view('siswa.edit', ['siswa' => $siswa, 'kelasList' => Kelas::orderBy('nama')->get()]);
    }

    public function update(Request $request, Siswa $siswa)
    {
        if (Kelas::count() === 0) {
            return redirect()->route('kelas.index', ['need' => 1]);
        }
        $data = $request->validate($this->rules($siswa->id), $this->messages());
        $fotoLama = $siswa->foto;
        $foto = $this->simpanFoto($request);
        if ($foto !== null) {
            $data['foto'] = $foto;
        } elseif ($request->boolean('hapus_foto')) {
            $data['foto'] = null;
        } else {
            unset($data['foto']);
        }
        $siswa->update($data);
        if (array_key_exists('foto', $data) && $fotoLama && $fotoLama !== $siswa->foto) {
            FotoWebp::hapus($fotoLama);
        }

        return redirect()->route('siswa.index')->with('ok', 'Siswa diupdate.');
    }

    /** Edit cepat NISN / RFID dari tabel halaman siswa (double-click). */
    public function updateIdentitas(Request $request, Siswa $siswa)
    {
        $data = $request->validate([
            'nisn' => ['required', 'regex:/^[0-9]{1,10}$/', Rule::unique('tb_siswa', 'nisn')->ignore($siswa->id)],
            'rfid_tag' => ['required', 'string', 'max:50', Rule::unique('tb_siswa', 'rfid_tag')->ignore($siswa->id)],
        ], [
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.regex' => 'NISN hanya angka, maks 10 digit.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'rfid_tag.required' => 'RFID wajib diisi.',
            'rfid_tag.max' => 'RFID maks 50 karakter.',
            'rfid_tag.unique' => 'RFID sudah dipakai siswa lain.',
        ]);

        $nisnLama = $siswa->nisn;

        DB::transaction(function () use ($siswa, $data, $nisnLama) {
            // FK tb_absensi.nisn memakai ON UPDATE CASCADE (lihat migrasi 2026_10_25_000010).
            $siswa->update($data);
            if ($nisnLama !== $siswa->nisn) {
                DB::table('tb_notifikasi_log')->where('nisn', $nisnLama)->update(['nisn' => $siswa->nisn]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'nama' => $siswa->nama,
                'nisn' => $siswa->nisn,
                'rfid_tag' => $siswa->rfid_tag,
            ]);
        }

        return redirect()->route('siswa.index')->with('ok', 'NISN/RFID siswa diupdate.');
    }

    public function destroy(Siswa $siswa)
    {
        $siswa->delete();

        return redirect()->route('siswa.index')->with('ok', 'Siswa dihapus.');
    }

    public function bulkPindah(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tb_siswa,id'],
            'kelas_id' => ['required', 'exists:tb_kelas,id'],
        ], [
            'ids.required' => 'Pilih minimal 1 siswa dulu.',
            'kelas_id.required' => 'Pilih kelas tujuan dulu.',
            'kelas_id.exists' => 'Kelas tujuan tidak valid.',
        ]);

        $count = Siswa::whereIn('id', $data['ids'])->update(['kelas_id' => $data['kelas_id']]);

        return redirect()->route('siswa.index')->with('ok', "$count siswa dipindah kelas.");
    }

    public function bulkHapus(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tb_siswa,id'],
        ], [
            'ids.required' => 'Pilih minimal 1 siswa dulu.',
        ]);

        $siswas = Siswa::whereIn('id', $data['ids'])->get(['id', 'foto']);
        $count = $siswas->count();
        foreach ($siswas as $s) {
            $s->hapusFoto();
        }
        Siswa::whereIn('id', $data['ids'])->delete();

        return redirect()->route('siswa.index')->with('ok', "$count siswa dihapus.");
    }

    public function cekRfid(Request $request)
    {
        $tag = trim((string) $request->query('rfid_tag', ''));
        if ($tag === '') {
            return response()->json(['exists' => false]);
        }
        $s = Siswa::where('rfid_tag', $tag)->when($request->filled('ignore_id'), fn ($q) => $q->where('id', '!=', $request->query('ignore_id')))->first(['nama', 'nisn']);

        return response()->json(['exists' => (bool) $s, 'nama' => $s->nama ?? null, 'nisn' => $s->nisn ?? null]);
    }

    public function template()
    {
        $path = $this->buildTemplate();

        return response()->download($path, 'template-siswa.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:5120']], ['file.required' => 'Pilih file .xlsx dulu.', 'file.mimes' => 'File harus .xlsx.']);
        try {
            $rows = $this->parseXlsx($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Gagal baca .xlsx: ' . $e->getMessage()]);
        }
        // ponytail: loop per baris OK utk <2rb baris. Upgrade batch upsert + queue bila >10rb.
        // Kolom template: NISN | RFID_TAG | NAMA | KELAS | GENDER | NO_HP_ORTU | TEMPAT_LAHIR | TANGGAL_LAHIR | TAHUN_MASUK
        // File lama 6 kolom (tanpa 3 kolom terakhir) tetap diterima, 3 kolom baru dianggap kosong.
        $ok = 0;
        $fail = [];
        foreach ($rows as $i => $r) {
            $line = $i + 2;
            [$nisn, $rfid, $nama, $kelasNama, $gender, $noHp, $tempatLahir, $tglLahir, $thnMasuk] = array_map('trim', array_pad(array_values($r), 9, ''));
            $gender = strtoupper($gender);
            if ($nisn === '' || $rfid === '' || $nama === '' || $kelasNama === '' || ! in_array($gender, ['L', 'P'], true)) {
                $fail[] = "Baris $line: tak lengkap / gender bukan L/P.";

                continue;
            }
            if (! preg_match('/^[0-9]{1,10}$/', $nisn)) {
                $fail[] = "Baris $line: NISN harus angka maks 10 digit.";

                continue;
            }
            if ($noHp !== '' && ! preg_match('/^[+]?[0-9][0-9\s-]{6,18}$/', $noHp)) {
                $fail[] = "Baris $line: no HP ortu/wali tidak valid.";

                continue;
            }
            if (Siswa::where('rfid_tag', $rfid)->where('nisn', '!=', $nisn)->exists()) {
                $fail[] = "Baris $line: RFID $rfid dipakai siswa lain.";

                continue;
            }
            if ($tempatLahir !== '' && mb_strlen($tempatLahir) > 50) {
                $fail[] = "Baris $line: tempat lahir maks 50 karakter.";

                continue;
            }
            $tglDb = null;
            if ($tglLahir !== '') {
                $ts = strtotime($tglLahir);
                if ($ts === false) {
                    $fail[] = "Baris $line: tanggal lahir tidak valid (pakai YYYY-MM-DD).";

                    continue;
                }
                $tglDb = date('Y-m-d', $ts);
                if ($tglDb > date('Y-m-d')) {
                    $fail[] = "Baris $line: tanggal lahir tidak boleh masa depan.";

                    continue;
                }
            }
            $thnDb = null;
            if ($thnMasuk !== '') {
                if (! preg_match('/^[0-9]{4}$/', $thnMasuk) || (int) $thnMasuk < 2000 || (int) $thnMasuk > 2100) {
                    $fail[] = "Baris $line: tahun masuk harus angka 2000-2100.";

                    continue;
                }
                $thnDb = (int) $thnMasuk;
            }
            try {
                $kelasId = Kelas::firstOrCreate(['nama' => mb_substr($kelasNama, 0, 30)])->id;
                Siswa::updateOrCreate(['nisn' => $nisn], ['rfid_tag' => $rfid, 'nama' => $nama, 'kelas_id' => $kelasId, 'gender' => $gender, 'no_hp_ortu' => $noHp !== '' ? mb_substr($noHp, 0, 20) : null, 'tempat_lahir' => $tempatLahir !== '' ? mb_substr($tempatLahir, 0, 50) : null, 'tanggal_lahir' => $tglDb, 'tahun_masuk' => $thnDb]);
                $ok++;
            } catch (\Throwable $e) {
                $fail[] = "Baris $line: gagal simpan.";
            }
        }

        return back()->with('ok', "Import: $ok ok, " . count($fail) . ' gagal.')->with('fail', $fail);
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'nisn' => ['required', 'regex:/^[0-9]{1,10}$/', Rule::unique('tb_siswa', 'nisn')->ignore($ignoreId)],
            'rfid_tag' => ['required', 'string', 'max:50', Rule::unique('tb_siswa', 'rfid_tag')->ignore($ignoreId)],
            'nama' => ['required', 'string', 'max:100'],
            'tempat_lahir' => ['nullable', 'string', 'max:50'],
            'tanggal_lahir' => ['nullable', 'date', 'before_or_equal:today'],
            'tahun_masuk' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:300'],
            'kelas_id' => ['required', 'exists:tb_kelas,id'],
            'gender' => ['required', 'in:L,P'],
            'no_hp_ortu' => ['nullable', 'string', 'max:20', 'regex:/^[+]?[0-9][0-9\s-]{6,18}$/'],
        ];
    }

    private function simpanFoto(Request $request): ?string
    {
        if (! $request->hasFile('foto')) {
            return null;
        }
        $f = $request->file('foto');
        if (! $f->isValid()) {
            return null;
        }

        return FotoWebp::simpan($f);
    }

    private function parseXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('bukan xlsx valid.');
        }
        try {
            $sheet = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $n = $zip->getNameIndex($i);
                if (preg_match('#xl/worksheets/sheet\d+\.xml#', $n)) {
                    $sheet = $n;
                    break;
                }
            }
            if (! $sheet) {
                throw new \RuntimeException('sheet tak ketemu.');
            }
            $shared = [];
            if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                foreach ($this->loadXmlTolerant($this->sanitizeSheetXml($ss))->si as $si) {
                    $t = '';
                    foreach ($si->t as $x) {
                        $t .= (string) $x;
                    }
                    foreach ($si->r as $r) {
                        foreach ($r->t as $x) {
                            $t .= (string) $x;
                        }
                    }
                    $shared[] = $t;
                }
            }
            $xml = $this->loadXmlTolerant($this->sanitizeSheetXml((string) $zip->getFromName($sheet)));
            $rows = [];
            foreach ($xml->sheetData->row as $row) {
                $cells = ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => '', 'F' => '', 'G' => '', 'H' => '', 'I' => ''];
                foreach ($row->c as $c) {
                    preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
                    $col = $m[1] ?? '';
                    if (! isset($cells[$col])) {
                        continue;
                    }
                    $t = (string) $c['t'];
                    if ($t === 's') {
                        $cells[$col] = $shared[(int) $c->v] ?? '';
                    } elseif ($t === 'inlineStr') {
                        $txt = isset($c->is->t) ? (string) $c->is->t : '';
                        foreach ($c->is->r as $rr) {
                            foreach ($rr->t as $tt) {
                                $txt .= (string) $tt;
                            }
                        }
                        $cells[$col] = $txt;
                    } else {
                        $cells[$col] = (string) $c->v;
                    }
                }
                $rows[] = $cells;
            }
        } finally {
            $zip->close();
        }
        if ($rows) {
            array_shift($rows);
        }

        return array_values(array_filter($rows, fn ($r) => implode('', $r) !== ''));
    }

    private function loadXmlTolerant(string $xml): \SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $el = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT);
        if ($el === false) {
            $el = simplexml_load_string($this->declareMissingPrefixes($xml), 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT);
        }
        if ($el === false) {
            throw new \RuntimeException('struktur sheet tidak dikenal.');
        }

        return $el;
    }

    private function sanitizeSheetXml(string $xml): string
    {
        $xml = (string) preg_replace('/\s+[A-Za-z_][\w.-]*:[\w.-]+\s*=\s*("[^"]*"|\'[^\']*\')/', '', $xml);
        $xml = (string) preg_replace('#</?[A-Za-z_][\w.-]*:[\w.-]+[^>]*>#', '', $xml);
        $xml = (string) preg_replace('/xmlns(:\w+)?="[^"]*"/', '', $xml);
        return $xml;
    }

    private function declareMissingPrefixes(string $xml): string
    {
        preg_match_all('/(?:<|\s)([A-Za-z_][\w.-]*):[\w.-]+/', $xml, $m);
        preg_match_all('/xmlns:([A-Za-z_][\w.-]*)=/', $xml, $d);
        $declared = array_fill_keys($d[1], true);
        $xmlns = '';
        foreach (array_unique($m[1]) as $p) {
            if (! isset($declared[$p])) {
                $xmlns .= ' xmlns:'.$p.'="urn:lazy:'.$p.'"';
            }
        }
        if ($xmlns === '') {
            return $xml;
        }

        return preg_replace('/<([A-Za-z_][\w.-]*)(\s[^>]*)?>/', '<$1$2'.$xmlns.'>', $xml, 1);
    }

    private function buildTemplate(): string
    {
        $rows = [['NISN', 'RFID_TAG', 'NAMA', 'KELAS', 'GENDER', 'NO_HP_ORTU', 'TEMPAT_LAHIR', 'TANGGAL_LAHIR', 'TAHUN_MASUK'], ['0082234749', '2234749610', 'Muhammad Rizky Pratama', 'XII RPL 1', 'L', '6281234567890', 'Bandung', '2010-05-12', '2025'], ['0082345678', '2094812', 'Siti Aminah', 'XII RPL 2', 'P', '6289876543210', 'Jakarta', '2011-08-20', '2025']];
        $sd = '';
        foreach ($rows as $i => $r) {
            $n = $i + 1;
            $sd .= "<row r=\"$n\">";
            foreach (range(0, 8) as $c) {
                $v = htmlspecialchars($r[$c] ?? '', ENT_XML1);
                $sd .= '<c r="'.chr(65 + $c).$n.'" t="inlineStr"><is><t>'.$v.'</t></is></c>';
            }
            $sd .= '</row>';
        }
        $parts = ['[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>', '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>', 'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Siswa" sheetId="1" r:id="rId1"/></sheets></workbook>', 'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>', 'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sd.'</sheetData></worksheet>', 'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs></styleSheet>'];
        $tmp = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($parts as $k => $v) {
            $zip->addFromString($k, $v);
        }
        $zip->close();

        return $tmp;
    }

    private function messages(): array
    {
        return ['nisn.required' => 'NISN wajib diisi.', 'nisn.regex' => 'NISN hanya angka, maks 10 digit.', 'nisn.unique' => 'NISN sudah terdaftar.', 'rfid_tag.unique' => 'RFID sudah dipakai siswa lain.', 'tempat_lahir.max' => 'Tempat lahir maks 50 karakter.', 'tanggal_lahir.date' => 'Tanggal lahir tidak valid.', 'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh masa depan.', 'tahun_masuk.integer' => 'Tahun masuk harus angka.', 'tahun_masuk.min' => 'Tahun masuk minimal 2000.', 'tahun_masuk.max' => 'Tahun masuk maksimal 2100.', 'foto.mimes' => 'Pas foto harus jpg/png/webp.', 'foto.max' => 'Pas foto maks 300 Kb.', 'kelas_id.required' => 'Pilih kelas dulu.', 'kelas_id.exists' => 'Kelas tidak valid, buat dulu di menu Kelas.', 'gender.in' => 'Gender harus L atau P.', 'no_hp_ortu.regex' => 'No HP ortu/wali tidak valid.']; 
    }
}
