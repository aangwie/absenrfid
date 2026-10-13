<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WhatsappController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'konfigurasi');
        if (! in_array($tab, ['konfigurasi', 'uji'])) {
            $tab = 'konfigurasi';
        }
        $def = ['wa_url' => '', 'wa_api_key' => '', 'wa_number' => ''];
        $cfg = array_merge($def, Pengaturan::ambil());

        return view('whatsapp.index', ['tab' => $tab, 'cfg' => $cfg]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'wa_url' => ['required', 'url', 'max:500'],
            'wa_api_key' => ['required', 'string', 'max:500'],
            'wa_number' => ['required', 'string', 'max:30'],
        ], [
            'wa_url.required' => 'URL API wajib diisi.',
            'wa_url.url' => 'URL API tidak valid.',
            'wa_api_key.required' => 'API Key wajib diisi.',
            'wa_number.required' => 'Number pengirim wajib diisi.',
        ]);

        foreach (['wa_url' => trim($data['wa_url']), 'wa_api_key' => trim($data['wa_api_key']), 'wa_number' => trim($data['wa_number'])] as $k => $v) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => $v, 'updated_at' => now(), 'created_at' => now()]);
        }

        return redirect()->route('whatsapp.index', ['tab' => 'konfigurasi'])->with('ok', 'Konfigurasi WhatsApp tersimpan.');
    }

    public function uji(Request $request)
    {
        $data = $request->validate([
            'tujuan' => ['required', 'string', 'max:30'],
            'pesan' => ['required', 'string', 'max:1000'],
        ], [
            'tujuan.required' => 'Nomor tujuan wajib diisi.',
            'pesan.required' => 'Pesan wajib diisi.',
        ]);

        $cfg = Pengaturan::ambil();
        $url = trim($cfg['wa_url'] ?? '');
        $apiKey = trim($cfg['wa_api_key'] ?? '');
        $sender = trim($cfg['wa_number'] ?? '');

        if (! $url || ! $apiKey || ! $sender) {
            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withErrors(['msg' => 'Konfigurasi WhatsApp (URL/API Key/Number) belum disimpan.']);
        }

        try {
            $res = Http::withHeaders(['Content-Type' => 'application/x-www-form-urlencoded'])
                ->asForm()
                ->timeout(30)
                ->post($url, [
                    'api_key' => $apiKey,
                    'sender' => $sender,
                    'number' => trim($data['tujuan']),
                    'message' => $data['pesan'],
                ]);

            $body = (string) $res->body();
            if (strlen($body) > 2000) {
                $body = substr($body, 0, 2000) . '... (dipotong)';
            }
            $log = 'POST ' . $url . ' [form-urlencoded]' . "\n"
                . 'HTTP ' . $res->status() . "\n" . $body;

            if ($res->successful()) {
                return redirect()->route('whatsapp.index', ['tab' => 'uji'])->with('ok', 'Pesan terkirim (HTTP ' . $res->status() . ').')->with('log', $log);
            }

            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withErrors(['msg' => 'Gagal kirim (HTTP ' . $res->status() . ').'])->with('log', $log);
        } catch (\Throwable $e) {
            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withErrors(['msg' => 'Gagal kirim: ' . $e->getMessage()]);
        }
    }
}
