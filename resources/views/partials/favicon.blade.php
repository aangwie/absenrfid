@php($fav = \App\Models\Pengaturan::nilai('logo_sekolah'))
@if($fav)
<link rel="icon" href="{{ $fav }}">
@else
<link rel="icon" href="{{ asset('favicon.ico') }}">
@endif
