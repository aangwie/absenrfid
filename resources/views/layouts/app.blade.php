<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Absensi RFID') - SMK Smart School</title>
@php($fav = \App\Models\Pengaturan::nilai('logo_sekolah'))
@if($fav)<link rel="icon" href="{{ $fav }}">@endif
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 font-sans antialiased">
@yield('content')
</body>
</html>
