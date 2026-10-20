<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FotoWebp
{
    /** Folder penyimpanan relatif terhadap disk public (storage/app/public). */
    public const FOLDER = 'foto';

    /**
     * Simpan file upload ke storage/app/public/foto sebagai WebP (resize maks
     * 600px, kualitas 75). Mengembalikan path relatif disk public, cth:
     * "foto/xxxxxxxx.webp", atau null bila gagal menyimpan.
     */
    public static function simpan(UploadedFile $f, string $folder = self::FOLDER): ?string
    {
        $raw = (string) file_get_contents($f->getRealPath());
        if ($raw === '') {
            return null;
        }

        $isi = null;
        $ext = 'webp';

        if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
            $img = @imagecreatefromstring($raw);
            if ($img !== false) {
                $w = imagesx($img);
                $h = imagesy($img);
                if ($w > 0 && $h > 0 && max($w, $h) > 600) {
                    $skala = 600 / max($w, $h);
                    $nw = max(1, (int) round($w * $skala));
                    $nh = max(1, (int) round($h * $skala));
                    $dst = imagecreatetruecolor($nw, $nh);
                    imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    imagedestroy($img);
                    $img = $dst;
                }
                ob_start();
                $ok = @imagewebp($img, null, 75);
                $webp = (string) ob_get_clean();
                imagedestroy($img);
                if ($ok && $webp !== '') {
                    $isi = $webp;
                }
            }
        }

        // Fallback: GD/WebP tidak tersedia -> simpan file asli apa adanya.
        if ($isi === null) {
            $ext = strtolower($f->getClientOriginalExtension() ?: 'jpg');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $isi = $raw;
        }

        $folder = trim($folder, '/') ?: self::FOLDER;
        Storage::disk('public')->makeDirectory($folder);

        $path = $folder . '/' . Str::random(40) . '.' . $ext;

        return Storage::disk('public')->put($path, $isi) ? $path : null;
    }

    /**
     * Hapus foto lama. Abaikan nilai lama berupa base64/data URI atau URL
     * eksternal agar data lama tetap aman.
     */
    public static function hapus(?string $path): void
    {
        if (! $path || str_starts_with($path, 'data:') || preg_match('~^https?://~i', $path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
