<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FotoController extends Controller
{
    /**
     * Sajikan foto siswa langsung dari storage/app/public/foto.
     * Tidak bergantung pada symlink public/storage maupun APP_URL, sehingga
     * tetap tampil di tabel, kartu pelajar, cetak PDF, dan ekspor gambar.
     */
    public function show(string $file): BinaryFileResponse
    {
        $file = basename($file);
        abort_if($file === '' || $file === '.' || $file === '..', 404);

        $path = 'foto/' . $file;
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return response()->file($disk->path($path), [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
