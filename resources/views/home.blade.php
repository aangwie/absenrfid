@extends('layouts.app')
@section('title', 'Kios Absensi RFID')

@push('head')
<style>
    /* Tema kios: terang / gelap / auto.
       Palet slate di-override khusus halaman kios supaya kelas Tailwind yang sudah ada ikut berubah. */
    html[data-page="kios"][data-theme="light"] {
        --color-slate-900: #f8fafc;
        --color-slate-800: #ffffff;
        --color-slate-700: #e2e8f0;
        --color-slate-600: #cbd5e1;
        --color-slate-400: #64748b;
        --color-slate-300: #475569;
        --color-slate-200: #334155;
        --color-slate-100: #0f172a;
    }

    /* Permukaan dengan opasitas (header, kartu, input) — eksplisit agar tetap terang di semua browser. */
    html[data-page="kios"][data-theme="light"] .bg-slate-800\/90 { background-color: rgba(255, 255, 255, 0.92); }
    html[data-page="kios"][data-theme="light"] .bg-slate-800\/80 { background-color: rgba(255, 255, 255, 0.85); }
    html[data-page="kios"][data-theme="light"] .bg-slate-800\/70 { background-color: rgba(255, 255, 255, 0.75); }
    html[data-page="kios"][data-theme="light"] .bg-slate-900\/90 { background-color: rgba(248, 250, 252, 0.95); }
    html[data-page="kios"][data-theme="light"] .bg-slate-900\/80 { background-color: rgba(248, 250, 252, 0.90); }
    html[data-page="kios"][data-theme="light"] .bg-slate-600\/40 { background-color: rgba(203, 213, 225, 0.50); }
    html[data-page="kios"][data-theme="light"] .border-slate-700\/80 { border-color: rgba(226, 232, 240, 0.90); }

    /* Tema terang: tiap kartu punya warna latar berbeda. */
    html[data-page="kios"][data-theme="light"] .kios-card-header {
        background-color: #eef2ff;
        background-image: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        border-color: #c7d2fe;
    }
    html[data-page="kios"][data-theme="light"] .kios-stat-0 { background-color: #eff6ff; border-color: #bfdbfe; }
    html[data-page="kios"][data-theme="light"] .kios-stat-1 { background-color: #ecfdf5; border-color: #a7f3d0; }
    html[data-page="kios"][data-theme="light"] .kios-stat-2 { background-color: #f5f3ff; border-color: #ddd6fe; }
    html[data-page="kios"][data-theme="light"] .kios-stat-3 { background-color: #fffbeb; border-color: #fde68a; }
    html[data-page="kios"][data-theme="light"] .kios-card-tap { background-color: #f0f9ff; border-color: #bae6fd; }
    html[data-page="kios"][data-theme="light"] .kios-card-scan { background-color: #faf5ff; border-color: #e9d5ff; }
    html[data-page="kios"][data-theme="light"] .kios-card-scan.kios-scan-ok { background-color: #f0fdfa; border-color: #99f6e4; }
    html[data-page="kios"][data-theme="light"] .kios-card-scan.kios-scan-err { background-color: #fff1f2; border-color: #fecdd3; }
    html[data-page="kios"][data-theme="light"] .kios-card-table { background-color: #f1f5f9; border-color: #cbd5e1; }

    /* Warna teks aksen disesuaikan agar tetap terbaca di latar terang. */
    html[data-page="kios"][data-theme="light"] .text-white { color: #0f172a; }
    html[data-page="kios"][data-theme="light"] .text-blue-400 { color: #1d4ed8; }
    html[data-page="kios"][data-theme="light"] .text-indigo-300,
    html[data-page="kios"][data-theme="light"] .text-indigo-400 { color: #4338ca; }
    html[data-page="kios"][data-theme="light"] .text-emerald-300,
    html[data-page="kios"][data-theme="light"] .text-emerald-400 { color: #047857; }
    html[data-page="kios"][data-theme="light"] .text-amber-400 { color: #b45309; }
    html[data-page="kios"][data-theme="light"] .text-rose-200,
    html[data-page="kios"][data-theme="light"] .text-rose-300 { color: #be123c; }
    html[data-page="kios"][data-theme="light"] .text-rose-200\/80 { color: rgba(190, 18, 60, 0.85); }
    html[data-page="kios"][data-theme="light"] .text-sky-300 { color: #0369a1; }

    /* Teks putih tetap putih pada tombol/badge berlatar warna solid. */
    html[data-page="kios"][data-theme="light"] .bg-indigo-600.text-white,
    html[data-page="kios"][data-theme="light"] .bg-indigo-500.text-white { color: #ffffff; }

    /* Bayangan lebih lembut untuk tema terang. */
    html[data-page="kios"][data-theme="light"] .shadow-xl {
        box-shadow: 0 20px 40px -24px rgba(15, 23, 42, 0.35);
    }
</style>
<script>
    (function () {
        var mode = 'auto';
        try { mode = localStorage.getItem('kios-theme') || 'auto'; } catch (e) {}
        if (mode !== 'light' && mode !== 'dark' && mode !== 'auto') mode = 'auto';
        var gelap = mode === 'dark' || (mode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        var el = document.documentElement;
        el.dataset.page = 'kios';
        el.dataset.mode = mode;
        el.dataset.theme = gelap ? 'dark' : 'light';
    })();
</script>
@endpush

@section('content')
<div id="kios-root" class="min-h-screen p-3 sm:p-6 flex flex-col gap-6"></div>
<script>
    window.__SISWA__ = @json($siswas);
    window.__RENTANG__ = @json($rentang ?? []);
    window.__BOLEH_AWAL__ = {{ ($boleh ?? true) ? 'true' : 'false' }};
    // Dipakai untuk memuat ulang kios saat berganti hari agar jadwal harian terbaru dipakai.
    window.__KIOS_TANGGAL__ = new Date().toDateString();
    window.__JADWAL_HASH__ = @json(md5(json_encode($rentang ?? [])));
</script>
@include('partials.nfc-helper')
<script src="https://unpkg.com/react@18/umd/react.production.min.js"></script><script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/htm@3/dist/htm.umd.js"></script>
<script>
    const html = htm.bind(React.createElement);
    const {
        useState,
        useEffect,
        useRef
    } = React;
    const nowStr = () => new Date().toTimeString().split(' ')[0];
    const R = window.__RENTANG__ || {};
    const dalam = (jam, mulai, selesai) => { const j = (jam || '').slice(0, 5); return mulai && selesai && j >= mulai && j <= selesai; };
    const bolehJam = (jam) => dalam(jam, R.masuk_mulai, R.masuk_selesai) || dalam(jam, R.terlambat_mulai, R.terlambat_selesai) || dalam(jam, R.pulang_mulai, R.pulang_selesai);

    // Nomor halaman yang tampil: maks 5 nomor di sekitar halaman aktif + elipsis.
    function nomorHalaman(aktif, total) {
        const jendela = 5;
        let mulai = Math.max(1, aktif - Math.floor(jendela / 2));
        let selesai = Math.min(total, mulai + jendela - 1);
        mulai = Math.max(1, selesai - jendela + 1);
        const hasil = [];
        if (mulai > 1) {
            hasil.push(1);
            if (mulai > 2) hasil.push('…');
        }
        for (let n = mulai; n <= selesai; n++) hasil.push(n);
        if (selesai < total) {
            if (selesai < total - 1) hasil.push('…');
            hasil.push(total);
        }
        return hasil;
    }

    function App() {
        const [students, setStudents] = useState(window.__SISWA__ || []);
        const [rfid, setRfid] = useState('');
        const [scan, setScan] = useState(null);
        const [clock, setClock] = useState(new Date());
        const [query, setQuery] = useState('');
        const [page, setPage] = useState(1);
        const [nfcMsg, setNfcMsg] = useState('');
        const [nfcBusy, setNfcBusy] = useState(false);
        const [mode, setMode] = useState(function () {
            try {
                const m = localStorage.getItem('kios-theme');
                return (m === 'light' || m === 'dark' || m === 'auto') ? m : 'auto';
            } catch (e) { return 'auto'; }
        });
        const nfcOk = typeof window !== 'undefined' && window.NfcHelper && window.NfcHelper.supported();
        function scanNfc() {
            if (!window.NfcHelper || !window.NfcHelper.supported()) { setNfcMsg('Perangkat/browser tidak mendukung Web NFC. Gunakan reader USB/OTG.'); return; }
            setNfcBusy(true);
            setNfcMsg('Jangan pindah aplikasi — tempelkan kartu ke HP sekarang...');
            window.NfcHelper.read().then(function (tag) { setNfcMsg(''); tap(tag); }).catch(function (err) { setNfcMsg(window.NfcHelper.pesanError(err)); }).finally(function () { setNfcBusy(false); });
        }
        const perPage = 5;
        const ref = useRef(null);
        const jamNow = clock.toTimeString().split(' ')[0];
        const boleh = bolehJam(jamNow);
        const jamBuka = (R.masuk_mulai || '06:30') + '–' + (R.pulang_selesai || '14:30');
        useEffect(() => {
            const t = setInterval(() => setClock(new Date()), 1000);
            return () => clearInterval(t);
        }, []);
        useEffect(() => {
            const mq = window.matchMedia('(prefers-color-scheme: dark)');
            const terapkan = () => {
                const gelap = mode === 'dark' || (mode === 'auto' && mq.matches);
                const el = document.documentElement;
                el.dataset.page = 'kios';
                el.dataset.mode = mode;
                el.dataset.theme = gelap ? 'dark' : 'light';
                try { localStorage.setItem('kios-theme', mode); } catch (e) {}
            };
            terapkan();
            const onSistemBerubah = () => { if (mode === 'auto') terapkan(); };
            if (mq.addEventListener) mq.addEventListener('change', onSistemBerubah);
            else mq.addListener(onSistemBerubah);
            return () => {
                if (mq.removeEventListener) mq.removeEventListener('change', onSistemBerubah);
                else mq.removeListener(onSistemBerubah);
            };
        }, [mode]);
        useEffect(() => {
            const f = setInterval(() => {
                if (ref.current && document.activeElement !== ref.current && !ref.current.disabled) ref.current.focus();
            }, 1500);
            return () => clearInterval(f);
        }, []);
        useEffect(() => {
            const p = setInterval(() => {
                // Lewat tengah malam: muat ulang agar jadwal hari baru langsung berlaku.
                if (new Date().toDateString() !== window.__KIOS_TANGGAL__) { location.reload(); return; }
                fetch('/', { headers: { 'Accept': 'application/json' }, cache: 'no-store' }).then(function (r) {
                    // Jadwal hari ini diubah admin: muat ulang agar rentang jam ikut terbarui.
                    const h = r.headers.get('X-Jadwal-Absensi');
                    if (h && window.__JADWAL_HASH__ && h !== window.__JADWAL_HASH__) { location.reload(); return null; }
                    return r.json();
                }).then(function (d) { if (Array.isArray(d)) setStudents(d); }).catch(() => {});
            }, 5000);
            return () => clearInterval(p);
        }, []);

        function tap(tagRaw) {
            const tag = (tagRaw || '').trim();
            if (!tag) return;
            const jam = nowStr();
            if (!bolehJam(jam)) {
                setScan({ ok: false, pesan: 'Di luar jam absensi (' + jam + '). Jam buka ' + (R.masuk_mulai || '') + '-' + (R.pulang_selesai || '') + '.' });
                setRfid('');
                return;
            }
            const i = students.findIndex(s => s.rfid_tag === tag || s.nisn === tag);
            if (i === -1) {
                setScan({
                    ok: false,
                    pesan: 'Kartu [' + tag + '] tidak terdaftar!'
                });
                setRfid('');
                return;
            }
            const s = students[i],
                next = students.slice();
            if (!s.jam_masuk) {
                const telat = dalam(jam, R.terlambat_mulai, R.terlambat_selesai);
                next[i] = Object.assign({}, s, {
                    jam_masuk: jam,
                    status_masuk: telat ? 'Terlambat' : 'Tepat Waktu'
                });
                setScan({
                    ok: true,
                    type: 'MASUK',
                    nama: s.nama,
                    jam,
                    status: next[i].status_masuk
                });
            } else if (!s.jam_pulang) {
                next[i] = Object.assign({}, s, {
                    jam_pulang: jam,
                    status_pulang: 'Selesai'
                });
                setScan({
                    ok: true,
                    type: 'PULANG',
                    nama: s.nama,
                    jam,
                    status: 'Selesai'
                });
            } else {
                setScan({
                    ok: false,
                    pesan: s.nama + ' sudah tap masuk & pulang.'
                });
                setRfid('');
                return;
            }
            const prev = students;
            // ponytail: kios optimis lokal, DB sinkron via /tap; tambah retry queue bila offline.
            setStudents(next);
            setRfid('');
            fetch('/tap', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '', 'Accept': 'application/json' }, body: JSON.stringify({ tag }) }).then(function (r) { return r.json(); }).then(function (d) { if (!d || !d.ok) { setStudents(prev); setScan({ ok: false, pesan: (d && d.pesan) || 'Tap ditolak server.' }); } }).catch(function () {});
        }
        const masuk = students.filter(s => s.jam_masuk).length;
        const pulang = students.filter(s => s.jam_pulang).length;
        const telat = students.filter(s => s.status_masuk === 'Terlambat').length;
        const q = query.toLowerCase();
        const cocok = students.filter(s => !q || s.nama.toLowerCase().includes(q) || s.nisn.includes(q) || s.rfid_tag.includes(q) || (s.kelas || '').toLowerCase().includes(q));
        // Absen terbaru otomatis tampil paling atas; yang belum absen tetap urut kelas/nama.
        const waktuTerakhir = s => s.jam_pulang || s.jam_masuk || '';
        const filtered = cocok.slice().sort((a, b) => {
            const ta = waktuTerakhir(a),
                tb = waktuTerakhir(b);
            if (ta && tb) return tb.localeCompare(ta);
            if (ta) return -1;
            if (tb) return 1;
            return 0;
        });
        const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        const safePage = Math.min(page, totalPages);
        const rows = filtered.slice((safePage - 1) * perPage, safePage * perPage);
        const dariData = filtered.length ? (safePage - 1) * perPage + 1 : 0;
        const sampaiData = Math.min(safePage * perPage, filtered.length);
        return html`<div className="flex flex-col gap-6">
    <header className="kios-card-header bg-slate-800/90 border border-slate-700 rounded-2xl p-4 sm:p-6 shadow-xl flex flex-col lg:flex-row items-center justify-between gap-4">
      <div className="flex items-center gap-4">
        <div className="w-14 h-14 bg-gradient-to-tr from-blue-600 to-indigo-500 rounded-2xl flex items-center justify-center shrink-0"><span className="w-3 h-3 rounded-full bg-white animate-pulse"></span></div>
        <div><p className="text-[11px] font-semibold text-blue-400">USB HID RFID Reader</p>
        <h1 className="text-xl sm:text-2xl font-bold text-white">Sistem Absensi Siswa RFID</h1>
        <p className="text-slate-400 text-xs sm:text-sm">${clock.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })} • <span className="font-mono font-bold text-indigo-300">${clock.toLocaleTimeString('id-ID')} WIB</span></p></div>
      </div>
      <div className="flex flex-wrap items-center justify-center gap-2">
        <div role="radiogroup" aria-label="Mode tampilan" className="flex items-center gap-1 bg-slate-900/80 border border-slate-700 rounded-xl p-1">
          ${[['light', 'Terang'], ['dark', 'Gelap'], ['auto', 'Auto']].map(function (opt) {
            const aktif = mode === opt[0];
            return html`<label key=${opt[0]} title=${'Mode ' + opt[1]} className=${'cursor-pointer select-none px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-colors focus-within:ring-2 focus-within:ring-indigo-500/40 ' + (aktif ? 'bg-indigo-600 text-white' : 'text-slate-300')}>
              <input type="radio" name="kios-theme" value=${opt[0]} checked=${aktif} onChange=${function () { setMode(opt[0]); }} className="sr-only" />
              ${opt[1]}
            </label>`;
          })}
        </div>
        <a href="/login" className="text-xs font-semibold px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300">Login Admin</a>
      </div>
    </header>
    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
      ${[['Total Siswa', students.length, 'text-white'], ['Masuk', masuk, 'text-emerald-400'], ['Pulang', pulang, 'text-indigo-400'], ['Terlambat', telat, 'text-amber-400']].map((c, i) => html`<div key=${c[0]} className=${'kios-stat kios-stat-' + i + ' bg-slate-800/70 border border-slate-700/80 rounded-xl p-4'}><p className="text-xs text-slate-400">${c[0]}</p><h3 className=${'text-2xl font-bold mt-1 ' + c[2]}>${c[1]}</h3></div>`)}
    </div>
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div className="kios-card-tap lg:col-span-2 bg-slate-800 border border-slate-700 rounded-2xl p-5">
        <h2 className="font-semibold text-white mb-1">Area Tap Reader RFID USB</h2>
        <p className="text-xs text-slate-400 mb-3">Tempelkan kartu pada reader USB — ID terbaca otomatis sebagai keyboard + Enter. Di HP Android (Chrome) bisa juga pakai tombol Scan NFC HP. Jam buka ${jamBuka}.</p>
        ${boleh ? null : html`<div className="bg-rose-500/15 border border-rose-500/40 text-rose-200 text-xs rounded-xl px-4 py-3 mb-3">Di luar jam absensi (${jamNow}). Input dikunci — jam buka ${jamBuka}.</div>`}
        <form onSubmit=${e => { e.preventDefault(); tap(rfid); }} className="relative">
          <input ref=${ref} type="password" autoComplete="off" spellCheck="false" value=${rfid} onInput=${e => setRfid(e.target.value)} placeholder=${boleh ? 'Tempelkan kartu RFID di sini...' : 'Di luar jam absensi — input dikunci...'} disabled=${!boleh}
            className=${'w-full bg-slate-900/90 border-2 rounded-xl px-4 py-3.5 text-white font-mono focus:outline-none ' + (boleh ? 'border-indigo-500/50' : 'border-slate-700 opacity-50 cursor-not-allowed')} />
          <button disabled=${!boleh} className="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-600 text-white text-xs font-semibold px-4 py-2 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed">Proses Tap</button>
        </form>
        ${nfcOk ? html`<button onClick=${scanNfc} disabled=${!boleh || nfcBusy} className="mt-2 w-full border border-slate-600 text-slate-200 text-xs font-semibold px-4 py-2.5 rounded-xl disabled:opacity-40 disabled:cursor-not-allowed">${nfcBusy ? 'Menunggu kartu ditempel...' : 'Scan NFC HP'}</button>` : null}
        ${nfcMsg ? html`<p className="text-[11px] text-slate-400 mt-2">${nfcMsg}</p>` : null}
      </div>
      <div className=${'kios-card-scan bg-slate-800 border border-slate-700 rounded-2xl p-5 text-center' + (scan ? (scan.ok ? ' kios-scan-ok' : ' kios-scan-err') : '')}>
        ${scan ? (scan.ok ? html`<div><p className="text-xs font-bold px-3 py-1 rounded-full inline-block ${scan.type === 'MASUK' ? 'bg-emerald-500 text-slate-950' : 'bg-indigo-500 text-white'}">ABSEN ${scan.type} • ${scan.jam}</p><h4 className="font-bold text-white mt-2">${scan.nama}</h4><p className="text-xs text-slate-400">${scan.status}</p></div>`
          : html`<div><p className="font-bold text-rose-300">Gagal</p><p className="text-xs text-rose-200/80 mt-1">${scan.pesan}</p></div>`)
        : html`<div className="border-2 border-dashed border-slate-700 rounded-xl p-6 text-slate-500 text-sm">Siap menunggu tap RFID USB...</div>`}
        <p className="text-[11px] text-slate-500 mt-3">Gunakan reader USB fisik, OTG di HP, atau Scan NFC HP (Chrome Android).</p>
      </div>
    </div>
    <div className="kios-card-table bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
        <h2 className="font-semibold text-white">Data Siswa Hari Ini <span className="text-[11px] font-normal text-slate-500">· absen terbaru di atas</span></h2>
        <input value=${query} onInput=${e => { setQuery(e.target.value); setPage(1); }} placeholder="Cari nama / NISN / RFID / kelas..."
          className="w-full sm:w-64 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
      </div>
      <div className="overflow-x-auto"><table className="w-full text-xs"><thead><tr className="text-slate-400 border-b border-slate-700"><th className="text-left py-2 px-2">Nama / NISN</th><th className="text-left py-2 px-2">Kelas</th><th className="text-center py-2 px-2">Masuk</th><th className="text-center py-2 px-2">Pulang</th><th className="text-center py-2 px-2">Status</th></tr></thead>
      <tbody>${rows.length ? rows.map(s => { const st = (s.status_masuk === 'Sakit' || s.status_masuk === 'Izin' || s.status_masuk === 'Ijin') ? html`<span className="bg-sky-500/20 text-sky-300 border border-sky-500/40 px-2 py-0.5 rounded-full text-[11px]">${s.status_masuk}</span>` : s.status_masuk === 'Tanpa Keterangan' ? html`<span className="bg-slate-600/40 text-slate-300 border border-slate-600 px-2 py-0.5 rounded-full text-[11px]">Tanpa Keterangan</span>` : s.jam_pulang ? html`<span className="bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full text-[11px]">Pulang</span>` : (s.jam_masuk || s.status_masuk === 'Di Sekolah') ? html`<span className="bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full text-[11px]">Di Sekolah</span>` : html`<span className="bg-rose-500/20 text-rose-300 px-2 py-0.5 rounded-full text-[11px]">Belum Absen</span>`; return html`<tr key=${s.nisn} className="border-b border-slate-700/50"><td className="py-2.5 px-2"><div className="font-semibold text-white">${s.nama}</div><div className="font-mono text-slate-500 text-[11px]">${s.nisn} • ${s.rfid_tag}</div></td><td className="py-2.5 px-2 text-slate-300">${s.kelas}</td><td className="py-2.5 px-2 text-center font-mono text-emerald-300">${s.jam_masuk || '-'}</td><td className="py-2.5 px-2 text-center font-mono text-indigo-300">${s.jam_pulang || '-'}</td><td className="py-2.5 px-2 text-center">${st}</td></tr>`; }) : html`<tr><td colSpan="5" className="py-8 text-center text-slate-500">Tidak ada data cocok.</td></tr>`}</tbody></table></div>
      <div className="flex flex-wrap items-center justify-between gap-2 mt-3 text-xs text-slate-400">
        <span>Menampilkan ${dariData}–${sampaiData} dari ${filtered.length} data</span>
        <div className="flex flex-wrap gap-1">
          <button disabled=${safePage <= 1} onClick=${() => setPage(safePage - 1)} aria-label="Sebelumnya" className="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 disabled:opacity-40"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-4 h-4"><path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg></button>
          ${nomorHalaman(safePage, totalPages).map((n, i) => n === '…'
            ? html`<span key=${'e' + i} className="px-2 py-1.5 text-slate-500">…</span>`
            : html`<button key=${'p' + n} onClick=${() => setPage(n)} className=${'px-3 py-1.5 rounded-lg border ' + (n === safePage ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-900 border-slate-700')}>${n}</button>`)}
          <button disabled=${safePage >= totalPages} onClick=${() => setPage(safePage + 1)} aria-label="Berikutnya" className="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 disabled:opacity-40"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-4 h-4"><path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg></button>
        </div>
      </div>
    </div>
    <footer className="text-center text-xs text-slate-500 py-2 border-t border-slate-800">SMP Smart School — Dev. By Aang Wirawan</footer>
  </div>`;
    }
    ReactDOM.createRoot(document.getElementById('kios-root')).render(React.createElement(App));
</script>
@endsection