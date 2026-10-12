@extends('layouts.admin')
@section('title', 'Data Siswa')
@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({title: 'Belum ada kelas!', text: 'Buat kelas dulu sebelum kelola siswa.', icon: 'info', confirmButtonColor: '#4f46e5', confirmButtonText: 'OK, buat kelas', background: '#1e293b', color: '#f1f5f9', allowOutsideClick: false}).then(() => { window.location.href = "{{ route('kelas.index', ['need' => 1]) }}"; });
setTimeout(() => { window.location.href = "{{ route('kelas.index', ['need' => 1]) }}"; }, 4000);
</script>
<div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-8 text-center text-sm text-slate-400">Belum ada kelas. Mengarahkan ke pembuatan kelas...</div>
@endsection