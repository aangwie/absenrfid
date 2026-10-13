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
            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()->withErrors(['msg' => 'Konfigurasi WhatsApp (URL/API Key/Number) belum disimpan.'])->with('wa_hasil', [
                'sukses' => false, 'http' => null, 'tujuan' => trim($data['tujuan']), 'waktu' => now()->toDateTimeString(),
                'ringkasan' => 'Konfigurasi belum lengkap. Isi URL, API Key, dan Number dulu di tab Konfigurasi.',
                'detail' => 'wa_url / wa_api_key / wa_number kosong.',
            ]);
        }

        try {
            $tujuan = trim($data['tujuan']);
            $res = Http::withHeaders(['Content-Type' => 'application/x-www-form-urlencoded'])
                ->asForm()
                ->timeout(30)
                ->post($url, [
                    'nomor_pengirim' => $sender,
                    'api_key' => $apiKey,
                    'nomor_penerima' => $tujuan,
                    'pesan' => $data['pesan'],
                ]);

            $body = (string) $res->body();
            $detail = $body;
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $detail = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            if (strlen($detail) > 2000) {
                $detail = substr($detail, 0, 2000) . '... (dipotong)';
            }
            $log = 'POST ' . $url . ' [form-urlencoded]' . "\n"
                . 'HTTP ' . $res->status() . "\n" . $detail;

            if ($res->successful()) {
                $ket = 'Berhasil: API merespons HTTP ' . $res->status() . '. Cek detail respons & pastikan pesan masuk di nomor tujuan.';
                if (is_array($decoded)) {
                    foreach (['message', 'msg', 'status', 'result', 'success'] as $k) {
                        if (isset($decoded[$k]) && (is_string($decoded[$k]) || is_numeric($decoded[$k]) || is_bool($decoded[$k]))) {
                            $ket .= ' | ' . $k . ': ' . (is_bool($decoded[$k]) ? ($decoded[$k] ? 'true' : 'false') : $decoded[$k]);
                        }
                    }
                }
                return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()->with('ok', 'Pesan terkirim (HTTP ' . $res->status() . ').')->with('log', $log)->with('wa_hasil', [
                    'sukses' => true, 'http' => $res->status(), 'tujuan' => $tujuan, 'waktu' => now()->toDateTimeString(),
                    'ringkasan' => $ket, 'detail' => $detail,
                ]);
            }

            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()->withErrors(['msg' => 'Gagal kirim (HTTP ' . $res->status() . '). Periksa detail respons di bawah.'])->with('log', $log)->with('wa_hasil', [
                'sukses' => false, 'http' => $res->status(), 'tujuan' => $tujuan, 'waktu' => now()->toDateTimeString(),
                'ringkasan' => 'Gagal: API merespons HTTP ' . $res->status() . ' (bukan 2xx). Umumnya API Key / sender / format nomor salah, atau kuota habis. Lihat detail respons.',
                'detail' => $detail,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('whatsapp.index', ['tab' => 'uji'])->withInput()->withErrors(['msg' => 'Gagal kirim: ' . $e->getMessage()])->with('wa_hasil', [
                'sukses' => false, 'http' => null, 'tujuan' => trim($data['tujuan']), 'waktu' => now()->toDateTimeString(),
                'ringkasan' => 'Gagal: koneksi ke API bermasalah (' . $e->getMessage() . '). Cek URL, jaringan server, dan timeout.',
                'detail' => $e->getMessage(),
            ]);
        }
    }
}
