@extends('layouts.admin')
@section('title', 'Edit Siswa')
@section('content')
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5 max-w-lg">
  @if ($errors->any())<div class="mb-4 bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">{{ $errors->first() }}</div>@endif
  <form id="form-siswa" method="POST" action="{{ route('siswa.update', $siswa) }}" enctype="multipart/form-data" class="flex flex-col gap-4 text-sm">@csrf @method('PUT')
    <div><label class="block text-xs text-slate-300 mb-1">NISN (angka, maks 10 digit)</label><input name="nisn" value="{{ old('nisn', $siswa->nisn) }}" required inputmode="numeric" pattern="[0-9]{1,10}" maxlength="10" title="NISN hanya angka, maks 10 digit" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white font-mono focus:outline-none focus:border-indigo-500"></div>
    <div><label class="block text-xs text-slate-300 mb-1">RFID Tag</label><input id="rfid_tag" name="rfid_tag" value="{{ old('rfid_tag', $siswa->rfid_tag) }}" required autocomplete="off" class="w-full bg-slate-900 border border-indigo-500/60 rounded-xl px-3 py-2.5 text-indigo-300 font-mono font-bold focus:outline-none focus:border-indigo-400"><p id="rfid_warn" class="hidden mt-1 text-[11px] text-rose-300"></p></div>
<script>
(function(){const f=document.getElementById('form-siswa'),el=document.getElementById('rfid_tag'),w=document.getElementById('rfid_warn');let t;async function cek(v){const r=await fetch("{{ route('siswa.cek-rfid') }}?rfid_tag="+encodeURIComponent(v)+"&ignore_id={{ $siswa->id }}",{headers:{'X-Requested-With':'XMLHttpRequest'}});return r.json();}el.addEventListener('input',()=>{clearTimeout(t);w.classList.add('hidden');t=setTimeout(async()=>{const v=el.value.trim();if(!v)return;try{const j=await cek(v);if(j.exists){w.textContent='RFID '+v+' sudah dipakai '+(j.nama||'')+'. Input dikosongkan.';w.classList.remove('hidden');el.value='';el.focus();}}catch(e){}},350);});f.addEventListener('submit',async(e)=>{const v=el.value.trim();if(!v)return;e.preventDefault();try{const j=await cek(v);if(j.exists){w.textContent='RFID '+v+' sudah dipakai '+(j.nama||'')+'. Update dibatalkan.';w.classList.remove('hidden');el.value='';el.focus();return;}f.submit();}catch(err){f.submit();}});})();
</script>
    <div><label class="block text-xs text-slate-300 mb-1">Nama</label><input name="nama" value="{{ old('nama', $siswa->nama) }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500"></div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div><label class="block text-xs text-slate-300 mb-1">Tempat Lahir (opsional)</label><input name="tempat_lahir" value="{{ old('tempat_lahir', $siswa->tempat_lahir) }}" maxlength="50" placeholder="cth Bandung" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500"></div>
      <div><label class="block text-xs text-slate-300 mb-1">Tanggal Lahir (opsional)</label><input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('Y-m-d') : '') }}" max="{{ date('Y-m-d') }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500"></div>
      <div><label class="block text-xs text-slate-300 mb-1">Tahun Masuk (opsional)</label><input type="number" name="tahun_masuk" value="{{ old('tahun_masuk', $siswa->tahun_masuk) }}" min="2000" max="2100" placeholder="cth 2025" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500"></div>
    </div>
    <div><label class="block text-xs text-slate-300 mb-1">Nomor HP Orang Tua / Wali (opsional)</label><input name="no_hp_ortu" value="{{ old('no_hp_ortu', $siswa->no_hp_ortu) }}" inputmode="tel" maxlength="20" placeholder="cth 6281234567890" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white font-mono focus:outline-none focus:border-indigo-500"></div>
    <div>
      <label class="block text-xs text-slate-300 mb-1">Pas Foto 3x4 (opsional, jpg/png/webp maks 300 Kb, otomatis jadi WebP di storage/app/public/foto)</label>
      @if ($siswa->foto_url)<div class="flex items-center gap-3 mb-2"><img src="{{ $siswa->foto_url }}" alt="Foto" class="h-16 w-12 object-cover rounded-lg bg-white/10 border border-slate-700"><label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer"><input type="checkbox" name="hapus_foto" value="1" class="accent-rose-600"> Hapus foto</label></div>@endif
      <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs text-slate-300 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div><label class="block text-xs text-slate-300 mb-1">Kelas</label><select name="kelas_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500"><option value="">-- Pilih kelas --</option>@foreach ($kelasList as $k)<option value="{{ $k->id }}" @selected(old('kelas_id', $siswa->kelas_id)==$k->id)>{{ $k->nama }}</option>@endforeach</select></div>
      <div><label class="block text-xs text-slate-300 mb-1">Gender</label><select name="gender" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:border-indigo-500">
          <option value="L" @selected(old('gender', $siswa->gender) === 'L')>Laki-laki (L)</option>
          <option value="P" @selected(old('gender', $siswa->gender) === 'P')>Perempuan (P)</option>
        </select></div>
    </div>
    <div class="flex gap-2"><a href="{{ route('siswa.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-700 text-slate-300 text-sm">Batal</a><button class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2.5 rounded-xl">Update</button></div>
  </form>
</div>
@endsection