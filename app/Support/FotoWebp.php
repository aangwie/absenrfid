<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class FotoWebp
{
    /**
     * Konversi file upload (jpg/png/webp) menjadi data URI WebP yang kecil.
     * Foto di-resize bila sisi terpanjang > 600px lalu di-encode WebP q75.
     * Fallback ke data URI original bila GD/WebP tak tersedia.
     */
    public static function keDataUri(UploadedFile $f, int $sisiMaks = 600, int $kualitas = 75): string
    {
        $raw = (string) file_get_contents($f->getRealPath());
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return 'data:' . $f->getMimeType() . ';base64,' . base64_encode($raw);
        }
        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            return 'data:' . $f->getMimeType() . ';base64,' . base64_encode($raw);
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > 0 && $h > 0 && max($w, $h) > $sisiMaks) {
            $skala = $sisiMaks / max($w, $h);
            $nw = max(1, (int) round($w * $skala));
            $nh = max(1, (int) round($h * $skala));
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $dst;
        }
        ob_start();
        $ok = @imagewebp($img, null, $kualitas);
        $webp = (string) ob_get_clean();
        imagedestroy($img);
        if (! $ok || $webp === '') {
            return 'data:' . $f->getMimeType() . ';base64,' . base64_encode($raw);
        }

        return 'data:image/webp;base64,' . base64_encode($webp);
    }
}
