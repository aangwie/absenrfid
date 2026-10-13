<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DokumentasiController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'dashboard');
        if (! in_array($tab, ['dashboard', 'siswa', 'kelas', 'laporan', 'whatsapp', 'pengaturan'])) {
            $tab = 'dashboard';
        }

        return view('dokumentasi.index', ['tab' => $tab]);
    }
}
