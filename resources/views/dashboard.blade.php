@extends('layouts.admin')
@section('title', 'Dashboard Admin')
@section('content')
  <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
    @foreach ([['Total Siswa', $totalSiswa, 'text-white', 'Terdaftar di DB'], ['Siswa Masuk', $masukHariIni, 'text-emerald-400', 'Kehadiran hari ini'], ['Sudah Pulang', $pulangHariIni, 'text-indigo-400', 'Gate Out'], ['Terlambat', $terlambatHariIni, 'text-amber-400', '> 07:00 WIB'], ['Belum Absen', $belumAbsen, 'text-rose-400', 'Tanpa keterangan']] as [$label, $val, $cls, $sub])
    <div class="bg-slate-800/70 border border-slate-700/80 rounded-xl p-4">
      <p class="text-xs font-medium text-slate-400">{{ $label }}</p>
      <h3 class="text-2xl font-bold mt-1 {{ $cls }}">{{ $val }}</h3>
      <span class="text-[11px] text-slate-500">{{ $sub }}</span>
    </div>
    @endforeach
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-slate-800/80 border border-slate-700 rounded-2xl p-5 shadow-xl flex flex-col gap-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-2 border-b border-slate-700/80">
        <h2 class="text-base font-semibold text-white">Rekap Hari Ini</h2>
        <div id="rekap-tabs" class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs font-semibold">
          <button data-f="semua" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white whitespace-nowrap">Semua</button>
          <button data-f="masuk" class="px-3 py-1.5 rounded-lg text-slate-400 whitespace-nowrap">Masuk</button>
          <button data-f="pulang" class="px-3 py-1.5 rounded-lg text-slate-400 whitespace-nowrap">Pulang</button>
          <button data-f="terlambat" class="px-3 py-1.5 rounded-lg text-slate-400 whitespace-nowrap">Terlambat</button>
          <button data-f="belum" class="px-3 py-1.5 rounded-lg text-slate-400 whitespace-nowrap">Belum</button>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <input id="rekap-q" placeholder="Cari Nama / NISN / RFID..." class="sm:col-span-2 w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
        <select id="rekap-kelas" class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500"><option value="Semua">Filter Kelas: Semua</option></select>
      </div>
      <div class="overflow-x-auto rounded-xl border border-slate-700/80">
        <table id="rekap-table" class="w-full text-left text-xs border-collapse">
          <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold">
            <th class="py-3.5 px-4">No</th><th class="py-3.5 px-4">Siswa</th><th class="py-3.5 px-4">NISN / RFID</th><th class="py-3.5 px-4">Kelas</th>
            <th class="py-3.5 px-4 text-center">Masuk</th><th class="py-3.5 px-4 text-center">Pulang</th><th class="py-3.5 px-4 text-center">Status</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-700/60 text-slate-300" id="rekap-body">
            @forelse ($rekap as $i => $s)
            @php($hari = $s->jam_pulang ? 'Pulang' : ($s->jam_masuk ? 'Masuk' : 'Belum Absen'))
            <tr class="hover:bg-slate-700/30 transition-colors" data-nama="{{ strtolower($s->nama.' '.$s->nisn.' '.$s->rfid_tag) }}" data-kelas="{{ $s->kelas_nama ?? '-' }}" data-hari="{{ $hari }}" data-telat="{{ $s->status_masuk === 'Terlambat' ? 1 : 0 }}">
              <td class="py-3.5 px-4 font-mono text-slate-400">{{ $i + 1 }}</td>
              <td class="py-3.5 px-4"><div class="font-semibold text-white text-sm">{{ $s->nama }}</div><div class="text-[11px] text-slate-400">Gender: {{ ($s->gender ?? 'L') === 'P' ? 'Perempuan' : 'Laki-laki' }}</div></td>
              <td class="py-3.5 px-4 font-mono"><div class="text-slate-200">NISN: {{ $s->nisn }}</div><span class="inline-block text-[10px] px-2 py-0.5 rounded border mt-1 font-semibold bg-slate-900 text-slate-400 border-slate-700">RFID: {{ $s->rfid_tag }}</span></td>
              <td class="py-3.5 px-4"><span class="bg-slate-700/60 px-2.5 py-1 rounded-md border border-slate-600/80 font-medium">{{ $s->kelas_nama ?? '-' }}</span></td>
              <td class="py-3.5 px-4 text-center">@if ($s->jam_masuk)<div><span class="font-mono {{ $s->status_masuk === 'Terlambat' ? 'text-amber-300' : 'text-emerald-400' }} font-semibold">{{ $s->jam_masuk }}</span><div><span class="text-[10px] px-2 py-0.5 rounded-full mt-1 inline-block {{ $s->status_masuk === 'Terlambat' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">{{ $s->status_masuk }}</span></div></div>@else<span class="text-slate-500 font-mono">-</span>@endif</td>
              <td class="py-3.5 px-4 text-center">@if ($s->jam_pulang)<div><span class="font-mono text-indigo-400 font-semibold">{{ $s->jam_pulang }}</span><div><span class="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-full mt-1 inline-block">{{ $s->status_pulang }}</span></div></div>@else<span class="text-slate-500 font-mono">-</span>@endif</td>
              <td class="py-3.5 px-4 text-center">@if ($hari === 'Pulang')<span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Selesai Pulang</span>@elseif ($hari === 'Masuk')<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Di Sekolah</span>@else<span class="bg-rose-500/20 text-rose-300 border border-rose-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">Belum Absen</span>@endif</td>
            </tr>
            @empty<tr><td colspan="7" class="py-8 text-center text-slate-500">Belum ada data siswa.</td></tr>@endforelse
            <tr id="rekap-empty" class="hidden"><td colspan="7" class="py-8 text-center text-slate-500">Tidak ada data cocok.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400">
        <span id="rekap-info"></span>
        <div class="flex items-center gap-1">
          <select id="rekap-per" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white"><option>5</option><option selected>10</option><option>25</option></select>
          <button id="rekap-prev" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-700 disabled:opacity-40">‹ Prev</button>
          <span id="rekap-pages" class="flex gap-1"></span>
          <button id="rekap-next" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-700 disabled:opacity-40">Next ›</button>
        </div>
      </div>
      <script>(function(){var b=document.getElementById('rekap-body'),r=Array.prototype.slice.call(b.querySelectorAll('tr[data-nama]')),q=document.getElementById('rekap-q'),kf=document.getElementById('rekap-kelas'),info=document.getElementById('rekap-info'),per=document.getElementById('rekap-per'),pv=document.getElementById('rekap-prev'),nx=document.getElementById('rekap-next'),pg=document.getElementById('rekap-pages'),em=document.getElementById('rekap-empty'),tb=document.getElementById('rekap-tabs'),f='semua',p=1;Array.from(new Set(r.map(function(x){return x.dataset.kelas}))).sort().forEach(function(k){var o=document.createElement('option');o.value=k;o.textContent='Filter Kelas: '+k;kf.appendChild(o)});function R(){var s=q.value.toLowerCase(),L=r.filter(function(x){var a=!s||x.dataset.nama.indexOf(s)!==-1,b=kf.value==='Semua'||x.dataset.kelas===kf.value,h=x.dataset.hari,c=f==='semua'||(f==='masuk'?(h==='Masuk'||h==='Pulang'):f==='pulang'?h==='Pulang':f==='terlambat'?x.dataset.telat==='1':h==='Belum Absen');return a&&b&&c}),n=parseInt(per.value,10)||10,tp=Math.max(1,Math.ceil(L.length/n));p=Math.min(Math.max(1,p),tp);r.forEach(function(x){x.style.display='none'});var S=L.slice((p-1)*n,p*n);S.forEach(function(x,i){x.style.display='';x.cells[0].textContent=(p-1)*n+i+1});em.classList.toggle('hidden',L.length>0);info.textContent='Menampilkan '+S.length+' dari '+L.length+' data • Hal '+p+'/'+tp;pv.disabled=p<=1;nx.disabled=p>=tp;pg.innerHTML='';Array.from({length:tp},function(_,i){return i+1}).slice(0,5).forEach(function(y){var o=document.createElement('button');o.textContent=y;o.className='px-3 py-1.5 rounded-lg border '+(y===p?'bg-indigo-600 text-white border-indigo-600':'bg-slate-900 border-slate-700');o.onclick=function(){p=y;R()};pg.appendChild(o)})}q.addEventListener('input',function(){p=1;R()});kf.addEventListener('change',function(){p=1;R()});per.addEventListener('change',function(){p=1;R()});pv.onclick=function(){p--;R()};nx.onclick=function(){p++;R()};tb.querySelectorAll('button').forEach(function(o){o.onclick=function(){f=o.dataset.f;p=1;tb.querySelectorAll('button').forEach(function(x){x.className='px-3 py-1.5 rounded-lg text-slate-400 whitespace-nowrap'});o.className='px-3 py-1.5 rounded-lg bg-indigo-600 text-white whitespace-nowrap';R()}});R()})();</script>
    </div>
    <div class="flex flex-col gap-6">
      <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
        <h2 class="text-base font-semibold text-white mb-1">Catat Masuk / Pulang</h2>
        <p class="text-[11px] text-slate-500 mb-3">Sama seperti kios: tap pertama = Masuk, tap kedua = Pulang.</p>
        @if (session('tap'))
          @if (session('tap')['ok'])
          <div class="border-2 {{ session('tap')['type'] === 'PULANG' ? 'border-indigo-500 bg-indigo-500/10' : 'border-emerald-500 bg-emerald-500/10' }} rounded-xl p-3 text-center text-sm mb-3">
            <p class="font-bold {{ session('tap')['type'] === 'PULANG' ? 'text-indigo-300' : 'text-emerald-300' }}">{{ session('tap')['type'] }} • {{ session('tap')['jam'] }}</p>
            <h4 class="font-bold text-white mt-1">{{ session('tap')['nama'] }}</h4>
            <p class="text-xs text-slate-400">{{ session('tap')['status'] }}</p>
          </div>
          @else
          <div class="border-2 border-rose-500 bg-rose-500/10 rounded-xl p-3 text-center text-sm mb-3"><p class="font-bold text-rose-300">Gagal</p><p class="text-xs text-rose-200/80 mt-1">{{ session('tap')['pesan'] }}</p></div>
          @endif
        @endif
        <form method="POST" action="{{ route('tap') }}" class="flex flex-col gap-2">@csrf
          <input name="tag" required autofocus autocomplete="off" placeholder="Tempel / ketik RFID / NISN lalu Enter..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 font-mono">
          <button class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Catat Tap</button>
        </form>
        <p class="text-[11px] text-slate-500 mt-2">Gunakan reader USB fisik untuk tap kartu.</p>
      </div>
      <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
        <h2 class="text-base font-semibold text-white mb-3">Tap Terakhir</h2>
        @forelse ($terakhirTap as $t)
        <div class="py-2 border-b border-slate-700/50 text-xs">
          <span class="font-semibold text-white">{{ $t->siswa->nama ?? $t->nisn }}</span>
          <span class="font-mono text-slate-500"> • {{ $t->jam_pulang ?? $t->jam_masuk }}</span>
          <span class="ml-1 px-2 py-0.5 rounded-full {{ $t->jam_pulang ? 'bg-indigo-500/20 text-indigo-300' : 'bg-emerald-500/20 text-emerald-300' }}">{{ $t->jam_pulang ? 'PULANG' : 'MASUK' }}</span>
        </div>
        @empty<p class="text-xs text-slate-500">Belum ada tap hari ini.</p>@endforelse
      </div>
      <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
        <h2 class="text-base font-semibold text-white mb-3">Siswa per Kelas</h2>
        @foreach ($perKelas as $k)
        <div class="flex items-center justify-between py-1.5 text-xs border-b border-slate-700/50">
          <span class="text-slate-300">{{ $k->kelas }}</span><span class="font-mono font-bold text-indigo-300">{{ $k->total }}</span>
        </div>
        @endforeach
      </div>
    </div>
  </div>
@endsection
