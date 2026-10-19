@extends('layouts.admin')
@section('title', 'Manajemen User')
@section('content')
@if (session('ok'))<div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs rounded-xl px-4 py-3">{{ $errors->first() }}</div>@endif

<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-5">
  @if (auth()->user()->isAdmin())
  <div class="flex items-center justify-between gap-2 mb-3">
    <h2 class="text-sm font-bold text-white">Tambah User</h2>
    <span class="text-[11px] text-slate-500">Nama, email, nomor HP, role, password</span>
  </div>
  <form method="POST" action="{{ route('users.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-2">@csrf
    <div>
      <label class="block text-xs text-slate-300 mb-1">Nama</label>
      <input name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Nama lengkap" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
    </div>
    <div>
      <label class="block text-xs text-slate-300 mb-1">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" placeholder="nama@sekolah.test" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
    </div>
    <div>
      <label class="block text-xs text-slate-300 mb-1">Nomor HP</label>
      <input name="nomor_hp" value="{{ old('nomor_hp') }}" required maxlength="30" placeholder="08xxxxxxxxxx" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
    </div>
    <div>
      <label class="block text-xs text-slate-300 mb-1">Role</label>
      <select name="role" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
        <option value="admin" {{ old('role', 'admin') === 'admin' ? 'selected' : '' }}>Admin</option>
        <option value="guru" {{ old('role') === 'guru' ? 'selected' : '' }}>Guru</option>
      </select>
    </div>
    <div>
      <label class="block text-xs text-slate-300 mb-1">Password</label>
      <div class="relative">
        <input type="password" name="password" id="new-password" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 pr-10 text-sm text-white focus:outline-none focus:border-indigo-500">
        <button type="button" class="eye-btn absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-white" data-target="new-password" aria-label="Tampilkan password">
          <svg class="w-5 h-5 eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <svg class="w-5 h-5 eye-off hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
        </button>
      </div>
    </div>
    <div>
      <label class="block text-xs text-slate-300 mb-1">Konfirmasi Password</label>
      <div class="relative">
        <input type="password" name="password_confirmation" id="new-password-confirm" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 pr-10 text-sm text-white focus:outline-none focus:border-indigo-500">
        <button type="button" class="eye-btn absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-white" data-target="new-password-confirm" aria-label="Tampilkan konfirmasi password">
          <svg class="w-5 h-5 eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <svg class="w-5 h-5 eye-off hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
        </button>
      </div>
    </div>
    <div class="sm:col-span-2 flex justify-end">
      <button class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">+ Tambah User</button>
    </div>
  </form>
  @endif

  <form method="GET" class="flex flex-col sm:flex-row gap-2 my-4">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama, email, atau nomor HP..." class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
    <button class="bg-slate-700 hover:bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded-xl">Cari</button>
  </form>

  <div class="overflow-x-auto rounded-xl border border-slate-700/80"><table class="w-full text-left text-xs border-collapse">
    <thead><tr class="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold"><th class="py-3.5 px-4">No</th><th class="py-3.5 px-4">Nama</th><th class="py-3.5 px-4">Email</th><th class="py-3.5 px-4">Nomor HP</th><th class="py-3.5 px-4">Role</th><th class="py-3.5 px-4 text-center">Aksi</th></tr></thead>
    <tbody class="divide-y divide-slate-700/60 text-slate-300">@forelse ($users as $i => $u)<tr class="hover:bg-slate-700/30 transition-colors"><td class="py-3.5 px-4 font-mono text-slate-400">{{ $users->firstItem() + $i }}</td><td class="py-3.5 px-4"><div class="font-semibold text-white text-sm">{{ $u->name }}</div>@if ($u->id === auth()->id())<span class="text-[10px] px-2 py-0.5 rounded-full inline-block mt-1 bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Akun ini</span>@endif</td><td class="py-3.5 px-4 font-mono">{{ $u->email }}</td><td class="py-3.5 px-4 font-mono">{{ $u->nomor_hp ?: '-' }}</td>
    <td class="py-3.5 px-4">@if ($u->role === 'guru')<span class="text-[10px] px-2 py-0.5 rounded-full inline-block bg-sky-500/20 text-sky-300 border border-sky-500/30">Guru</span>@else<span class="text-[10px] px-2 py-0.5 rounded-full inline-block bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Admin</span>@endif</td>
    <td class="py-3.5 px-4 text-center whitespace-nowrap"><button type="button" title="Edit {{ $u->name }}" class="edit-btn inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-700 hover:bg-indigo-600 text-slate-200 hover:text-white transition-all mr-1" data-id="{{ $u->id }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}" data-hp="{{ $u->nomor_hp }}" data-role="{{ $u->role }}" data-action="{{ route('users.update', $u) }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></button>@if (auth()->user()->isAdmin())<form method="POST" action="{{ route('users.destroy', $u) }}" class="inline del-form" data-nama="{{ $u->name }}">@csrf @method('DELETE')<button title="Hapus {{ $u->name }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-600/20 border border-rose-500/40 text-rose-300 hover:bg-rose-600 hover:text-white transition-all"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button></form>@endif</td></tr>@empty<tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada user. Tambah dulu.</td></tr>@endforelse</tbody>
  </table></div>

  <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4 text-xs text-slate-400">
    <span>{{ $users->total() > 0 ? 'Menampilkan '.$users->firstItem().'–'.$users->lastItem().' dari '.$users->total().' data' : 'Tidak ada data' }}</span>
    @include('partials.paginasi', ['pg' => $users])
  </div>
</div>

<!-- Modal edit user -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
  <div class="w-full max-w-lg bg-slate-800 border border-slate-700 rounded-2xl p-5 shadow-2xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-sm font-bold text-white">Edit User</h2>
      <button type="button" id="edit-close" class="text-slate-400 hover:text-white text-lg leading-none">✕</button>
    </div>
    <form method="POST" id="edit-form" class="grid grid-cols-1 sm:grid-cols-2 gap-3">@csrf @method('PUT')
      <div class="sm:col-span-2">
        <label class="block text-xs text-slate-300 mb-1">Nama</label>
        <input name="name" id="edit-name" required maxlength="255" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
      </div>
      <div class="sm:col-span-2">
        <label class="block text-xs text-slate-300 mb-1">Email</label>
        <input type="email" name="email" id="edit-email" required maxlength="255" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
      </div>
      <div class="sm:col-span-2">
        <label class="block text-xs text-slate-300 mb-1">Nomor HP</label>
        <input name="nomor_hp" id="edit-hp" required maxlength="30" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
      </div>
      @if (auth()->user()->isAdmin())
      <div class="sm:col-span-2">
        <label class="block text-xs text-slate-300 mb-1">Role</label>
        <select name="role" id="edit-role" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
          <option value="admin">Admin</option>
          <option value="guru">Guru</option>
        </select>
      </div>
      @endif
      <div>
        <label class="block text-xs text-slate-300 mb-1">Password baru (opsional)</label>
        <div class="relative">
          <input type="password" name="password" id="edit-password" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 pr-10 text-sm text-white focus:outline-none focus:border-indigo-500">
          <button type="button" class="eye-btn absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-white" data-target="edit-password" aria-label="Tampilkan password">
            <svg class="w-5 h-5 eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <svg class="w-5 h-5 eye-off hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
          </button>
        </div>
      </div>
      <div>
        <label class="block text-xs text-slate-300 mb-1">Konfirmasi Password</label>
        <div class="relative">
          <input type="password" name="password_confirmation" id="edit-password-confirm" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 pr-10 text-sm text-white focus:outline-none focus:border-indigo-500">
          <button type="button" class="eye-btn absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-white" data-target="edit-password-confirm" aria-label="Tampilkan konfirmasi password">
            <svg class="w-5 h-5 eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <svg class="w-5 h-5 eye-off hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
          </button>
        </div>
      </div>
      <div class="sm:col-span-2 flex justify-end gap-2 mt-2">
        <button type="button" id="edit-cancel" class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold px-4 py-2 rounded-xl">Batal</button>
        <button class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
  // Toggle tampilkan/sembunyikan password (logo mata).
  document.querySelectorAll('.eye-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.target);
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      var open = btn.querySelector('.eye-open');
      var off = btn.querySelector('.eye-off');
      if (open) open.classList.toggle('hidden', show);
      if (off) off.classList.toggle('hidden', !show);
    });
  });

  // Modal edit user.
  var modal = document.getElementById('edit-modal');
  var form = document.getElementById('edit-form');
  function openModal(btn) {
    form.action = btn.dataset.action;
    document.getElementById('edit-name').value = btn.dataset.name || '';
    document.getElementById('edit-email').value = btn.dataset.email || '';
    document.getElementById('edit-hp').value = btn.dataset.hp || '';
    var role = document.getElementById('edit-role');
    if (role) role.value = btn.dataset.role || 'admin';
    document.getElementById('edit-password').value = '';
    document.getElementById('edit-password-confirm').value = '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }
  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
  document.querySelectorAll('.edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function () { openModal(btn); });
  });
  document.getElementById('edit-close')?.addEventListener('click', closeModal);
  document.getElementById('edit-cancel')?.addEventListener('click', closeModal);
  modal?.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

  // Konfirmasi hapus.
  document.querySelectorAll('.del-form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var nama = f.dataset.nama || 'user ini';
      if (window.Swal && Swal.fire) {
        Swal.fire({title: 'Hapus ' + nama + '?', text: 'User tidak bisa dipulihkan.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#475569', confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', background: '#1e293b', color: '#f1f5f9'}).then(function (r) { if (r.isConfirmed) f.submit(); });
      } else if (window.confirm('Hapus ' + nama + '?')) {
        f.submit();
      }
    });
  });
})();
</script>
@endsection
