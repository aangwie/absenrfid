<div class="card" data-nama-file="{{ $s->nisn }}_belakang">
  <div class="wm">@for ($i = 0; $i < 40; $i++)<span>{{ $p['nama_institusi'] ?: 'SMK Smart School' }}</span>@endfor</div>
  <div class="back-title">TATA TERTIB PEMEGANG KARTU</div>
  <ol class="tatib">
    <li>Kartu ini identitas resmi peserta didik, wajib dibawa setiap mengikuti kegiatan sekolah.</li>
    <li>Wajib tap kartu pada mesin absensi saat masuk dan pulang sekolah.</li>
    <li>Dilarang meminjamkan atau memindahtangankan kartu kepada orang lain.</li>
    <li>Kehilangan/kerusakan segera lapor ke wali kelas atau Tata Usaha.</li>
    <li>Penggantian kartu hilang/rusak dikenakan biaya administrasi.</li>
    <li>Kartu yang ditemukan harap dikembalikan ke sekolah yang bersangkutan.</li>
  </ol>
  <div class="ttd">
    <div class="cap">
      <div>Mengetahui,<br>Kepala Sekolah</div>
      <div class="sign-zone">
        @if (!empty($p['ttd_kepala_sekolah']))
        <img class="ttd-img" src="{{ $p['ttd_kepala_sekolah'] }}" alt="TTD">
        @else
        <div class="ttd-space"></div>
        @endif
        @if (!empty($p['stempel_sekolah']))
        <img class="stempel" src="{{ $p['stempel_sekolah'] }}" alt="Stempel">
        @endif
        <div class="sign-name"><u>{{ $p['nama_kepala_sekolah'] ?: '( ............................ )' }}</u>@if(!empty($p['nip_kepala_sekolah']))<br>NIP. {{ $p['nip_kepala_sekolah'] }}@endif</div>
      </div>
    </div>
  </div>
  <!--div class="foot">{{ $s->nama }} • {{ $s->nisn }}</div-->
</div>
